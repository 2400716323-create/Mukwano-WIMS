<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseOrderRequest;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\AuditService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', PurchaseOrder::class);
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:draft,approved,partially_received,received,cancelled'],
        ]);
        $purchaseOrders = PurchaseOrder::query()
            ->with('supplier')
            ->withCount('lines')
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('order_number', 'like', "%{$search}%")
                        ->orWhereHas('supplier', fn ($supplier) => $supplier->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $metrics = [
            'total' => PurchaseOrder::query()->count(),
            'approved' => PurchaseOrder::query()->where('status', PurchaseOrder::STATUS_APPROVED)->count(),
            'draft' => PurchaseOrder::query()->where('status', PurchaseOrder::STATUS_DRAFT)->count(),
            'received' => PurchaseOrder::query()->whereIn('status', [PurchaseOrder::STATUS_PARTIALLY_RECEIVED, PurchaseOrder::STATUS_RECEIVED])->count(),
        ];

        return view('purchase-orders.index', compact('purchaseOrders', 'filters', 'metrics'));
    }

    public function create(): View
    {
        $this->authorize('create', PurchaseOrder::class);

        return view('purchase-orders.form', [
            'purchaseOrder' => new PurchaseOrder(['order_date' => today()]),
            'suppliers' => Supplier::query()->where('status', 'active')->orderBy('name')->get(),
            'products' => Product::query()->where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function store(StorePurchaseOrderRequest $request, AuditService $auditService): RedirectResponse
    {
        $attributes = $request->safe()->except('lines');
        $lines = $request->validated('lines');
        $purchaseOrder = DB::transaction(function () use ($attributes, $lines, $request, $auditService): PurchaseOrder {
            $purchaseOrder = PurchaseOrder::query()->create([
                ...$attributes,
                'created_by' => $request->user()->id,
                'status' => PurchaseOrder::STATUS_DRAFT,
            ]);
            $purchaseOrder->update([
                'order_number' => 'PO-'.now()->format('Y').'-'.str_pad((string) $purchaseOrder->id, 6, '0', STR_PAD_LEFT),
            ]);

            foreach ($lines as $line) {
                $purchaseOrder->lines()->create([
                    'product_id' => $line['product_id'],
                    'ordered_quantity' => $line['ordered_quantity'],
                    'received_quantity' => 0,
                ]);
            }

            $auditService->record($purchaseOrder, 'created', [], [
                'order_number' => $purchaseOrder->order_number,
                'supplier_id' => $purchaseOrder->supplier_id,
                'line_count' => count($lines),
                'status' => $purchaseOrder->status,
            ]);

            return $purchaseOrder;
        });

        return to_route('purchase-orders.show', $purchaseOrder)->with('status', 'Purchase order created as draft.');
    }

    public function show(PurchaseOrder $purchaseOrder): View
    {
        $this->authorize('view', $purchaseOrder);
        $purchaseOrder->load('supplier', 'lines.product', 'creator', 'approver', 'goodsReceipts');

        return view('purchase-orders.show', compact('purchaseOrder'));
    }

    public function edit(PurchaseOrder $purchaseOrder): View
    {
        $this->authorize('update', $purchaseOrder);
        abort_unless($purchaseOrder->status === PurchaseOrder::STATUS_DRAFT, 403);
        $purchaseOrder->load('lines');

        return view('purchase-orders.form', [
            'purchaseOrder' => $purchaseOrder,
            'suppliers' => Supplier::query()->where('status', 'active')->orderBy('name')->get(),
            'products' => Product::query()->where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function update(
        StorePurchaseOrderRequest $request,
        PurchaseOrder $purchaseOrder,
        AuditService $auditService,
    ): RedirectResponse {
        $attributes = $request->safe()->except('lines');
        $lines = $request->validated('lines');

        DB::transaction(function () use ($purchaseOrder, $attributes, $lines, $auditService): void {
            $lockedOrder = PurchaseOrder::query()->lockForUpdate()->findOrFail($purchaseOrder->id);
            abort_unless($lockedOrder->status === PurchaseOrder::STATUS_DRAFT, 409);

            $oldValues = $lockedOrder->only(['supplier_id', 'order_date', 'expected_date', 'notes']);
            $lockedOrder->update($attributes);
            $lockedOrder->lines()->delete();

            foreach ($lines as $line) {
                $lockedOrder->lines()->create([
                    'product_id' => $line['product_id'],
                    'ordered_quantity' => $line['ordered_quantity'],
                    'received_quantity' => 0,
                ]);
            }

            $auditService->record($lockedOrder, 'updated', $oldValues, [
                ...$lockedOrder->only(array_keys($oldValues)),
                'line_count' => count($lines),
            ]);
        });

        return to_route('purchase-orders.show', $purchaseOrder)->with('status', 'Purchase order updated.');
    }

    public function approve(
        PurchaseOrder $purchaseOrder,
        AuditService $auditService,
        Request $request,
    ): RedirectResponse {
        $this->authorize('approve', $purchaseOrder);

        DB::transaction(function () use ($purchaseOrder, $auditService, $request): void {
            $lockedOrder = PurchaseOrder::query()->lockForUpdate()->with('lines')->findOrFail($purchaseOrder->id);
            abort_unless($lockedOrder->status === PurchaseOrder::STATUS_DRAFT, 409);
            abort_if($lockedOrder->lines->isEmpty(), 422, 'A purchase order must contain at least one product.');
            abort_if($lockedOrder->supplier->status !== 'active', 422, 'The supplier is inactive.');

            $lockedOrder->update([
                'status' => PurchaseOrder::STATUS_APPROVED,
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
            ]);
            $auditService->record($lockedOrder, 'approved', ['status' => PurchaseOrder::STATUS_DRAFT], [
                'status' => PurchaseOrder::STATUS_APPROVED,
            ]);
        });

        return to_route('purchase-orders.show', $purchaseOrder)->with('status', 'Purchase order approved.');
    }

    public function cancel(PurchaseOrder $purchaseOrder, AuditService $auditService): RedirectResponse
    {
        $this->authorize('cancel', $purchaseOrder);

        DB::transaction(function () use ($purchaseOrder, $auditService): void {
            $lockedOrder = PurchaseOrder::query()->lockForUpdate()->findOrFail($purchaseOrder->id);
            abort_unless(in_array($lockedOrder->status, [
                PurchaseOrder::STATUS_DRAFT,
                PurchaseOrder::STATUS_APPROVED,
                PurchaseOrder::STATUS_PARTIALLY_RECEIVED,
            ], true), 409);

            $oldStatus = $lockedOrder->status;
            $lockedOrder->update(['status' => PurchaseOrder::STATUS_CANCELLED]);
            $auditService->record($lockedOrder, 'cancelled', ['status' => $oldStatus], [
                'status' => PurchaseOrder::STATUS_CANCELLED,
            ]);
        });

        return to_route('purchase-orders.show', $purchaseOrder)->with('status', 'Purchase order cancelled.');
    }

    public function print(PurchaseOrder $purchaseOrder): View
    {
        $this->authorize('view', $purchaseOrder);
        $purchaseOrder->load('supplier', 'lines.product', 'creator', 'approver');

        return view('purchase-orders.print', compact('purchaseOrder'));
    }
}
