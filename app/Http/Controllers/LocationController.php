<?php

namespace App\Http\Controllers;

use App\Http\Requests\MoveBinInventoryRequest;
use App\Http\Requests\StoreLocationRequest;
use App\Http\Requests\UpdateLocationRequest;
use App\Models\BinInventory;
use App\Models\Location;
use App\Models\StockBalance;
use App\Services\AuditService;
use App\Services\InventoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LocationController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Location::class);
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
        ]);
        $warehouses = Location::query()
            ->whereNull('parent_id')
            ->where('type', 'warehouse')
            ->with('children.children.children.inventory.product')
            ->orderBy('name')
            ->get();

        $rows = [];
        foreach ($warehouses as $warehouse) {
            $this->flattenTree($warehouse, 0, [], $rows, $filters['search'] ?? null);
        }

        $occupiedBins = collect($rows)
            ->filter(fn (array $row): bool => $row['location']->type === 'bin' && $row['quantity'] > 0)
            ->count();
        $availableBins = collect($rows)
            ->filter(fn (array $row): bool => $row['location']->type === 'bin'
                && $row['location']->isOperational()
                && $row['quantity'] <= 0)
            ->count();

        $metrics = [
            'total_locations' => Location::query()->count(),
            'warehouses' => Location::query()->where('type', 'warehouse')->count(),
            'available_bins' => $availableBins,
            'occupied_bins' => $occupiedBins,
        ];

        return view('locations.index', compact('rows', 'occupiedBins', 'availableBins', 'metrics'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Location::class);
        $data = $request->validate([
            'type' => ['nullable', 'in:warehouse,zone,rack,bin'],
            'parent_id' => ['nullable', 'integer', 'exists:locations,id'],
        ]);
        $type = $data['type'] ?? 'warehouse';
        $parent = isset($data['parent_id']) ? Location::query()->findOrFail($data['parent_id']) : null;
        $expectedParentType = match ($type) {
            'zone' => 'warehouse',
            'rack' => 'zone',
            'bin' => 'rack',
            default => null,
        };

        if (($parent?->type) !== $expectedParentType || ($parent !== null && ! $parent->isOperational())) {
            abort(404);
        }

        return view('locations.form', [
            'location' => new Location(['type' => $type, 'parent_id' => $parent?->id]),
            'parent' => $parent,
        ]);
    }

    public function store(StoreLocationRequest $request, AuditService $auditService): RedirectResponse
    {
        $attributes = $request->validated();
        $location = DB::transaction(function () use ($attributes, $auditService): Location {
            $location = Location::query()->create([
                ...$attributes,
                'status' => 'active',
            ]);
            $auditService->record($location, 'created', [], $location->only([
                'parent_id',
                'type',
                'name',
                'code',
                'status',
            ]));

            return $location;
        });

        return to_route('locations.show', $location)->with('status', ucfirst($location->type).' created.');
    }

    public function show(Location $location): View
    {
        $this->authorize('view', $location);
        $location->load([
            'parent.parent.parent',
            'children.children.children',
            'inventory.product.category',
        ]);
        $destinationBins = $location->type === 'bin' && $location->isOperational()
            ? Location::query()
                ->where('type', 'bin')
                ->where('status', 'active')
                ->whereKeyNot($location->id)
                ->orderBy('code')
                ->get()
                ->filter(fn (Location $bin): bool => $bin->isOperational())
            : collect();
        $quantity = $location->inventory->sum(fn (BinInventory $inventory): float => (float) $inventory->quantity);

        return view('locations.show', compact('location', 'destinationBins', 'quantity'));
    }

    public function edit(Location $location): View
    {
        $this->authorize('update', $location);
        $location->load('parent');

        return view('locations.form', [
            'location' => $location,
            'parent' => $location->parent,
        ]);
    }

    public function update(
        UpdateLocationRequest $request,
        Location $location,
        AuditService $auditService,
    ): RedirectResponse {
        $oldValues = $location->only(['name', 'code']);
        $newValues = $request->validated();

        DB::transaction(function () use ($location, $oldValues, $newValues, $auditService): void {
            $location->update($newValues);
            $auditService->record($location, 'updated', $oldValues, $location->only(array_keys($oldValues)));
        });

        return to_route('locations.show', $location)->with('status', ucfirst($location->type).' updated.');
    }

    public function deactivate(Location $location, AuditService $auditService): RedirectResponse
    {
        $this->authorize('update', $location);

        DB::transaction(function () use ($location, $auditService): void {
            $lockedLocation = Location::query()->lockForUpdate()->findOrFail($location->id);
            $oldStatus = $lockedLocation->status;
            $lockedLocation->update(['status' => 'inactive']);
            $auditService->record($lockedLocation, 'deactivated', ['status' => $oldStatus], ['status' => 'inactive']);
        });

        return to_route('locations.index')->with('status', ucfirst($location->type).' deactivated.');
    }

    public function activate(Location $location, AuditService $auditService): RedirectResponse
    {
        $this->authorize('update', $location);
        $location->load('parent.parent.parent');
        if ($location->parent !== null && ! $location->parent->isOperational()) {
            return back()->withErrors([
                'status' => 'Activate the parent location before activating this location.',
            ]);
        }

        DB::transaction(function () use ($location, $auditService): void {
            $oldStatus = $location->status;
            $location->update(['status' => 'active']);
            $auditService->record($location, 'activated', ['status' => $oldStatus], ['status' => 'active']);
        });

        return to_route('locations.show', $location)->with('status', ucfirst($location->type).' activated.');
    }

    public function moveInventory(
        MoveBinInventoryRequest $request,
        BinInventory $binInventory,
        InventoryService $inventoryService,
    ): RedirectResponse {
        $sourceLocation = $binInventory->location;
        $validated = $request->validated();
        $destination = Location::query()->findOrFail($validated['destination_location_id']);
        $balance = StockBalance::query()
            ->where('location_id', $sourceLocation->id)
            ->whereHas('batch', fn ($query) => $query
                ->where('product_id', $binInventory->product_id)
                ->where('batch_number', $binInventory->batch_number))
            ->first();
        $balance ??= $inventoryService->balanceForLegacyInventory($binInventory, $request->user());
        $inventoryService->putAway($balance, $destination, (string) $binInventory->quantity, $request->user());

        return to_route('locations.show', $sourceLocation)->with('status', 'Product batch moved.');
    }

    /**
     * @param  array<int, array{location: Location, depth: int, path: string, quantity: float}>  $rows
     * @param  array<int, string>  $ancestors
     */
    private function flattenTree(
        Location $location,
        int $depth,
        array $ancestors,
        array &$rows,
        ?string $search,
    ): void {
        $path = [...$ancestors, $location->name];
        $pathLabel = implode(' / ', $path);
        $quantity = $location->inventory->sum(fn (BinInventory $inventory): float => (float) $inventory->quantity);
        $matchesSearch = $search === null
            || mb_stripos($pathLabel.' '.$location->code, $search) !== false;
        $hasMatchingChild = false;
        $children = $location->children;
        $rowStart = count($rows);

        foreach ($children as $child) {
            $beforeCount = count($rows);
            $this->flattenTree($child, $depth + 1, $path, $rows, $search);
            $hasMatchingChild = $hasMatchingChild || count($rows) > $beforeCount;
        }

        if ($matchesSearch || $hasMatchingChild || $search === null) {
            array_splice($rows, $rowStart, 0, [[
                'location' => $location,
                'depth' => $depth,
                'path' => $pathLabel,
                'quantity' => $quantity,
            ]]);
        }
    }
}
