<?php

namespace App\Http\Controllers;

use App\Exceptions\DuplicateGoodsReceiptException;
use App\Http\Requests\StoreGoodsReceiptRequest;
use App\Models\GoodsReceipt;
use App\Models\Location;
use App\Models\OfflineSync;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\InventoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GoodsReceiptController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', GoodsReceipt::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $goodsReceipts = GoodsReceipt::query()
            ->with('supplier', 'purchaseOrder', 'receiver')
            ->withCount('lines')
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('receipt_number', 'like', "%{$search}%")
                        ->orWhereHas('supplier', fn ($supplier) => $supplier->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('purchaseOrder', fn ($order) => $order->where('order_number', 'like', "%{$search}%"))
                        ->orWhereHas('receiver', fn ($receiver) => $receiver->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('lines', function ($line) use ($search): void {
                            $line->whereHas('batch', fn ($batch) => $batch->where('batch_number', 'like', "%{$search}%"))
                                ->orWhereHas('product', fn ($product) => $product
                                    ->where('sku', 'like', "%{$search}%")
                                    ->orWhere('name', 'like', "%{$search}%"));
                        });
                });
            })
            ->orderByDesc('received_at')
            ->paginate(15)
            ->withQueryString();

        $metrics = [
            'total' => GoodsReceipt::query()->count(),
            'today' => GoodsReceipt::query()->whereDate('received_at', today())->count(),
            'this_month' => GoodsReceipt::query()->whereMonth('received_at', now()->month)->count(),
            'suppliers_count' => GoodsReceipt::query()->distinct('supplier_id')->count('supplier_id'),
        ];

        return view('goods-receipts.index', compact('goodsReceipts', 'filters', 'metrics'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', GoodsReceipt::class);

        $data = $request->validate([
            'purchase_order_id' => ['nullable', 'integer', 'exists:purchase_orders,id'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
        ]);

        $purchaseOrders = PurchaseOrder::query()
            ->whereIn('status', [
                PurchaseOrder::STATUS_APPROVED,
                PurchaseOrder::STATUS_PARTIALLY_RECEIVED,
            ])
            ->with('supplier', 'lines.product')
            ->orderByDesc('id')
            ->get();

        $selectedPurchaseOrder = isset($data['purchase_order_id'])
            ? $purchaseOrders->firstWhere('id', $data['purchase_order_id'])
            : null;

        if (isset($data['purchase_order_id']) && $selectedPurchaseOrder === null) {
            abort(404);
        }

        $receivingLocations = $this->operationalReceivingLocations();

        $selectedLocationId = isset($data['location_id'])
            ? $receivingLocations->firstWhere('id', $data['location_id'])?->id
            : null;

        if (isset($data['location_id']) && $selectedLocationId === null) {
            abort(404);
        }

        return view('goods-receipts.form', [
            'purchaseOrders' => $purchaseOrders,
            'selectedPurchaseOrder' => $selectedPurchaseOrder,
            'selectedLocationId' => $selectedLocationId,
            'suppliers' => Supplier::query()
                ->where('status', 'active')
                ->orderBy('name')
                ->get(),
            'products' => Product::query()
                ->where('status', 'active')
                ->orderBy('name')
                ->get(),
            'receivingLocations' => $receivingLocations,
        ]);
    }

    public function review(StoreGoodsReceiptRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $token = $validated['client_id'] ?? (string) Str::uuid();

        unset($validated['client_id']);

        $sessionKey = 'goods_receipt_reviews.'.$token;

        $existingReview = session()->get($sessionKey);

        if (is_array($existingReview)) {
            abort_unless(
                $existingReview === $validated,
                409,
                'This receipt review ID was already used with different data.'
            );
        } else {
            session()->put($sessionKey, $validated);
        }

        return to_route('goods-receipts.review.show', $token);
    }

    /**
     * Display a saved receipt review without posting stock.
     */
    public function showReview(
        string $token,
        Request $request
    ): View|RedirectResponse {
        // A review must belong to an authenticated user's session/account.
        abort_unless($request->user(), 403);

        abort_unless(Str::isUuid($token), 404);

        $receiptData = session()->get('goods_receipt_reviews.'.$token);

        if (! is_array($receiptData)) {
            // Only retrieve an offline review belonging to the signed-in user.
            $offlineSync = OfflineSync::query()
                ->whereKey($token)
                ->where('user_id', $request->user()->id)
                ->where('operation', 'goods_receipt_review')
                ->whereNotNull('completed_at')
                ->first();

            if ($offlineSync && is_array($offlineSync->payload)) {
                $receiptData = $offlineSync->payload;

                session()->put('goods_receipt_reviews.'.$token, $receiptData);
            } elseif ($offlineSync && $offlineSync->payload === null) {
                $resultUrl = $offlineSync->result_url;

                // Never redirect back to the same review page.
                if (
                    $resultUrl
                    && ! str_contains($resultUrl, '/goods-receipts/review/')
                ) {
                    return redirect()->to($resultUrl);
                }

                return to_route('goods-receipts.index')
                    ->with('status', 'This offline receipt was already processed. Check the goods receipts list.');
            }
        }

        abort_unless(is_array($receiptData), 404);

        $purchaseOrder = isset($receiptData['purchase_order_id'])
            ? PurchaseOrder::query()
                ->with('lines')
                ->findOrFail($receiptData['purchase_order_id'])
            : null;

        $receiptLines = collect($receiptData['lines']);

        $products = Product::query()
            ->whereKey($receiptLines->pluck('product_id')->unique())
            ->get()
            ->keyBy('id');

        $locations = Location::query()
            ->whereKey($receiptLines->pluck('location_id')->unique())
            ->get()
            ->keyBy('id');

        $lines = $receiptLines->map(function (array $line) use ($purchaseOrder, $products, $locations): array {
            $product = $products->get($line['product_id']) ?? abort(404);

            $location = $locations->get($line['location_id']) ?? abort(404);

            $purchaseLine = $purchaseOrder?->lines->firstWhere('product_id', $product->id);

            $outstanding = $purchaseLine === null
                ? null
                : number_format(max(
                    0,
                    (float) $purchaseLine->ordered_quantity - (float) $purchaseLine->received_quantity,
                ), 3, '.', '');

            return [
                ...$line,
                'product' => $product,
                'location' => $location,
                'outstanding' => $outstanding,
                'exceeds_outstanding' => $outstanding !== null
                    && (float) $line['quantity'] > (float) $outstanding,
                'partial' => $outstanding !== null
                    && (float) $line['quantity'] < (float) $outstanding,
            ];
        });

        return view('goods-receipts.review', [
            'token' => $token,
            'receiptData' => $receiptData,
            'purchaseOrder' => $purchaseOrder,
            'supplier' => $purchaseOrder?->supplier
                ?? Supplier::query()->findOrFail($receiptData['supplier_id']),
            'lines' => $lines,
        ]);
    }

    /**
     * Confirming a receipt is a separate stock-changing operation.
     * Keep its existing authorization protection.
     */
    public function confirm(
        string $token,
        Request $request,
        InventoryService $inventoryService,
    ): RedirectResponse {
        $this->authorize('create', GoodsReceipt::class);

        abort_unless(Str::isUuid($token), 404);

        $receiptData = session()->get('goods_receipt_reviews.'.$token);

        if (! is_array($receiptData)) {
            $offlineSync = OfflineSync::query()
                ->whereKey($token)
                ->where('user_id', $request->user()->id)
                ->where('operation', 'goods_receipt_review')
                ->whereNotNull('completed_at')
                ->first();

            $receiptData = $offlineSync?->payload;

            if (is_array($receiptData)) {
                session()->put('goods_receipt_reviews.'.$token, $receiptData);
            }
        }

        abort_unless(
            is_array($receiptData),
            409,
            'This review has expired or has already been confirmed.'
        );

        try {
            $receipt = $inventoryService->receive(
                $receiptData,
                $request->user(),
                $token
            );
        } catch (DuplicateGoodsReceiptException $exception) {
            abort(409, $exception->getMessage());
        }

        OfflineSync::query()
            ->whereKey($token)
            ->where('user_id', $request->user()->id)
            ->where('operation', 'goods_receipt_review')
            ->update([
                'payload' => null,
                'result_url' => route('goods-receipts.show', $receipt),
            ]);

        session()->forget('goods_receipt_reviews.'.$token);

        return to_route('goods-receipts.show', $receipt)
            ->with('status', 'Goods receipt confirmed and stock posted.');
    }

    public function show(GoodsReceipt $goodsReceipt): View
    {
        $this->authorize('view', $goodsReceipt);

        $goodsReceipt->load(
            'supplier',
            'purchaseOrder',
            'receiver',
            'lines.product',
            'lines.batch',
            'lines.location',
        );

        return view('goods-receipts.show', compact('goodsReceipt'));
    }

    public function print(GoodsReceipt $goodsReceipt): View
    {
        $this->authorize('view', $goodsReceipt);

        $goodsReceipt->load(
            'supplier',
            'purchaseOrder',
            'receiver',
            'lines.product',
            'lines.batch',
            'lines.location',
        );

        return view('goods-receipts.print', compact('goodsReceipt'));
    }

    /**
     * Get active Receiving Areas directly under operational warehouses.
     *
     * @return Collection<int, Location>
     */
    private function operationalReceivingLocations(): Collection
    {
        return Location::query()
            ->with('parent')
            ->where('type', 'receiving')
            ->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->filter(fn (Location $location): bool =>
                $location->parent?->type === 'warehouse'
                && $location->isOperational()
            )
            ->values();
    }
}
