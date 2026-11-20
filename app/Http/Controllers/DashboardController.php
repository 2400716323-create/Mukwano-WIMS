<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\Batch;
use App\Models\Category;
use App\Models\DispatchRequest;
use App\Models\GoodsReceipt;
use App\Models\InventorySetting;
use App\Models\Location;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PutAwayTask;
use App\Models\StockCount;
use App\Models\StockMovement;
use App\Models\Supplier;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'batch_id' => ['nullable', 'integer', 'exists:batches,id'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
        ]);

        $filters = [
            'from' => $validated['from'] ?? today()->subDays(29)->toDateString(),
            'to' => $validated['to'] ?? today()->toDateString(),
            'product_id' => $validated['product_id'] ?? null,
            'category_id' => $validated['category_id'] ?? null,
            'batch_id' => $validated['batch_id'] ?? null,
            'location_id' => $validated['location_id'] ?? null,
        ];

        // STOCK AND INVENTORY METRICS

        $stockQuery = $this->stockQuery($filters);

        $stockQuantity = (float) (clone $stockQuery)->sum('sb.quantity');

        $activeBatchesCount = (clone $stockQuery)
            ->distinct()
            ->count('b.id');

        $pendingDispatchesCount = DispatchRequest::query()
            ->where('status', 'requested')
            ->count();

        $criticalAlertsCount = Alert::query()
            ->whereNull('resolved_at')
            ->where('condition_active', true)
            ->whereIn('severity', ['critical', 'high'])
            ->count();

        $categoryStock = (clone $stockQuery)
            ->selectRaw("COALESCE(c.name, 'Uncategorized') as label, SUM(sb.quantity) as quantity")
            ->groupBy('c.name')
            ->orderBy('label')
            ->get();

        $expiryStatus = $this->expiryStatus($stockQuery);
        $lowStockProducts = $this->lowStockProducts($filters);

        $pendingPurchaseOrders = $this->pendingPurchaseOrders($filters)->count();
        $pendingCountApprovals = $this->pendingCountApprovals($filters)->count();

        $expiryWindows = InventorySetting::query()->first()?->expiryWindows() ?? [
            'soon' => 30,
            'warning' => 60,
            'notice' => 90,
        ];

        // STOCK MOVEMENT CHARTS

        $movementRows = $this->movementQuery($filters)
            ->selectRaw('DATE(sm.moved_at) as movement_date, sm.movement_type, SUM(sm.quantity) as quantity')
            ->groupByRaw('DATE(sm.moved_at), sm.movement_type')
            ->orderBy('movement_date')
            ->get();

        $movementChart = $this->movementChart($movementRows);
        $receiptIssueChart = $this->receiptIssueChart($movementRows);
        $ira = $this->inventoryRecordAccuracy($filters);

        $receivedQuantity = (float) (clone $this->movementQuery($filters))
            ->where('sm.movement_type', 'receipt')
            ->sum('sm.quantity');

        $issuedQuantity = (float) (clone $this->movementQuery($filters))
            ->where('sm.movement_type', 'dispatch')
            ->sum('sm.quantity');

        $expiringBatchCount = (clone $stockQuery)
            ->whereNotNull('b.expires_at')
            ->whereDate('b.expires_at', '>=', today())
            ->whereDate('b.expires_at', '<=', today()->addDays($expiryWindows['notice']))
            ->distinct()
            ->count('b.id');

        // RECENT STOCK MOVEMENTS

        $recentMovements = StockMovement::query()
            ->with('batch.product', 'fromLocation', 'toLocation', 'performer')
            ->when($filters['from'], fn ($query, string $date) => $query->whereDate('moved_at', '>=', $date))
            ->when($filters['to'], fn ($query, string $date) => $query->whereDate('moved_at', '<=', $date))
            ->when($filters['product_id'], fn ($query, int $id) => $query->whereHas('batch', fn ($batch) => $batch->where('product_id', $id)))
            ->when($filters['batch_id'], fn ($query, int $id) => $query->where('batch_id', $id))
            ->when($filters['category_id'], fn ($query, int $id) => $query->whereHas('batch.product', fn ($product) => $product->where('category_id', $id)))
            ->when($filters['location_id'], fn ($query, int $id) => $query->where(fn ($movement) => $movement
                ->where('from_location_id', $id)
                ->orWhere('to_location_id', $id)))
            ->latest('moved_at')
            ->limit(8)
            ->get();

        // RECENT GOODS RECEIPTS

        $recentReceipts = GoodsReceipt::query()
            ->with('supplier', 'lines.product', 'lines.batch', 'lines.location')
            ->when($filters['from'], fn ($query, string $date) => $query->whereDate('received_at', '>=', $date))
            ->when($filters['to'], fn ($query, string $date) => $query->whereDate('received_at', '<=', $date))
            ->when(
                $filters['product_id'] || $filters['category_id'] || $filters['batch_id'] || $filters['location_id'],
                function ($query) use ($filters): void {
                    $query->whereHas('lines', function ($line) use ($filters): void {
                        $line
                            ->when($filters['product_id'], fn ($query, int $id) => $query->where('product_id', $id))
                            ->when($filters['batch_id'], fn ($query, int $id) => $query->where('batch_id', $id))
                            ->when($filters['location_id'], fn ($query, int $id) => $query->where('location_id', $id))
                            ->when($filters['category_id'], fn ($query, int $id) => $query->whereHas('product', fn ($product) => $product->where('category_id', $id)));
                    });
                }
            )
            ->latest('received_at')
            ->limit(5)
            ->get();

        // RECENT DISPATCHES

        $recentDispatches = DispatchRequest::query()
            ->with('product', 'requester', 'dispatcher', 'lines.batch', 'lines.location')
            ->where('status', 'dispatched')
            ->when($filters['from'], fn ($query, string $date) => $query->whereDate('dispatched_at', '>=', $date))
            ->when($filters['to'], fn ($query, string $date) => $query->whereDate('dispatched_at', '<=', $date))
            ->when($filters['product_id'], fn ($query, int $id) => $query->where('product_id', $id))
            ->when($filters['category_id'], fn ($query, int $id) => $query->whereHas('product', fn ($product) => $product->where('category_id', $id)))
            ->when($filters['batch_id'] || $filters['location_id'], function ($query) use ($filters): void {
                $query->whereHas('lines', fn ($line) => $line
                    ->when($filters['batch_id'], fn ($query, int $id) => $query->where('batch_id', $id))
                    ->when($filters['location_id'], fn ($query, int $id) => $query->where('location_id', $id)));
            })
            ->latest('dispatched_at')
            ->limit(5)
            ->get();

        // ACTIVE ALERTS

        $openAlerts = Alert::query()
            ->whereNull('resolved_at')
            ->where('condition_active', true)
            ->orderByRaw("CASE severity WHEN 'critical' THEN 0 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END")
            ->orderByDesc('last_seen_at')
            ->limit(6)
            ->get();

        // QUICK ACTIONS

        $user = $request->user();

        $quickActions = collect([
            [
                'ability' => 'create',
                'target' => PurchaseOrder::class,
                'label' => 'Create purchase order',
                'url' => route('purchase-orders.create'),
            ],
            [
                'ability' => 'create',
                'target' => GoodsReceipt::class,
                'label' => 'Receive goods',
                'url' => route('goods-receipts.create'),
            ],
            [
                'ability' => 'create',
                'target' => DispatchRequest::class,
                'label' => 'Request dispatch',
                'url' => route('dispatch-requests.create'),
            ],
            [
                'ability' => 'create',
                'target' => StockCount::class,
                'label' => 'Create cycle count',
                'url' => route('stock-counts.create'),
            ],
            [
                'ability' => 'viewAny',
                'target' => Location::class,
                'label' => 'Transfer stock',
                'url' => route('stock-transfers.create'),
            ],
        ])
            ->filter(fn (array $action): bool => $user?->can($action['ability'], $action['target']) ?? false)
            ->values();

        // GENERAL DASHBOARD COUNTS

        $totalProductsCount = Product::query()
            ->where('status', 'active')
            ->count();

        $warehouseLocationsCount = Location::query()
            ->where('type', 'bin')
            ->count();

        $putAwayBalancesCount = DB::table('stock_balances')
            ->where('quantity', '>', 0)
            ->count();

        $putAwayUnitsCount = (float) DB::table('stock_balances')
            ->where('quantity', '>', 0)
            ->sum('quantity');

        $supplierCount = Supplier::query()
            ->where('status', 'active')
            ->count();

        $outOfStockCount = Product::query()
            ->where('status', 'active')
            ->whereNotIn(
                'id',
                DB::table('stock_balances')
                    ->join('batches', 'batches.id', '=', 'stock_balances.batch_id')
                    ->where('stock_balances.quantity', '>', 0)
                    ->select('batches.product_id')
            )
            ->count();

        // PUT-AWAY TASK COUNTS

        $putAwayTaskQuery = PutAwayTask::query()
            ->when($filters['from'], fn ($query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['to'], fn ($query, string $date) => $query->whereDate('created_at', '<=', $date))
            ->when($filters['product_id'], fn ($query, int $id) => $query->whereHas('batch', fn ($batch) => $batch->where('product_id', $id)))
            ->when($filters['category_id'], fn ($query, int $id) => $query->whereHas('batch.product', fn ($product) => $product->where('category_id', $id)))
            ->when($filters['batch_id'], fn ($query, int $id) => $query->where('batch_id', $id))
            ->when($filters['location_id'], fn ($query) => $query->where(function ($location) use ($filters): void {
                $location->where('source_location_id', $filters['location_id'])
                    ->orWhere('destination_location_id', $filters['location_id']);
            }));

        $putAwayPendingCount = (clone $putAwayTaskQuery)
            ->where('status', PutAwayTask::STATUS_PENDING)
            ->count();

        $putAwayInProgressCount = (clone $putAwayTaskQuery)
            ->where('status', PutAwayTask::STATUS_IN_PROGRESS)
            ->count();

        $putAwayCompletedCount = (clone $putAwayTaskQuery)
            ->where('status', PutAwayTask::STATUS_COMPLETED)
            ->count();

        // LIVE CATEGORY BREAKDOWN

        $categoryColors = [
            '#2563eb',
            '#0ea5e9',
            '#eab308',
            '#22c55e',
            '#8b5cf6',
            '#f97316',
            '#ec4899',
            '#64748b',
        ];

        $categoryTotal = (float) $categoryStock->sum('quantity');

        $categoryBreakdown = $categoryStock
            ->values()
            ->map(function ($item, int $index) use ($categoryColors, $categoryTotal): array {
                $quantity = (float) $item->quantity;

                return [
                    'name' => $item->label,
                    'quantity' => $quantity,
                    'percentage' => $categoryTotal > 0
                        ? round(($quantity / $categoryTotal) * 100, 1)
                        : 0,
                    'color' => $categoryColors[$index % count($categoryColors)],
                ];
            })
            ->all();

        // LIVE SEVEN-DAY STOCK MOVEMENT

        $sevenDaysStart = today()->subDays(6)->startOfDay();
        $sevenDaysEnd = today()->endOfDay();

        $sevenDayRows = $this->movementQuery($filters)
            ->whereBetween('sm.moved_at', [$sevenDaysStart, $sevenDaysEnd])
            ->selectRaw('DATE(sm.moved_at) as movement_date, sm.movement_type, SUM(sm.quantity) as quantity')
            ->groupByRaw('DATE(sm.moved_at), sm.movement_type')
            ->get();

        $sevenDayTotals = [];

        foreach ($sevenDayRows as $row) {
            $date = $row->movement_date;
            $type = $row->movement_type;

            $sevenDayTotals[$date][$type] = (float) $row->quantity;
        }

        $sevenDayLabels = [];
        $sevenDayReceived = [];
        $sevenDayIssued = [];
        $sevenDayPutAway = [];
        $sevenDayTransferred = [];

        for ($day = 0; $day < 7; $day++) {
            $date = today()->subDays(6 - $day)->toDateString();

            $sevenDayLabels[] = Carbon::parse($date)->format('M d');
            $sevenDayReceived[] = (float) ($sevenDayTotals[$date]['receipt'] ?? 0);
            $sevenDayIssued[] = (float) ($sevenDayTotals[$date]['dispatch'] ?? 0);
            $sevenDayPutAway[] = (float) ($sevenDayTotals[$date]['put_away'] ?? 0);
            $sevenDayTransferred[] = (float) ($sevenDayTotals[$date]['transfer'] ?? 0);
        }

        $stockMovement7Days = [
            'labels' => $sevenDayLabels,
            'received' => $sevenDayReceived,
            'issued' => $sevenDayIssued,
            'put_away' => $sevenDayPutAway,
            'transferred' => $sevenDayTransferred,
        ];

        // LIVE EXPIRY BREAKDOWN

        $expiryTotal = (float) collect($expiryStatus)->sum('quantity');

        $expiryBreakdown = collect($expiryStatus)
            ->values()
            ->map(function (array $item, int $index) use ($expiryTotal, $categoryColors): array {
                $label = $item['label'];
                $quantity = (float) $item['quantity'];
                $lowerLabel = strtolower($label);

                $color = str_contains($lowerLabel, 'expired')
                    ? '#ef4444'
                    : (str_contains($lowerLabel, 'within')
                        ? '#f97316'
                        : (str_contains($lowerLabel, 'days')
                            ? '#eab308'
                            : (str_contains($lowerLabel, 'over')
                                ? '#22c55e'
                                : (str_contains($lowerLabel, 'no expiry')
                                    ? '#64748b'
                                    : $categoryColors[$index % count($categoryColors)]))));

                return [
                    'label' => $label,
                    'quantity' => $quantity,
                    'percentage' => $expiryTotal > 0
                        ? round(($quantity / $expiryTotal) * 100, 1)
                        : 0,
                    'color' => $color,
                ];
            })
            ->all();

        // LIVE MOVEMENT TABLE

        $targetMovements = $recentMovements
            ->map(function (StockMovement $movement): array {
                $type = strtolower((string) $movement->movement_type);

                $badge = match ($type) {
                    'receipt' => 'received',
                    'dispatch' => 'issued',
                    'put_away' => 'put-away',
                    'transfer' => 'transferred',
                    default => 'other',
                };

                $quantity = (float) $movement->quantity;

                $formattedQuantity = rtrim(
                    rtrim(number_format(abs($quantity), 3, '.', ''), '0'),
                    '.'
                );

                $formattedQuantity = match ($badge) {
                    'received' => '+' . $formattedQuantity,
                    'issued' => '-' . $formattedQuantity,
                    default => $formattedQuantity,
                };

                $location = $movement->toLocation?->code
                    ?? $movement->toLocation?->name
                    ?? $movement->fromLocation?->code
                    ?? $movement->fromLocation?->name
                    ?? 'External';

                return [
                    'date' => $movement->moved_at
                        ? Carbon::parse($movement->moved_at)->format('d M Y H:i')
                        : '-',
                    'product' => $movement->batch?->product?->name ?? 'Unknown product',
                    'category' => $movement->batch?->product?->category?->name ?? 'Uncategorized',
                    'type' => ucfirst(str_replace('_', ' ', $type)),
                    'badge' => $badge,
                    'quantity' => $formattedQuantity,
                    'location' => $location,
                    'icon' => 'box',
                ];
            })
            ->values()
            ->all();

        // LIVE GOODS RECEIPTS TABLE

        $targetReceipts = $recentReceipts
            ->map(fn (GoodsReceipt $receipt): array => [
                'date' => $receipt->received_at
                    ? Carbon::parse($receipt->received_at)->format('d M Y')
                    : '-',
                'receipt_number' => $receipt->receipt_number ?? 'GRN pending',
                'supplier' => $receipt->supplier?->name ?? 'Unknown supplier',
                'status' => ucfirst(str_replace('_', ' ', (string) $receipt->status)),
            ])
            ->values()
            ->all();

        // LIVE DISPATCH TABLE

        $targetDispatches = $recentDispatches
            ->map(fn (DispatchRequest $dispatch): array => [
                'date' => $dispatch->dispatched_at
                    ? Carbon::parse($dispatch->dispatched_at)->format('d M Y')
                    : '-',
                'gdn_number' => $dispatch->gdn_number ?? 'GDN pending',
                'customer' => $dispatch->requester?->name ?? 'Internal request',
                'status' => ucfirst(str_replace('_', ' ', (string) $dispatch->status)),
            ])
            ->values()
            ->all();

        // LIVE LOW-STOCK TABLE

        $targetLowStock = $lowStockProducts
            ->map(function (array $item, int $index) use ($categoryColors): array {
                return [
                    'product' => $item['product']->name,
                    'current_stock' => number_format($item['quantity'], 3),
                    'min_level' => number_format($item['reorder_level'], 3),
                    'status' => 'Low',
                    'color' => $categoryColors[$index % count($categoryColors)],
                ];
            })
            ->values()
            ->all();

        // LIVE ALERTS TABLE

        $targetAlerts = $openAlerts
            ->map(function (Alert $alert): array {
                $severity = strtolower((string) $alert->severity);

                $displaySeverity = in_array($severity, ['critical', 'high'], true)
                    ? 'danger'
                    : 'warning';

                $title = $alert->getAttribute('title')
                    ?: $alert->getAttribute('message')
                    ?: $alert->getAttribute('description')
                    ?: 'Inventory alert';

                $icon = str_contains(strtolower($title), 'expir')
                    ? 'clock'
                    : (str_contains(strtolower($title), 'order') ? 'order' : 'alert');

                return [
                    'title' => $title,
                    'time' => $alert->last_seen_at
                        ? Carbon::parse($alert->last_seen_at)->diffForHumans()
                        : 'Recently',
                    'severity' => $displaySeverity,
                    'icon' => $icon,
                    'link' => route('alerts.index'),
                ];
            })
            ->values()
            ->all();

        // REPORT DOWNLOAD DEFINITIONS

        $downloadReports = [
            ['title' => 'Stock on Hand Report', 'key' => 'stock-on-hand', 'icon' => 'clipboard', 'color' => '#10b981'],
            ['title' => 'Goods Received Report', 'key' => 'goods-received', 'icon' => 'truck', 'color' => '#2563eb'],
            ['title' => 'Goods Dispatched Report', 'key' => 'goods-dispatched', 'icon' => 'arrows', 'color' => '#8b5cf6'],
            ['title' => 'Expiry Ageing Report', 'key' => 'expiry-ageing', 'icon' => 'warning', 'color' => '#f59e0b'],
            ['title' => 'Stock Movement Report', 'key' => 'movement', 'icon' => 'chart', 'color' => '#0ea5e9'],
        ];

        // SEND DATA TO DASHBOARD VIEW

        return view('dashboard', [
            'filters' => $filters,

            'categories' => Category::query()->orderBy('name')->get(),

            'products' => Product::query()
                ->where('status', 'active')
                ->orderBy('name')
                ->get(),

            'batches' => Batch::query()
                ->with('product')
                ->orderBy('batch_number')
                ->get(),

            'locations' => Location::query()
                ->where('type', 'bin')
                ->orderBy('code')
                ->get(),

            'metrics' => [
                'total_products' => $totalProductsCount,
                'stock_quantity' => $stockQuantity,
                'active_batches' => $activeBatchesCount,
                'warehouse_locations' => $warehouseLocationsCount,
                'pending_dispatches' => $pendingDispatchesCount,
                'critical_alerts' => $criticalAlertsCount,
                'low_stock_products' => $lowStockProducts->count(),
                'expiring_batches' => $expiringBatchCount,
                'expiry_notice_days' => $expiryWindows['notice'],
                'pending_purchase_orders' => $pendingPurchaseOrders,
                'pending_count_approvals' => $pendingCountApprovals,
                'received_quantity' => $receivedQuantity,
                'issued_quantity' => $issuedQuantity,
                'put_away_balances' => $putAwayBalancesCount,
                'put_away_units' => $putAwayUnitsCount,
                'put_away_pending' => $putAwayPendingCount,
                'put_away_in_progress' => $putAwayInProgressCount,
                'put_away_completed' => $putAwayCompletedCount,
                'active_suppliers' => $supplierCount,
                'out_of_stock_products' => $outOfStockCount,
            ],

            'categoryBreakdown' => $categoryBreakdown,
            'stockMovement7Days' => $stockMovement7Days,
            'expiryBreakdown' => $expiryBreakdown,
            'targetMovements' => $targetMovements,
            'targetReceipts' => $targetReceipts,
            'targetDispatches' => $targetDispatches,
            'targetLowStock' => $targetLowStock,
            'targetAlerts' => $targetAlerts,
            'downloadReports' => $downloadReports,

            'lowStockProducts' => $lowStockProducts,
            'categoryStock' => $categoryStock,
            'expiryStatus' => $expiryStatus,
            'movementChart' => $movementChart,
            'receiptIssueChart' => $receiptIssueChart,
            'ira' => $ira,

            'recentMovements' => $recentMovements,
            'recentReceipts' => $recentReceipts,
            'recentDispatches' => $recentDispatches,
            'openAlerts' => $openAlerts,
            'quickActions' => $quickActions,
        ]);
    }

    // STOCK QUERY

    private function stockQuery(array $filters): Builder
    {
        return $this->applyStockFilters(
            DB::table('stock_balances as sb')
                ->join('batches as b', 'b.id', '=', 'sb.batch_id')
                ->join('products as p', 'p.id', '=', 'b.product_id')
                ->leftJoin('categories as c', 'c.id', '=', 'p.category_id')
                ->join('locations as l', 'l.id', '=', 'sb.location_id')
                ->where('sb.quantity', '>', 0),
            $filters,
        );
    }

    private function applyStockFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['product_id'] ?? null, fn ($query, int $id) => $query->where('p.id', $id))
            ->when($filters['category_id'] ?? null, fn ($query, int $id) => $query->where('p.category_id', $id))
            ->when($filters['batch_id'] ?? null, fn ($query, int $id) => $query->where('b.id', $id))
            ->when($filters['location_id'] ?? null, fn ($query, int $id) => $query->where('l.id', $id));
    }

    // EXPIRY STATUS

    private function expiryStatus(Builder $stockQuery): array
    {
        $windows = InventorySetting::query()->first()?->expiryWindows() ?? [
            'soon' => 30,
            'warning' => 60,
            'notice' => 90,
        ];

        $totals = [
            'Expired' => 0.0,
            "Within {$windows['soon']} days" => 0.0,
            ($windows['soon'] + 1) . "-{$windows['warning']} days" => 0.0,
            ($windows['warning'] + 1) . "-{$windows['notice']} days" => 0.0,
            "Over {$windows['notice']} days" => 0.0,
            'No expiry date' => 0.0,
        ];

        $rows = (clone $stockQuery)
            ->selectRaw('b.expires_at, SUM(sb.quantity) as quantity')
            ->groupBy('b.expires_at')
            ->get();

        foreach ($rows as $row) {
            $daysRemaining = $row->expires_at === null
                ? null
                : today()->diffInDays(Carbon::parse($row->expires_at)->startOfDay(), false);

            $bucket = match (true) {
                $row->expires_at === null => 'No expiry date',
                $daysRemaining < 0 => 'Expired',
                $daysRemaining <= $windows['soon'] => "Within {$windows['soon']} days",
                $daysRemaining <= $windows['warning'] => ($windows['soon'] + 1) . "-{$windows['warning']} days",
                $daysRemaining <= $windows['notice'] => ($windows['warning'] + 1) . "-{$windows['notice']} days",
                default => "Over {$windows['notice']} days",
            };

            $totals[$bucket] += (float) $row->quantity;
        }

        return collect($totals)
            ->map(fn (float $quantity, string $label): array => [
                'label' => $label,
                'quantity' => $quantity,
            ])
            ->values()
            ->all();
    }

    // LOW STOCK PRODUCTS

    private function lowStockProducts(array $filters): Collection
    {
        $productsQuery = Product::query()
            ->where('status', 'active')
            ->where('reorder_level', '>', 0)
            ->when($filters['product_id'], fn ($query, int $id) => $query->whereKey($id))
            ->when($filters['category_id'], fn ($query, int $id) => $query->where('category_id', $id))
            ->when($filters['batch_id'], fn ($query, int $id) => $query->whereHas('batches', fn ($batch) => $batch->whereKey($id)));

        $products = $productsQuery->get([
            'id',
            'sku',
            'name',
            'unit',
            'reorder_level',
        ]);

        if ($products->isEmpty()) {
            return collect();
        }

        $quantities = $this->stockQuery($filters)
            ->whereIn('p.id', $products->modelKeys())
            ->selectRaw('p.id, SUM(sb.quantity) as quantity')
            ->groupBy('p.id')
            ->pluck('quantity', 'p.id');

        return $products
            ->filter(fn (Product $product): bool =>
                (float) ($quantities[$product->id] ?? 0) < (float) $product->reorder_level
            )
            ->map(fn (Product $product): array => [
                'product' => $product,
                'quantity' => (float) ($quantities[$product->id] ?? 0),
                'reorder_level' => (float) $product->reorder_level,
            ])
            ->values();
    }

    // PENDING PURCHASE ORDERS

    private function pendingPurchaseOrders(array $filters): \Illuminate\Database\Eloquent\Builder
    {
        return PurchaseOrder::query()
            ->whereIn('status', [
                PurchaseOrder::STATUS_DRAFT,
                PurchaseOrder::STATUS_APPROVED,
                PurchaseOrder::STATUS_PARTIALLY_RECEIVED,
            ])
            ->when($filters['from'], fn ($query, string $date) => $query->whereDate('order_date', '>=', $date))
            ->when($filters['to'], fn ($query, string $date) => $query->whereDate('order_date', '<=', $date))
            ->when(
                $filters['product_id'] || $filters['category_id'],
                fn ($query) => $query->whereHas('lines.product', fn ($product) => $product
                    ->when($filters['product_id'], fn ($query, int $id) => $query->whereKey($id))
                    ->when($filters['category_id'], fn ($query, int $id) => $query->where('category_id', $id)))
            );
    }

    // PENDING STOCK COUNT APPROVALS

    private function pendingCountApprovals(array $filters): \Illuminate\Database\Eloquent\Builder
    {
        return StockCount::query()
            ->where('status', 'submitted')
            ->when($filters['from'], fn ($query, string $date) => $query->whereDate('submitted_at', '>=', $date))
            ->when($filters['to'], fn ($query, string $date) => $query->whereDate('submitted_at', '<=', $date))
            ->when($filters['location_id'], fn ($query, int $id) => $query->where('location_id', $id))
            ->when($filters['product_id'] || $filters['category_id'] || $filters['batch_id'], function ($query) use ($filters): void {
                $query->whereHas('lines.batch.product', fn ($product) => $product
                    ->when($filters['product_id'], fn ($query, int $id) => $query->whereKey($id))
                    ->when($filters['category_id'], fn ($query, int $id) => $query->where('category_id', $id)))
                    ->when($filters['batch_id'], fn ($query, int $id) => $query->whereHas('lines', fn ($line) => $line->where('batch_id', $id)));
            });
    }

    // STOCK MOVEMENT QUERY

    private function movementQuery(array $filters): Builder
    {
        $query = DB::table('stock_movements as sm')
            ->join('batches as b', 'b.id', '=', 'sm.batch_id')
            ->join('products as p', 'p.id', '=', 'b.product_id')
            ->leftJoin('categories as c', 'c.id', '=', 'p.category_id')
            ->when($filters['from'], fn ($query, string $date) => $query->whereDate('sm.moved_at', '>=', $date))
            ->when($filters['to'], fn ($query, string $date) => $query->whereDate('sm.moved_at', '<=', $date))
            ->when($filters['location_id'], fn ($query, int $id) => $query->where(fn ($query) => $query
                ->where('sm.from_location_id', $id)
                ->orWhere('sm.to_location_id', $id)));

        return $this->applyStockFilters($query, $filters);
    }

    // MOVEMENT CHART

    private function movementChart(Collection $rows): array
    {
        $daily = [];
        $types = [];

        foreach ($rows as $row) {
            $daily[$row->movement_date][$row->movement_type] = (float) $row->quantity;
            $types[$row->movement_type] = true;
        }

        $labels = array_keys($daily);
        $movementTypes = array_keys($types);

        sort($movementTypes);

        $datasets = [];

        foreach ($movementTypes as $type) {
            $datasets[] = [
                'label' => ucwords(str_replace('_', ' ', $type)),
                'data' => array_map(
                    fn (string $date): float => $daily[$date][$type] ?? 0.0,
                    $labels
                ),
            ];
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets,
            'rows' => collect($daily)->map(fn (array $types, string $date): array => [
                'date' => $date,
                'types' => $types,
            ])->values()->all(),
        ];
    }

    // RECEIPT AND ISSUE CHART

    private function receiptIssueChart(Collection $rows): array
    {
        $daily = [];

        foreach ($rows as $row) {
            if (in_array($row->movement_type, ['receipt', 'dispatch'], true)) {
                $daily[$row->movement_date][$row->movement_type] = (float) $row->quantity;
            }
        }

        $labels = array_keys($daily);

        return [
            'labels' => $labels,
            'received' => array_map(fn (string $date): float => $daily[$date]['receipt'] ?? 0.0, $labels),
            'issued' => array_map(fn (string $date): float => $daily[$date]['dispatch'] ?? 0.0, $labels),
        ];
    }

    // INVENTORY RECORD ACCURACY

    private function inventoryRecordAccuracy(array $filters): array
    {
        $tolerance = (float) InventorySetting::current()->accuracy_tolerance_percent;

        $lines = DB::table('stock_count_lines as scl')
            ->join('stock_counts as sc', 'sc.id', '=', 'scl.stock_count_id')
            ->join('batches as b', 'b.id', '=', 'scl.batch_id')
            ->join('products as p', 'p.id', '=', 'b.product_id')
            ->leftJoin('categories as c', 'c.id', '=', 'p.category_id')
            ->where('sc.status', 'approved')
            ->whereNotNull('scl.physical_quantity')
            ->when($filters['from'], fn ($query, string $date) => $query->whereDate('sc.reviewed_at', '>=', $date))
            ->when($filters['to'], fn ($query, string $date) => $query->whereDate('sc.reviewed_at', '<=', $date))
            ->when($filters['product_id'], fn ($query, int $id) => $query->where('p.id', $id))
            ->when($filters['category_id'], fn ($query, int $id) => $query->where('p.category_id', $id))
            ->when($filters['batch_id'], fn ($query, int $id) => $query->where('b.id', $id))
            ->when($filters['location_id'], fn ($query, int $id) => $query->where('scl.location_id', $id))
            ->selectRaw('DATE(sc.reviewed_at) as review_date, scl.system_quantity, scl.variance')
            ->orderBy('review_date')
            ->get();

        $daily = [];
        $totalCounted = 0;
        $totalMatching = 0;

        foreach ($lines as $line) {
            $systemQuantity = (float) $line->system_quantity;
            $variance = (float) $line->variance;

            $matches = $systemQuantity === 0.0
                ? $variance === 0.0
                : abs(($variance / $systemQuantity) * 100) <= $tolerance;

            $daily[$line->review_date] ??= [
                'counted' => 0,
                'matching' => 0,
            ];

            $daily[$line->review_date]['counted']++;
            $daily[$line->review_date]['matching'] += $matches ? 1 : 0;

            $totalCounted++;
            $totalMatching += $matches ? 1 : 0;
        }

        $rows = collect($daily)
            ->map(fn (array $counts, string $date): array => [
                'date' => $date,
                ...$counts,
                'discrepancies' => $counts['counted'] - $counts['matching'],
                'accuracy_percent' => $counts['counted'] === 0
                    ? 0.0
                    : round(($counts['matching'] / $counts['counted']) * 100, 2),
            ])
            ->values();

        return [
            'counted' => $totalCounted,
            'matching' => $totalMatching,
            'discrepancies' => $totalCounted - $totalMatching,
            'accuracy_percent' => $totalCounted === 0
                ? 0.0
                : round(($totalMatching / $totalCounted) * 100, 2),
            'rows' => $rows,
        ];
    }
}
