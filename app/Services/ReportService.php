<?php

namespace App\Services;

use App\Models\InventorySetting;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ReportService
{
    /**
     * @return array<string, string>
     */
    public function availableReports(): array
    {
        return [
            'stock-on-hand' => 'Stock on hand by product, batch, and location',
            'goods-received' => 'Goods received',
            'goods-dispatched' => 'Goods dispatched',
            'movement' => 'Stock movement',
            'transfers' => 'Stock transfers',
            'expiry-ageing' => 'Expiry ageing',
            'low-stock' => 'Low stock',
            'cycle-count' => 'Cycle count',
            'ira' => 'Inventory reconciliation analysis (IRA)',
            'purchase-orders' => 'Purchase orders',
            'audit-log' => 'Audit log',
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{
     *     key: string,
     *     title: string,
     *     columns: array<string, string>,
     *     rows: list<array<string, mixed>>,
     *     totals: array<string, int|float|string>
     * }
     */
    public function report(string $key, array $filters): array
    {
        $title = $this->availableReports()[$key] ?? throw new InvalidArgumentException('Unknown report.');
        $rows = match ($key) {
            'stock-on-hand' => $this->stockOnHand($filters),
            'goods-received' => $this->goodsReceived($filters),
            'goods-dispatched' => $this->goodsDispatched($filters),
            'movement' => $this->movements($filters),
            'transfers' => $this->movements($filters, 'transfer'),
            'expiry-ageing' => $this->expiryAgeing($filters),
            'low-stock' => $this->lowStock($filters),
            'cycle-count' => $this->cycleCounts($filters),
            'ira' => $this->inventoryReconciliation($filters),
            'purchase-orders' => $this->purchaseOrders($filters),
            'audit-log' => $this->auditLog($filters),
        };
        $rows = $this->formatQuantities($rows);

        return [
            'key' => $key,
            'title' => $title,
            'columns' => $this->columns($key),
            'rows' => $rows,
            'totals' => $this->totals($key, $rows),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
     */
    private function stockOnHand(array $filters): array
    {
        $query = DB::table('stock_balances')
            ->join('batches', 'stock_balances.batch_id', '=', 'batches.id')
            ->join('products', 'batches.product_id', '=', 'products.id')
            ->join('locations', 'stock_balances.location_id', '=', 'locations.id')
            ->where('stock_balances.quantity', '>', 0)
            ->select([
                'products.sap_material_number',
                'products.sku',
                'products.name as product_name',
                'products.unit',
                'batches.batch_number',
                'locations.code as location_code',
                'locations.name as location_name',
                'stock_balances.quantity',
            ]);
        $this->applyProductFilters($query, $filters, 'stock_balances.location_id');

        return $query->orderBy('products.name')->orderBy('batches.batch_number')->orderBy('locations.code')
            ->get()->map(fn (object $row): array => (array) $row)->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
     */
    private function goodsReceived(array $filters): array
    {
        $query = DB::table('goods_receipt_lines as lines')
            ->join('goods_receipts as receipts', 'lines.goods_receipt_id', '=', 'receipts.id')
            ->join('products', 'lines.product_id', '=', 'products.id')
            ->join('batches', 'lines.batch_id', '=', 'batches.id')
            ->join('locations', 'lines.location_id', '=', 'locations.id')
            ->where('receipts.status', 'confirmed')
            ->select([
                'receipts.receipt_number',
                'receipts.received_at',
                'products.sap_material_number',
                'products.sku',
                'products.name as product_name',
                'batches.batch_number',
                'locations.code as location_code',
                'lines.quantity',
            ]);
        $this->applyProductFilters($query, $filters, 'lines.location_id');
        $this->applyDateFilters($query, $filters, 'receipts.received_at');

        return $query->orderByDesc('receipts.received_at')->orderBy('receipts.receipt_number')
            ->get()->map(fn (object $row): array => (array) $row)->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
     */
    private function goodsDispatched(array $filters): array
    {
        $query = DB::table('dispatch_lines as lines')
            ->join('dispatch_requests as requests', 'lines.dispatch_request_id', '=', 'requests.id')
            ->join('products', 'requests.product_id', '=', 'products.id')
            ->join('batches', 'lines.batch_id', '=', 'batches.id')
            ->join('locations', 'lines.location_id', '=', 'locations.id')
            ->where('requests.status', 'dispatched')
            ->select([
                'requests.gdn_number',
                'requests.dispatched_at',
                'products.sap_material_number',
                'products.sku',
                'products.name as product_name',
                'batches.batch_number',
                'locations.code as location_code',
                'lines.quantity',
            ]);
        $this->applyProductFilters($query, $filters, 'lines.location_id');
        $this->applyDateFilters($query, $filters, 'requests.dispatched_at');

        return $query->orderByDesc('requests.dispatched_at')->orderBy('requests.gdn_number')
            ->get()->map(fn (object $row): array => (array) $row)->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
     */
    private function movements(array $filters, ?string $type = null): array
    {
        $query = DB::table('stock_movements as movements')
            ->join('batches', 'movements.batch_id', '=', 'batches.id')
            ->join('products', 'batches.product_id', '=', 'products.id')
            ->leftJoin('locations as source', 'movements.from_location_id', '=', 'source.id')
            ->leftJoin('locations as destination', 'movements.to_location_id', '=', 'destination.id')
            ->leftJoin('users', 'movements.performed_by', '=', 'users.id')
            ->select([
                'movements.moved_at',
                'movements.movement_type',
                'movements.reason',
                'movements.reference_type',
                'movements.reference_id',
                'products.sap_material_number',
                'products.sku',
                'products.name as product_name',
                'batches.batch_number',
                'source.code as from_location',
                'destination.code as to_location',
                'users.name as performed_by',
                'movements.quantity',
            ]);
        $this->applyProductFilters($query, $filters);
        if ($type !== null) {
            $query->where('movements.movement_type', $type);
        }
        if (isset($filters['location_id'])) {
            $locationId = $filters['location_id'];
            $query->where(fn (Builder $builder) => $builder
                ->where('movements.from_location_id', $locationId)
                ->orWhere('movements.to_location_id', $locationId));
        }
        $this->applyDateFilters($query, $filters, 'movements.moved_at');

        return $query->orderByDesc('movements.moved_at')->orderByDesc('movements.id')
            ->get()->map(fn (object $row): array => (array) $row)->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
     */
    private function expiryAgeing(array $filters): array
    {
        $query = DB::table('stock_balances')
            ->join('batches', 'stock_balances.batch_id', '=', 'batches.id')
            ->join('products', 'batches.product_id', '=', 'products.id')
            ->join('locations', 'stock_balances.location_id', '=', 'locations.id')
            ->where('stock_balances.quantity', '>', 0)
            ->whereNotNull('batches.expires_at')
            ->select([
                'products.sap_material_number',
                'products.sku',
                'products.name as product_name',
                'products.unit',
                'batches.id as batch_id',
                'batches.batch_number',
                'batches.expires_at',
                'locations.code as location_code',
                'stock_balances.quantity',
            ]);
        $this->applyProductFilters($query, $filters, 'stock_balances.location_id');
        $this->applyDateFilters($query, $filters, 'batches.expires_at');
        $windows = InventorySetting::query()->first()?->expiryWindows() ?? [
            'soon' => 30,
            'warning' => 60,
            'notice' => 90,
        ];

        return $query->orderBy('batches.expires_at')->orderBy('products.name')->get()
            ->map(function (object $row) use ($windows): array {
                $daysUntilExpiry = today()->diffInDays(Carbon::parse($row->expires_at), false);
                $bucket = match (true) {
                    $daysUntilExpiry < 0 => 'Expired',
                    $daysUntilExpiry <= $windows['soon'] => "0-{$windows['soon']} days",
                    $daysUntilExpiry <= $windows['warning'] => ($windows['soon'] + 1)."-{$windows['warning']} days",
                    $daysUntilExpiry <= $windows['notice'] => ($windows['warning'] + 1)."-{$windows['notice']} days",
                    default => "Over {$windows['notice']} days",
                };

                return [
                    ...((array) $row),
                    'days_until_expiry' => $daysUntilExpiry,
                    'ageing_bucket' => $bucket,
                ];
            })->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
     */
    private function lowStock(array $filters): array
    {
        $balances = DB::table('stock_balances')
            ->join('batches', 'stock_balances.batch_id', '=', 'batches.id')
            ->when(isset($filters['location_id']), fn (Builder $query) => $query
                ->where('stock_balances.location_id', $filters['location_id']))
            ->when(isset($filters['batch_id']), fn (Builder $query) => $query
                ->where('batches.id', $filters['batch_id']))
            ->select('batches.product_id')
            ->selectRaw('SUM(stock_balances.quantity) as on_hand')
            ->groupBy('batches.product_id');
        $query = DB::table('products')
            ->leftJoinSub($balances, 'balances', 'products.id', '=', 'balances.product_id')
            ->where('products.status', 'active')
            ->where('products.reorder_level', '>', 0)
            ->whereRaw('COALESCE(balances.on_hand, 0) < products.reorder_level')
            ->select([
                'products.sap_material_number',
                'products.sku',
                'products.name as product_name',
                'products.unit',
                'products.reorder_level',
                'products.max_level',
            ])
            ->selectRaw('COALESCE(balances.on_hand, 0) as on_hand');
        if (isset($filters['product_id'])) {
            $query->where('products.id', $filters['product_id']);
        }

        return $query->orderBy('products.name')->get()->map(fn (object $row): array => (array) $row)->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
     */
    private function cycleCounts(array $filters): array
    {
        $query = DB::table('stock_count_lines as lines')
            ->join('stock_counts as counts', 'lines.stock_count_id', '=', 'counts.id')
            ->join('batches', 'lines.batch_id', '=', 'batches.id')
            ->join('products', 'batches.product_id', '=', 'products.id')
            ->join('locations', 'lines.location_id', '=', 'locations.id')
            ->select([
                'counts.id as count_id',
                'counts.status',
                'counts.created_at',
                'counts.submitted_at',
                'counts.reviewed_at',
                'products.sap_material_number',
                'products.sku',
                'products.name as product_name',
                'batches.batch_number',
                'locations.code as location_code',
                'lines.system_quantity',
                'lines.physical_quantity',
                'lines.variance',
                'lines.discrepancy_reason',
            ]);
        $this->applyProductFilters($query, $filters, 'lines.location_id');
        $this->applyDateFilters($query, $filters, 'counts.created_at');
        if (isset($filters['status'])) {
            $query->where('counts.status', $filters['status']);
        }

        return $query->orderByDesc('counts.created_at')->orderBy('counts.id')
            ->get()->map(fn (object $row): array => (array) $row)->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
     */
    private function inventoryReconciliation(array $filters): array
    {
        return array_values(array_map(
            static fn (array $row): array => [
                'sap_material_number' => $row['sap_material_number'],
                'sku' => $row['sku'],
                'product_name' => $row['product_name'],
                'batch_number' => $row['batch_number'],
                'location_code' => $row['location_code'],
                'balance_quantity' => $row['balance'],
                'ledger_quantity' => $row['ledger'],
                'variance' => $row['variance'],
                'matched' => $row['matched'] ? 'Yes' : 'No',
            ],
            array_filter(
                app(StockReconciliationService::class)->details(),
                static fn (array $row): bool => (! isset($filters['product_id']) || (int) $row['product_id'] === (int) $filters['product_id'])
                    && (! isset($filters['batch_id']) || (int) $row['batch_id'] === (int) $filters['batch_id'])
                    && (! isset($filters['location_id']) || (int) $row['location_id'] === (int) $filters['location_id']),
            ),
        ));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
     */
    private function purchaseOrders(array $filters): array
    {
        $query = DB::table('purchase_order_lines as lines')
            ->join('purchase_orders as orders', 'lines.purchase_order_id', '=', 'orders.id')
            ->join('suppliers', 'orders.supplier_id', '=', 'suppliers.id')
            ->join('products', 'lines.product_id', '=', 'products.id')
            ->select([
                'orders.order_number',
                'orders.order_date',
                'orders.expected_date',
                'orders.status',
                'suppliers.name as supplier_name',
                'products.sap_material_number',
                'products.sku',
                'products.name as product_name',
                'lines.ordered_quantity',
                'lines.received_quantity',
            ]);
        $this->applyProductFilters($query, $filters, batchColumn: null);
        $this->applyDateFilters($query, $filters, 'orders.order_date');
        if (isset($filters['status'])) {
            $query->where('orders.status', $filters['status']);
        }

        return $query->orderByDesc('orders.order_date')->orderBy('orders.order_number')
            ->get()->map(fn (object $row): array => (array) $row)->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
     */
    private function auditLog(array $filters): array
    {
        $query = DB::table('audit_logs')
            ->leftJoin('users', 'audit_logs.user_id', '=', 'users.id')
            ->select([
                'audit_logs.created_at',
                'users.name as user_name',
                'audit_logs.auditable_type',
                'audit_logs.auditable_id',
                'audit_logs.event',
                'audit_logs.old_values',
                'audit_logs.new_values',
            ]);
        $this->applyDateFilters($query, $filters, 'audit_logs.created_at');
        if (isset($filters['product_id'])) {
            $query->where('audit_logs.auditable_type', 'App\\Models\\Product')
                ->where('audit_logs.auditable_id', $filters['product_id']);
        }

        return $query->orderByDesc('audit_logs.created_at')->orderByDesc('audit_logs.id')
            ->get()->map(function (object $row): array {
                $row->old_values = $row->old_values === null ? null : json_encode(json_decode($row->old_values, true));
                $row->new_values = $row->new_values === null ? null : json_encode(json_decode($row->new_values, true));

                return (array) $row;
            })->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyProductFilters(
        Builder $query,
        array $filters,
        ?string $locationColumn = null,
        string $productColumn = 'products.id',
        ?string $batchColumn = 'batches.id',
    ): void {
        if (isset($filters['product_id'])) {
            $query->where($productColumn, $filters['product_id']);
        }
        if (isset($filters['batch_id']) && $batchColumn !== null) {
            $query->where($batchColumn, $filters['batch_id']);
        }
        if (isset($filters['location_id']) && $locationColumn !== null) {
            $query->where($locationColumn, $filters['location_id']);
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyDateFilters(Builder $query, array $filters, string $dateColumn): void
    {
        if (isset($filters['from'])) {
            $query->whereDate($dateColumn, '>=', $filters['from']);
        }
        if (isset($filters['to'])) {
            $query->whereDate($dateColumn, '<=', $filters['to']);
        }
    }

    /**
     * @return array<string, string>
     */
    private function columns(string $key): array
    {
        return match ($key) {
            'stock-on-hand' => [
                'sap_material_number' => 'SAP material',
                'sku' => 'SKU',
                'product_name' => 'Product',
                'batch_number' => 'Batch',
                'location_code' => 'Location',
                'quantity' => 'On hand',
                'unit' => 'Unit',
            ],
            'goods-received' => [
                'receipt_number' => 'Receipt',
                'received_at' => 'Received at',
                'sap_material_number' => 'SAP material',
                'sku' => 'SKU',
                'product_name' => 'Product',
                'batch_number' => 'Batch',
                'location_code' => 'Location',
                'quantity' => 'Quantity',
            ],
            'goods-dispatched' => [
                'gdn_number' => 'GDN',
                'dispatched_at' => 'Dispatched at',
                'sap_material_number' => 'SAP material',
                'sku' => 'SKU',
                'product_name' => 'Product',
                'batch_number' => 'Batch',
                'location_code' => 'Location',
                'quantity' => 'Quantity',
            ],
            'movement', 'transfers' => [
                'moved_at' => 'Moved at',
                'movement_type' => 'Movement',
                'sap_material_number' => 'SAP material',
                'sku' => 'SKU',
                'product_name' => 'Product',
                'batch_number' => 'Batch',
                'from_location' => 'From',
                'to_location' => 'To',
                'quantity' => 'Quantity',
                'reason' => 'Reason',
                'performed_by' => 'Performed by',
            ],
            'expiry-ageing' => [
                'sap_material_number' => 'SAP material',
                'sku' => 'SKU',
                'product_name' => 'Product',
                'batch_number' => 'Batch',
                'expires_at' => 'Expires at',
                'days_until_expiry' => 'Days to expiry',
                'ageing_bucket' => 'Ageing',
                'location_code' => 'Location',
                'quantity' => 'Quantity',
                'unit' => 'Unit',
            ],
            'low-stock' => [
                'sap_material_number' => 'SAP material',
                'sku' => 'SKU',
                'product_name' => 'Product',
                'on_hand' => 'On hand',
                'reorder_level' => 'Reorder level',
                'max_level' => 'Maximum level',
                'unit' => 'Unit',
            ],
            'cycle-count' => [
                'count_id' => 'Count',
                'status' => 'Status',
                'created_at' => 'Created at',
                'sap_material_number' => 'SAP material',
                'sku' => 'SKU',
                'product_name' => 'Product',
                'batch_number' => 'Batch',
                'location_code' => 'Location',
                'system_quantity' => 'System',
                'physical_quantity' => 'Physical',
                'variance' => 'Variance',
                'discrepancy_reason' => 'Reason',
            ],
            'ira' => [
                'sap_material_number' => 'SAP material',
                'sku' => 'SKU',
                'product_name' => 'Product',
                'batch_number' => 'Batch',
                'location_code' => 'Location',
                'balance_quantity' => 'Stock balance',
                'ledger_quantity' => 'Ledger',
                'variance' => 'Variance',
                'matched' => 'Matched',
            ],
            'purchase-orders' => [
                'order_number' => 'Order',
                'order_date' => 'Order date',
                'expected_date' => 'Expected date',
                'status' => 'Status',
                'supplier_name' => 'Supplier',
                'sap_material_number' => 'SAP material',
                'sku' => 'SKU',
                'product_name' => 'Product',
                'ordered_quantity' => 'Ordered',
                'received_quantity' => 'Received',
            ],
            'audit-log' => [
                'created_at' => 'Recorded at',
                'user_name' => 'User',
                'auditable_type' => 'Record type',
                'auditable_id' => 'Record ID',
                'event' => 'Event',
                'old_values' => 'Previous values',
                'new_values' => 'New values',
            ],
            default => [],
        };
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, int|float|string>
     */
    private function totals(string $key, array $rows): array
    {
        $quantityField = match ($key) {
            'goods-received', 'goods-dispatched', 'movement', 'transfers', 'stock-on-hand', 'expiry-ageing' => 'quantity',
            'low-stock' => 'on_hand',
            default => null,
        };

        $totals = ['rows' => count($rows)];
        if ($quantityField !== null) {
            $totals['quantity'] = number_format(array_sum(array_map(
                fn (array $row): float => (float) ($row[$quantityField] ?? 0),
                $rows,
            )), 3, '.', '');
        }
        if ($key === 'ira') {
            $totals['matched'] = count(array_filter($rows, fn (array $row): bool => $row['matched'] === 'Yes'));
            $totals['mismatched'] = count($rows) - $totals['matched'];
            $totals['balance_quantity'] = $this->sumQuantity($rows, 'balance_quantity');
            $totals['ledger_quantity'] = $this->sumQuantity($rows, 'ledger_quantity');
            $totals['variance'] = $this->sumQuantity($rows, 'variance');
        }
        if ($key === 'purchase-orders') {
            $totals['ordered_quantity'] = $this->sumQuantity($rows, 'ordered_quantity');
            $totals['received_quantity'] = $this->sumQuantity($rows, 'received_quantity');
        }
        if ($key === 'cycle-count') {
            $totals['system_quantity'] = $this->sumQuantity($rows, 'system_quantity');
            $totals['physical_quantity'] = $this->sumQuantity($rows, 'physical_quantity');
            $totals['variance'] = $this->sumQuantity($rows, 'variance');
        }

        return $totals;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function sumQuantity(array $rows, string $field): string
    {
        return number_format(array_sum(array_map(
            fn (array $row): float => (float) ($row[$field] ?? 0),
            $rows,
        )), 3, '.', '');
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function formatQuantities(array $rows): array
    {
        $quantityFields = [
            'quantity',
            'on_hand',
            'reorder_level',
            'max_level',
            'system_quantity',
            'physical_quantity',
            'variance',
            'balance_quantity',
            'ledger_quantity',
            'ordered_quantity',
            'received_quantity',
        ];
        foreach ($rows as &$row) {
            foreach ($quantityFields as $field) {
                if (isset($row[$field]) && is_numeric($row[$field])) {
                    $row[$field] = number_format((float) $row[$field], 3, '.', '');
                }
            }
        }
        unset($row);

        return $rows;
    }
}
