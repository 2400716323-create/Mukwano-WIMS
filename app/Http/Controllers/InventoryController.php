<?php

namespace App\Http\Controllers;

use App\Http\Requests\PutAwayStockRequest;
use App\Http\Requests\TransferStockRequest;
use App\Models\Batch;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Services\InventoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Location::class);
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'batch_id' => ['nullable', 'integer', 'exists:batches,id'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
        ]);
        $balances = StockBalance::query()
            ->where('quantity', '>', 0)
            ->with('batch.product', 'location.parent.parent.parent')
            ->when($filters['product_id'] ?? null, fn ($query, int $id) => $query->whereHas('batch', fn ($batch) => $batch->where('product_id', $id)))
            ->when($filters['category_id'] ?? null, fn ($query, int $id) => $query->whereHas('batch.product', fn ($product) => $product->where('category_id', $id)))
            ->when($filters['batch_id'] ?? null, fn ($query, int $id) => $query->where('batch_id', $id))
            ->when($filters['location_id'] ?? null, fn ($query, int $id) => $query->where('location_id', $id))
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->whereHas('batch', function ($batch) use ($search): void {
                        $batch->where('batch_number', 'like', "%{$search}%")
                            ->orWhereHas('product', function ($product) use ($search): void {
                                $product->where('sku', 'like', "%{$search}%")
                                    ->orWhere('name', 'like', "%{$search}%");
                            });
                    })->orWhereHas('location', fn ($location) => $location->where('code', 'like', "%{$search}%"));
                });
            })
            ->orderBy('location_id')
            ->orderBy('batch_id')
            ->paginate(20)
            ->withQueryString();
        $movements = StockMovement::query()
            ->with('batch.product', 'fromLocation', 'toLocation', 'performer', 'approver', 'receiptLine.goodsReceipt')
            ->latest('moved_at')
            ->limit(20)
            ->get();
        $destinationBins = Location::query()
            ->where('type', 'bin')
            ->where('status', 'active')
            ->orderBy('code')
            ->get()
            ->filter(fn (Location $location): bool => $location->isOperational())
            ->values();
        $productTotals = DB::table('stock_balances')
            ->join('batches', 'stock_balances.batch_id', '=', 'batches.id')
            ->join('products', 'batches.product_id', '=', 'products.id')
            ->when($filters['product_id'] ?? null, fn ($query, int $id) => $query->where('products.id', $id))
            ->when($filters['category_id'] ?? null, fn ($query, int $id) => $query->where('products.category_id', $id))
            ->when($filters['batch_id'] ?? null, fn ($query, int $id) => $query->where('batches.id', $id))
            ->when($filters['location_id'] ?? null, fn ($query, int $id) => $query->where('stock_balances.location_id', $id))
            ->select('products.id', 'products.sku', 'products.name', 'products.unit')
            ->selectRaw('SUM(stock_balances.quantity) as quantity')
            ->where('stock_balances.quantity', '>', 0)
            ->groupBy('products.id', 'products.sku', 'products.name', 'products.unit')
            ->orderBy('products.name')
            ->get();

        $metrics = [
            'total_units' => StockBalance::query()->where('quantity', '>', 0)->sum('quantity'),
            'active_batches' => StockBalance::query()->where('quantity', '>', 0)->distinct('batch_id')->count('batch_id'),
            'occupied_bins' => StockBalance::query()->where('quantity', '>', 0)->distinct('location_id')->count('location_id'),
            'total_skus' => DB::table('stock_balances')->join('batches', 'stock_balances.batch_id', '=', 'batches.id')->where('stock_balances.quantity', '>', 0)->distinct('batches.product_id')->count('batches.product_id'),
        ];

        return view('inventory.index', [
            'balances' => $balances,
            'movements' => $movements,
            'destinationBins' => $destinationBins,
            'productTotals' => $productTotals,
            'filters' => $filters,
            'metrics' => $metrics,
            'products' => Product::query()->orderBy('name')->get(),
            'categories' => Category::query()->orderBy('name')->get(),
            'batches' => Batch::query()->with('product')->orderBy('batch_number')->get(),
            'locations' => Location::query()->where('type', 'bin')->orderBy('code')->get(),
        ]);
    }

    public function enquiry(Request $request): View
    {
        $this->authorize('viewAny', Location::class);
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'batch_id' => ['nullable', 'integer', 'exists:batches,id'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
        ]);
        $hasFilter = ($filters['search'] ?? '') !== ''
            || isset($filters['product_id'])
            || isset($filters['batch_id'])
            || isset($filters['location_id']);
        $balances = StockBalance::query()
            ->with('batch.product', 'location')
            ->when($hasFilter, function ($query) use ($filters): void {
                if (! empty($filters['product_id'])) {
                    $query->whereHas('batch', fn ($batch) => $batch->where('product_id', $filters['product_id']));
                }
                if (! empty($filters['batch_id'])) {
                    $query->where('batch_id', $filters['batch_id']);
                }
                if (! empty($filters['location_id'])) {
                    $query->where('location_id', $filters['location_id']);
                }
                if (! empty($filters['search'])) {
                    $search = $filters['search'];
                    $query->where(function ($query) use ($search): void {
                        $query->whereHas('batch', function ($batch) use ($search): void {
                            $batch->where('batch_number', 'like', "%{$search}%")
                                ->orWhereHas('product', fn ($product) => $product
                                    ->where('sku', 'like', "%{$search}%")
                                    ->orWhere('name', 'like', "%{$search}%"));
                        })->orWhereHas('location', fn ($location) => $location
                            ->where('code', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%"));
                    });
                }
            })
            ->orderBy('location_id')
            ->orderBy('batch_id')
            ->paginate(20)
            ->withQueryString();
        $movements = StockMovement::query()
            ->with('batch.product', 'fromLocation', 'toLocation', 'performer', 'approver', 'receiptLine.goodsReceipt')
            ->when($hasFilter, function ($query) use ($filters): void {
                if (! empty($filters['product_id'])) {
                    $query->whereHas('batch', fn ($batch) => $batch->where('product_id', $filters['product_id']));
                }
                if (! empty($filters['batch_id'])) {
                    $query->where('batch_id', $filters['batch_id']);
                }
                if (! empty($filters['location_id'])) {
                    $locationId = $filters['location_id'];
                    $query->where(fn ($query) => $query
                        ->where('from_location_id', $locationId)
                        ->orWhere('to_location_id', $locationId));
                }
                if (! empty($filters['search'])) {
                    $search = $filters['search'];
                    $query->where(function ($query) use ($search): void {
                        $query->whereHas('batch', function ($batch) use ($search): void {
                            $batch->where('batch_number', 'like', "%{$search}%")
                                ->orWhereHas('product', fn ($product) => $product
                                    ->where('sku', 'like', "%{$search}%")
                                    ->orWhere('name', 'like', "%{$search}%"));
                        })->orWhereHas('fromLocation', fn ($location) => $location
                            ->where('code', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%"))
                            ->orWhereHas('toLocation', fn ($location) => $location
                                ->where('code', 'like', "%{$search}%")
                                ->orWhere('name', 'like', "%{$search}%"));
                    });
                }
            })
            ->latest('moved_at')
            ->paginate(30, ['*'], 'movement_page')
            ->withQueryString();

        return view('inventory.enquiry', [
            'balances' => $hasFilter ? $balances : collect(),
            'movements' => $hasFilter ? $movements : collect(),
            'filters' => $filters,
            'products' => Product::query()->orderBy('name')->get(),
            'batches' => Batch::query()->with('product')->orderBy('batch_number')->get(),
            'locations' => Location::query()->where('type', 'bin')->orderBy('code')->get(),
            'hasFilter' => $hasFilter,
        ]);
    }

    public function ledger(Request $request): View
    {
        $this->authorize('viewAny', Location::class);
        $filters = $request->validate([
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'batch_id' => ['nullable', 'integer', 'exists:batches,id'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'movement_type' => ['nullable', 'in:opening_balance,receipt,put_away,transfer,dispatch,adjustment'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);
        $movements = StockMovement::query()
            ->with('batch.product', 'fromLocation', 'toLocation', 'performer', 'approver', 'receiptLine.goodsReceipt')
            ->when($filters['product_id'] ?? null, fn ($query, int $productId) => $query
                ->whereHas('batch', fn ($batch) => $batch->where('product_id', $productId)))
            ->when($filters['batch_id'] ?? null, fn ($query, int $batchId) => $query->where('batch_id', $batchId))
            ->when($filters['location_id'] ?? null, function ($query, int $locationId): void {
                $query->where(fn ($query) => $query
                    ->where('from_location_id', $locationId)
                    ->orWhere('to_location_id', $locationId));
            })
            ->when($filters['movement_type'] ?? null, fn ($query, string $type) => $query->where('movement_type', $type))
            ->when($filters['from'] ?? null, fn ($query, string $date) => $query->whereDate('moved_at', '>=', $date))
            ->when($filters['to'] ?? null, fn ($query, string $date) => $query->whereDate('moved_at', '<=', $date))
            ->latest('moved_at')
            ->paginate(30)
            ->withQueryString();

        return view('inventory.ledger', [
            'movements' => $movements,
            'filters' => $filters,
            'products' => Product::query()->orderBy('name')->get(),
            'batches' => Batch::query()->with('product')->orderBy('batch_number')->get(),
            'locations' => Location::query()->where('type', 'bin')->orderBy('code')->get(),
        ]);
    }

    public function transfers(): View
    {
        $this->authorize('viewAny', Location::class);
        $balances = StockBalance::query()
            ->where('quantity', '>', 0)
            ->whereHas('location', function ($location): void {
                $location->where('type', 'bin')->where('status', 'active');
            })
            ->with('batch.product', 'location')
            ->orderBy('location_id')
            ->orderBy('batch_id')
            ->get()
            ->filter(fn (StockBalance $balance): bool => $balance->location->isOperational())
            ->values();

        return view('inventory.transfers', [
            'balances' => $balances,
            'locations' => Location::query()->where('type', 'bin')->where('status', 'active')->orderBy('code')->get()
                ->filter(fn (Location $location): bool => $location->isOperational())
                ->values(),
        ]);
    }

    public function transfer(
        TransferStockRequest $request,
        StockBalance $stockBalance,
        InventoryService $inventoryService,
    ): RedirectResponse {
        $attributes = $request->validated();
        $destination = Location::query()->findOrFail($attributes['destination_location_id']);
        $inventoryService->transfer(
            $stockBalance,
            $destination,
            (string) $attributes['quantity'],
            $attributes['reason'],
            $request->user(),
            Carbon::parse($attributes['transferred_at']),
            $attributes['idempotency_key'],
        );

        return to_route('stock-transfers.create')->with('status', 'Stock transfer completed atomically.');
    }

    public function putAway(
        PutAwayStockRequest $request,
        StockBalance $stockBalance,
        InventoryService $inventoryService,
    ): RedirectResponse {
        $attributes = $request->validated();
        $destination = Location::query()->findOrFail($attributes['destination_location_id']);
        $inventoryService->putAway(
            $stockBalance,
            $destination,
            (string) $attributes['quantity'],
            $request->user(),
            $attributes['idempotency_key'],
        );

        return to_route('inventory.index')->with('status', 'Stock put away into the destination bin.');
    }
}
