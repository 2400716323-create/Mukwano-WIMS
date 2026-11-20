<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewManualStockAdjustmentRequest;
use App\Http\Requests\StoreManualStockAdjustmentRequest;
use App\Models\Batch;
use App\Models\Location;
use App\Models\ManualStockAdjustment;
use App\Models\Product;
use App\Models\User;
use App\Services\ManualStockAdjustmentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ManualStockAdjustmentController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', ManualStockAdjustment::class);

        $user = $request->user();

        abort_unless($user instanceof User, 401);

        $canApprove = $user->hasPermission(
            'Approve or reject manual stock adjustments'
        );

        $query = ManualStockAdjustment::query()
            ->with(['product', 'batch', 'location', 'requester', 'reviewer'])
            ->latest();

        // Storekeepers see only the requests they created.
        if (! $canApprove) {
            $query->where('requested_by', $user->getKey());
        }

        $adjustments = $query->paginate(20);

        $metricsQuery = fn () => ManualStockAdjustment::query()
            ->when(
                ! $canApprove,
                fn ($q) => $q->where('requested_by', $user->getKey())
            );

        $metrics = [
            'total' => (clone $query)->reorder()->count(),
            'pending' => $metricsQuery()->where('status', 'pending')->count(),
            'approved' => $metricsQuery()->where('status', 'approved')->count(),
            'rejected' => $metricsQuery()->where('status', 'rejected')->count(),
        ];

        return view('manual-stock-adjustments.index', compact('adjustments', 'metrics'));
    }

    public function create(): View
    {
        $this->authorize('create', ManualStockAdjustment::class);

        $locations = Location::query()
            ->where('type', 'bin')
            ->where('status', 'active')
            ->orderBy('code')
            ->get()
            ->filter(fn (Location $location) => $location->isOperational())
            ->values();

        return view('manual-stock-adjustments.create', [
            'locations' => $locations,
            'products' => Product::query()
                ->where('status', 'active')
                ->orderBy('name')
                ->get(),
            'batches' => Batch::query()
                ->with('product')
                ->orderBy('batch_number')
                ->get(),
        ]);
    }

    public function store(
        StoreManualStockAdjustmentRequest $request,
        ManualStockAdjustmentService $service
    ): RedirectResponse {
        $this->authorize('create', ManualStockAdjustment::class);

        $user = $request->user();

        abort_unless($user instanceof User, 401);

        $adjustment = $service->create(
            $request->validated(),
            $user
        );

        return to_route('manual-stock-adjustments.show', $adjustment)
            ->with('status', 'Adjustment request submitted. Stock has not been changed.');
    }

    public function show(ManualStockAdjustment $manualStockAdjustment): View
    {
        $this->authorize('view', $manualStockAdjustment);

        $manualStockAdjustment->load([
            'product',
            'batch',
            'location',
            'requester',
            'reviewer',
        ]);

        return view('manual-stock-adjustments.show', [
            'adjustment' => $manualStockAdjustment,
            'canReview' => request()->user()->can('review', $manualStockAdjustment),
        ]);
    }

    public function review(
        ReviewManualStockAdjustmentRequest $request,
        ManualStockAdjustment $manualStockAdjustment,
        ManualStockAdjustmentService $service
    ): RedirectResponse {
        $this->authorize('review', $manualStockAdjustment);

        $user = $request->user();

        abort_unless($user instanceof User, 401);

        $attributes = $request->validated();

        $service->review(
            $manualStockAdjustment,
            $attributes['decision'],
            $attributes['reason'] ?? null,
            $user
        );

        return to_route('manual-stock-adjustments.show', $manualStockAdjustment)
            ->with(
                'status',
                $attributes['decision'] === 'approved'
                    ? 'Adjustment approved. Stock balances have been updated.'
                    : 'Adjustment rejected. Stock has not been changed.'
            );
    }
}
