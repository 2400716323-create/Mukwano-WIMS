<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\PutAwayTask;
use App\Models\StockBalance;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PutAwayTaskController extends Controller
{
    public function create(Request $request, StockBalance $stockBalance): View
    {
        $this->ensureWarehouseUser($request->user());
        abort_unless($request->user()->isSupervisor(), 403);

        $stockBalance->load('batch.product', 'location.parent.parent');

        abort_unless((float) $stockBalance->quantity > 0, 404);

        $this->ensurePutAwaySource($stockBalance->location);

        $storekeepers = User::query()
            ->whereIn('role', [User::ROLE_STOREKEEPER, 'user'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $destinationBins = Location::query()
            ->where('type', 'bin')
            ->where('status', 'active')
            ->whereKeyNot($stockBalance->location_id)
            ->with('parent.parent')
            ->orderBy('code')
            ->get()
            ->filter(fn (Location $location) => $location->isOperational())
            ->values();

        return view('put-away.tasks.create', compact(
            'stockBalance',
            'storekeepers',
            'destinationBins'
        ));
    }

    public function store(Request $request, StockBalance $stockBalance): RedirectResponse
    {
        $this->ensureWarehouseUser($request->user());
        abort_unless($request->user()->isSupervisor(), 403);

        $data = $request->validate([
            'assigned_to' => ['required', 'integer', 'exists:users,id'],
            'destination_location_id' => ['required', 'integer', 'exists:locations,id'],
            'quantity' => [
                'required',
                'numeric',
                'gt:0',
                'lte:'.$stockBalance->quantity,
                'decimal:0,3',
            ],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $assignee = User::query()->findOrFail($data['assigned_to']);

        if (! $assignee->isStorekeeper() || ! $assignee->is_active) {
            throw ValidationException::withMessages([
                'assigned_to' => 'Choose an active storekeeper.',
            ]);
        }

        $task = DB::transaction(function () use ($request, $stockBalance, $data): PutAwayTask {
            $lockedBalance = StockBalance::query()
                ->lockForUpdate()
                ->findOrFail($stockBalance->id);

            if ((float) $data['quantity'] > (float) $lockedBalance->quantity) {
                throw ValidationException::withMessages([
                    'quantity' => 'The requested quantity is greater than the available stock.',
                ]);
            }

            if ((float) $lockedBalance->quantity <= 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'There is no available stock to put away.',
                ]);
            }

            $source = Location::query()
                ->findOrFail($lockedBalance->location_id);

            $this->ensurePutAwaySource($source);

            $destination = Location::query()
                ->findOrFail($data['destination_location_id']);

            $this->ensurePutAwayDestination($destination, $source);

            return PutAwayTask::query()->create([
                'task_number' => 'PUT-'.now()->format('Y').'-'.Str::upper(Str::random(6)),
                'stock_balance_id' => $lockedBalance->id,
                'batch_id' => $lockedBalance->batch_id,
                'source_location_id' => $lockedBalance->location_id,
                'destination_location_id' => $destination->id,
                'quantity' => $data['quantity'],
                'status' => PutAwayTask::STATUS_PENDING,
                'assigned_to' => $data['assigned_to'],
                'created_by' => $request->user()->id,
                'notes' => $data['notes'] ?? null,
            ]);
        });

        return to_route('put-away.index')
            ->with('status', "Put-away task {$task->task_number} created and assigned successfully.");
    }

    public function start(Request $request, PutAwayTask $putAwayTask): RedirectResponse
    {
        $this->authorizeTaskActor($request->user(), $putAwayTask);

        DB::transaction(function () use ($putAwayTask): void {
            $task = PutAwayTask::query()
                ->lockForUpdate()
                ->findOrFail($putAwayTask->id);

            if ($task->status !== PutAwayTask::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'task' => 'Only pending tasks can be started.',
                ]);
            }

            $balance = StockBalance::query()
                ->lockForUpdate()
                ->find($task->stock_balance_id);

            if (
                $balance === null
                || (float) $balance->quantity < (float) $task->quantity
            ) {
                throw ValidationException::withMessages([
                    'task' => 'The source stock balance no longer has enough quantity for this task. Ask a manager to review it.',
                ]);
            }

            if ((int) $balance->location_id !== (int) $task->source_location_id) {
                throw ValidationException::withMessages([
                    'task' => 'The stock has moved from the original source location. Ask a manager to review this task.',
                ]);
            }

            $source = Location::query()->findOrFail($balance->location_id);

            $this->ensurePutAwaySource($source);

            $task->update([
                'status' => PutAwayTask::STATUS_IN_PROGRESS,
                'started_at' => now(),
            ]);
        });

        return back()->with(
            'status',
            "Task {$putAwayTask->task_number} marked In Progress."
        );
    }

    public function complete(
        Request $request,
        PutAwayTask $putAwayTask,
        InventoryService $inventoryService
    ): RedirectResponse {
        $this->authorizeTaskActor($request->user(), $putAwayTask);

        DB::transaction(function () use (
            $putAwayTask,
            $request,
            $inventoryService
        ): void {
            $task = PutAwayTask::query()
                ->lockForUpdate()
                ->findOrFail($putAwayTask->id);

            if ($task->status !== PutAwayTask::STATUS_IN_PROGRESS) {
                throw ValidationException::withMessages([
                    'task' => 'Start this task before completing it.',
                ]);
            }

            $balance = StockBalance::query()
                ->lockForUpdate()
                ->find($task->stock_balance_id);

            if (
                $balance === null
                || (float) $balance->quantity < (float) $task->quantity
            ) {
                throw ValidationException::withMessages([
                    'task' => 'The source location no longer has enough stock. The task was not completed.',
                ]);
            }

            if ((int) $balance->location_id !== (int) $task->source_location_id) {
                throw ValidationException::withMessages([
                    'task' => 'The stock has moved from the original source location. The task was not completed.',
                ]);
            }

            $source = Location::query()->findOrFail($balance->location_id);
            $destination = Location::query()->findOrFail($task->destination_location_id);

            $this->ensurePutAwaySource($source);
            $this->ensurePutAwayDestination($destination, $source);

            $inventoryService->putAway(
                $balance,
                $destination,
                (string) $task->quantity,
                $request->user(),
                (string) Str::uuid()
            );

            $task->update([
                'status' => PutAwayTask::STATUS_COMPLETED,
                'completed_by' => $request->user()->id,
                'completed_at' => now(),
            ]);
        });

        return to_route('put-away.index')
            ->with('status', "Task {$putAwayTask->task_number} completed and stock moved successfully.");
    }

    private function ensurePutAwaySource(?Location $source): void
    {
        if (
            $source === null
            || ! in_array($source->type, ['bin', 'receiving', 'staging'], true)
            || ! $source->isOperational()
        ) {
            throw ValidationException::withMessages([
                'task' => 'The source must be an active operational storage bin, Receiving Area, or Staging Area.',
            ]);
        }
    }

    private function ensurePutAwayDestination(
        Location $destination,
        Location $source
    ): void {
        if (
            $destination->type !== 'bin'
            || $destination->status !== 'active'
            || ! $destination->isOperational()
            || (int) $destination->id === (int) $source->id
        ) {
            throw ValidationException::withMessages([
                'destination_location_id' => 'Choose a different active operational storage bin.',
            ]);
        }
    }

    private function ensureWarehouseUser(User $user): void
    {
        abort_unless(
            $user->isAdministrator()
            || $user->isWarehouseManager()
            || $user->isStorekeeper(),
            403
        );
    }

    private function authorizeTaskActor(User $user, PutAwayTask $task): void
    {
        $this->ensureWarehouseUser($user);

        abort_unless(
            $user->isSupervisor()
            || (int) $task->assigned_to === (int) $user->id,
            403
        );
    }
}
