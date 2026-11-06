<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateStockCountRequest;
use App\Http\Requests\ReviewStockCountRequest;
use App\Http\Requests\SubmitStockCountRequest;
use App\Http\Requests\UpdateInventorySettingsRequest;
use App\Models\Batch;
use App\Models\InventorySetting;
use App\Models\Location;
use App\Models\Product;
use App\Models\StockCount;
use App\Services\AuditService;
use App\Services\StockCountService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class StockCountController extends Controller
{
    public function index(StockCountService $stockCountService): View
    {
        $this->authorize('viewAny', StockCount::class);
        $stockCounts = StockCount::query()
            ->with('location', 'product', 'batch', 'creator', 'reviewer', 'lines')
            ->withCount('lines')
            ->latest()
            ->paginate(20);
        $inventorySetting = InventorySetting::current();
        $accuracyByCount = $stockCounts->getCollection()
            ->mapWithKeys(fn (StockCount $stockCount): array => [
                $stockCount->id => $stockCountService->accuracyUsingLoadedLines($stockCount->lines, $inventorySetting),
            ]);

        $metrics = [
            'total' => StockCount::query()->count(),
            'pending_review' => StockCount::query()->where('status', 'submitted')->count(),
            'approved' => StockCount::query()->where('status', 'approved')->count(),
            'in_progress' => StockCount::query()->where('status', 'draft')->count(),
        ];

        return view('stock-counts.index', compact('stockCounts', 'accuracyByCount', 'metrics'));
    }

    public function create(): View
    {
        $this->authorize('create', StockCount::class);

        return view('stock-counts.create', [
            'locations' => Location::query()
                ->where('type', 'bin')
                ->where('status', 'active')
                ->orderBy('code')
                ->get()
                ->filter(fn (Location $location): bool => $location->isOperational())
                ->values(),
            'products' => Product::query()->where('status', 'active')->orderBy('name')->get(),
            'batches' => Batch::query()->with('product')->orderBy('batch_number')->get(),
        ]);
    }

    public function store(CreateStockCountRequest $request, StockCountService $stockCountService): RedirectResponse
    {
        $attributes = $request->validated();
        $stockCount = $stockCountService->create(
            Location::query()->findOrFail($attributes['location_id']),
            isset($attributes['product_id']) ? (int) $attributes['product_id'] : null,
            isset($attributes['batch_id']) ? (int) $attributes['batch_id'] : null,
            (bool) ($attributes['blind_count'] ?? false),
            $request->user(),
        );

        return to_route('stock-counts.show', $stockCount)
            ->with('status', 'Count list created. Stock will not change until a supervisor approves it.');
    }

    public function show(StockCount $stockCount, StockCountService $stockCountService): View
    {
        $this->authorize('view', $stockCount);
        $stockCount->load(
            'location',
            'product',
            'batch.product',
            'creator',
            'submitter',
            'reviewer',
            'lines.batch.product',
            'lines.location',
            'lines.counter',
            'reviews.supervisor',
        );
        $accuracy = $stockCountService->accuracyUsingLoadedLines(
            $stockCount->lines,
            InventorySetting::current(),
        );

        return view('stock-counts.show', [
            'stockCount' => $stockCount,
            'accuracy' => $accuracy,
            'canSubmit' => request()->user()->can('submit', $stockCount),
            'canReview' => request()->user()->can('review', $stockCount),
            'inventorySetting' => InventorySetting::current(),
        ]);
    }

    public function submit(
        SubmitStockCountRequest $request,
        StockCount $stockCount,
        StockCountService $stockCountService,
    ): RedirectResponse {
        $stockCountService->submit($stockCount, $request->validated()['lines'], $request->user());

        return to_route('stock-counts.show', $stockCount)
            ->with('status', 'Count submitted for supervisor approval. Stock has not been changed.');
    }

    public function review(
        ReviewStockCountRequest $request,
        StockCount $stockCount,
        StockCountService $stockCountService,
    ): RedirectResponse {
        $attributes = $request->validated();
        $stockCountService->review(
            $stockCount,
            $attributes['decision'],
            $attributes['reason'] ?? null,
            $request->user(),
        );

        return to_route('stock-counts.show', $stockCount)
            ->with('status', $attributes['decision'] === 'approved'
                ? 'Count approved and stock adjustments posted.'
                : 'Count rejected. No stock was changed.');
    }

    public function settings(): View
    {
        abort_unless(request()->user()->isSupervisor(), 403);

        return view('stock-counts.settings', [
            'inventorySetting' => InventorySetting::current(),
        ]);
    }

    public function updateSettings(
        UpdateInventorySettingsRequest $request,
        AuditService $auditService,
    ): RedirectResponse {
        $inventorySetting = InventorySetting::current();
        $oldValues = $inventorySetting->only([
            'variance_threshold_percent',
            'accuracy_tolerance_percent',
        ]);
        $inventorySetting->fill($request->validated());
        $inventorySetting->updated_by = $request->user()->id;
        $inventorySetting->save();
        $auditService->record($inventorySetting, 'updated', $oldValues, [
            'variance_threshold_percent' => $inventorySetting->variance_threshold_percent,
            'accuracy_tolerance_percent' => $inventorySetting->accuracy_tolerance_percent,
        ]);

        return to_route('stock-counts.settings')->with('status', 'Inventory accuracy settings updated.');
    }
}
