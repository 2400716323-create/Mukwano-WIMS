
@extends('catalog.layout')

@section('title', 'Dashboard')

@section('content')
<div class="mw-dashboard">

    {{-- Welcome banner --}}
    <section class="mw-hero">
        <div>
            <span class="mw-eyebrow">MUKWANO WAREHOUSE MANAGEMENT</span>
            <h1>Welcome back, {{ auth()->user()->first_name ?? explode(' ', auth()->user()->name)[0] }}!</h1>
            <p>Here is an overview of your warehouse and inventory information.</p>
            <div class="mw-hero-meta">
                <span>📅 {{ now()->format('l, d F Y') }}</span>
                <span>📍 Namanve Warehouse</span>
            </div>
        </div>
        <div class="mw-hero-location">
            <span class="mw-location-icon">⌖</span>
            <div>
                <strong>Namanve Warehouse</strong>
                <small>Kampala, Uganda</small>
            </div>
        </div>
    </section>

    {{-- KPI cards --}}
    <section class="mw-kpis">
        <a href="{{ route('products.index') }}" class="mw-kpi">
            <span class="mw-kpi-icon green">▦</span>
            <div>
                <small>Total Products</small>
                <strong>{{ number_format($metrics['total_products'] ?? 0) }}</strong>
                <span>Products in catalogue</span>
            </div>
        </a>

        <a href="{{ route('inventory.index') }}" class="mw-kpi">
            <span class="mw-kpi-icon orange">▤</span>
            <div>
                <small>Total Stock Quantity</small>
                <strong>{{ number_format($metrics['stock_quantity'] ?? 0) }}</strong>
                <span>Units recorded</span>
            </div>
        </a>

        <a href="{{ route('inventory.index') }}" class="mw-kpi">
            <span class="mw-kpi-icon purple">▥</span>
            <div>
                <small>Inventory Management</small>
                <strong class="mw-manage">View Inventory</strong>
                <span>Review inventory records</span>
            </div>
        </a>

        <a href="{{ route('batches.index') }}" class="mw-kpi">
            <span class="mw-kpi-icon teal">▦</span>
            <div>
                <small>Active Batches</small>
                <strong>{{ number_format($metrics['active_batches'] ?? 0) }}</strong>
                <span>Recorded batches</span>
            </div>
        </a>

        <a href="{{ route('locations.index') }}" class="mw-kpi">
            <span class="mw-kpi-icon blue">⌖</span>
            <div>
                <small>Warehouse Locations</small>
                <strong>{{ number_format($metrics['warehouse_locations'] ?? 0) }}</strong>
                <span>Storage locations</span>
            </div>
        </a>
    </section>

    {{-- Analytics --}}
    <section class="mw-section">
        <div class="mw-section-heading">
            <div>
                <h2>Warehouse Analytics</h2>
                <p>Visual summaries of your inventory information</p>
            </div>
        </div>

        <div class="mw-analytics">

            <article class="mw-card">
                <div class="mw-card-heading">
                    <div>
                        <h3>Stock by Category</h3>
                        <p>Quantity across product categories</p>
                    </div>
                </div>
                <div class="mw-donut-area">
                    <div class="mw-donut">
                        <canvas id="categoryDonutChart"></canvas>
                        <div class="mw-donut-center">
                            <strong>{{ number_format($metrics['stock_quantity'] ?? 0) }}</strong>
                            <small>Total Units</small>
                        </div>
                    </div>
                    <div class="mw-legend">
                        @forelse ($categoryBreakdown as $item)
                            <div class="mw-legend-row">
                                <span class="mw-dot" style="background:{{ $item['color'] }};"></span>
                                <span>{{ $item['name'] }}</span>
                                <strong>{{ $item['percentage'] }}%</strong>
                            </div>
                        @empty
                            <p class="mw-empty">No category information available.</p>
                        @endforelse
                    </div>
                </div>
            </article>

            <article class="mw-card">
                <div class="mw-card-heading">
                    <div>
                        <h3>Stock Movement</h3>
                        <p>Recorded activity over the last 7 days</p>
                    </div>
                </div>
                <div class="mw-movement-legend">
                    <span><i class="mw-dot" style="background:#10b981"></i> Received</span>
                    <span><i class="mw-dot" style="background:#2563eb"></i> Issued</span>
                    <span><i class="mw-dot" style="background:#f59e0b"></i> Put-away</span>
                    <span><i class="mw-dot" style="background:#8b5cf6"></i> Transferred</span>
                </div>
                <div class="mw-line-chart">
                    <canvas id="stockMovementChart"></canvas>
                </div>
            </article>

            <article class="mw-card">
                <div class="mw-card-heading">
                    <div>
                        <h3>Expiry Status</h3>
                        <p>Batch expiry overview</p>
                    </div>
                </div>
                <div class="mw-donut-area">
                    <div class="mw-donut">
                        <canvas id="expiryDonutChart"></canvas>
                        <div class="mw-donut-center">
                            <strong>{{ number_format($metrics['active_batches'] ?? 0) }}</strong>
                            <small>Batches</small>
                        </div>
                    </div>
                    <div class="mw-legend">
                        @forelse ($expiryBreakdown as $item)
                            <div class="mw-legend-row">
                                <span class="mw-dot" style="background:{{ $item['color'] }};"></span>
                                <span>{{ $item['label'] }}</span>
                                <strong>{{ $item['percentage'] }}%</strong>
                            </div>
                        @empty
                            <p class="mw-empty">No expiry information available.</p>
                        @endforelse
                    </div>
                </div>
            </article>

        </div>
    </section>

    {{-- Records --}}
    <section class="mw-section">
        <div class="mw-section-heading">
            <div>
                <h2>Warehouse Records</h2>
                <p>Recent records and inventory information</p>
            </div>
        </div>

        <div class="mw-record-grid">

            <article class="mw-card">
                <div class="mw-card-heading">
                    <h3>Recent Stock Movements</h3>
                    <a href="{{ route('inventory.ledger') }}">View all</a>
                </div>
                <div class="mw-table-wrap">
                    <table class="mw-table">
                        <thead>
                            <tr><th>Date</th><th>Product</th><th>Type</th><th>Quantity</th><th>Location</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($targetMovements as $mov)
                                <tr>
                                    <td>{{ $mov['date'] }}</td>
                                    <td>{{ $mov['product'] }}</td>
                                    <td>
                                        <span class="mw-status {{ $mov['badge'] === 'received' ? 'success' : ($mov['badge'] === 'issued' ? 'info' : 'purple') }}">
                                            {{ ucfirst($mov['badge']) }}
                                        </span>
                                    </td>
                                    <td>{{ $mov['quantity'] }}</td>
                                    <td>{{ $mov['location'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="mw-empty">No movement records to display.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="mw-card">
                <div class="mw-card-heading">
                    <h3>Recent Goods Receipts</h3>
                    <a href="{{ route('goods-receipts.index') }}">View all</a>
                </div>
                <div class="mw-table-wrap">
                    <table class="mw-table">
                        <thead>
                            <tr><th>Date</th><th>GRN No.</th><th>Supplier</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($targetReceipts as $rec)
                                <tr>
                                    <td>{{ $rec['date'] }}</td>
                                    <td>{{ $rec['receipt_number'] }}</td>
                                    <td>{{ $rec['supplier'] }}</td>
                                    <td><span class="mw-status success">{{ $rec['status'] }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="mw-empty">No receipt records to display.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="mw-card">
                <div class="mw-card-heading">
                    <h3>Recent Dispatches</h3>
                    <a href="{{ route('dispatch-requests.index') }}">View all</a>
                </div>
                <div class="mw-table-wrap">
                    <table class="mw-table">
                        <thead>
                            <tr><th>Date</th><th>GDN No.</th><th>Customer / Department</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($targetDispatches as $disp)
                                <tr>
                                    <td>{{ $disp['date'] }}</td>
                                    <td>{{ $disp['gdn_number'] }}</td>
                                    <td>{{ $disp['customer'] }}</td>
                                    <td><span class="mw-status info">{{ $disp['status'] }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="mw-empty">No dispatch records to display.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="mw-card">
                <div class="mw-card-heading">
                    <h3>Low Stock Products</h3>
                    <a href="{{ route('inventory.index', ['filter' => 'low_stock']) }}">View all</a>
                </div>
                <div class="mw-table-wrap">
                    <table class="mw-table">
                        <thead>
                            <tr><th>Product</th><th>Current Stock</th><th>Minimum Level</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($targetLowStock as $low)
                                <tr>
                                    <td>{{ $low['product'] }}</td>
                                    <td>{{ $low['current_stock'] }}</td>
                                    <td>{{ $low['min_level'] }}</td>
                                    <td><span class="mw-status danger">Low</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="mw-empty">No low-stock products to display.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>

        </div>
    </section>

    {{-- Alerts and tools --}}
    <section class="mw-bottom-grid">

        <article class="mw-card">
            <div class="mw-card-heading">
                <h3>Alerts and Notifications</h3>
                <a href="{{ route('alerts.index') }}">View all</a>
            </div>
            <div class="mw-alert-list">
                @forelse ($targetAlerts as $alert)
                    <div class="mw-alert-row">
                        <span class="mw-alert-symbol {{ $alert['severity'] === 'danger' ? 'alert-danger' : 'alert-warning' }}">!</span>
                        <div class="mw-alert-info">
                            <strong>{{ $alert['title'] }}</strong>
                            <a href="{{ $alert['link'] }}">View details</a>
                        </div>
                        <small>{{ $alert['time'] }}</small>
                    </div>
                @empty
                    <p class="mw-empty">There are no alerts to display.</p>
                @endforelse
            </div>
        </article>

        <article class="mw-card">
            <div class="mw-card-heading">
                <h3>Quick Navigation</h3>
            </div>
            <div class="mw-quick-links">
                <a href="{{ route('products.index') }}"><span>▦</span><div><strong>Products</strong><small>View product catalogue</small></div></a>
                <a href="{{ route('inventory.index') }}"><span>▤</span><div><strong>Inventory Overview</strong><small>Review stock records</small></div></a>
                <a href="{{ route('batches.index') }}"><span>▥</span><div><strong>Batch Records</strong><small>Review batch information</small></div></a>
                <a href="{{ route('reports.index') }}"><span>▧</span><div><strong>Reports</strong><small>View warehouse reports</small></div></a>
            </div>
        </article>

        <article class="mw-card">
            <div class="mw-card-heading">
                <h3>Download Reports</h3>
                <a href="{{ route('reports.index') }}">View all</a>
            </div>
            <div class="mw-report-list">
                @forelse ($downloadReports as $rep)
                    <div class="mw-report-row">
                        <strong>{{ $rep['title'] }}</strong>
                        <div>
                            <a class="mw-pdf" href="{{ route('reports.pdf', ['report' => $rep['key']]) }}">PDF</a>
                            <a class="mw-csv" href="{{ route('reports.csv', ['report' => $rep['key']]) }}">CSV</a>
                        </div>
                    </div>
                @empty
                    <p class="mw-empty">No reports available.</p>
                @endforelse
            </div>
        </article>

    </section>

</div>
@endsection

@push('scripts')
<style>
.mw-dashboard{display:flex;flex-direction:column;gap:22px;color:#172033}
.mw-hero{background:linear-gradient(120deg,#10243e,#174b70);border-radius:16px;padding:28px 32px;color:white;display:flex;justify-content:space-between;align-items:center;gap:20px;box-shadow:0 8px 24px #10243e18}
.mw-eyebrow{font-size:10px;letter-spacing:2px;font-weight:800;color:#93c5fd}
.mw-hero h1{font-size:26px;color:white;font-weight:800;margin:8px 0}
.mw-hero p{color:#dbeafe;margin:0 0 16px;font-size:13px}
.mw-hero-meta{display:flex;gap:18px;flex-wrap:wrap;color:#e2e8f0;font-size:12px}
.mw-hero-location{display:flex;align-items:center;gap:12px;padding:14px 18px;border:1px solid #ffffff30;background:#ffffff12;border-radius:12px}
.mw-location-icon{font-size:26px;color:#7dd3fc}
.mw-hero-location strong,.mw-hero-location small{display:block}
.mw-hero-location strong{font-size:13px}
.mw-hero-location small{font-size:11px;color:#cbd5e1;margin-top:3px}
.mw-kpis{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:14px}
.mw-kpi{display:flex;align-items:center;gap:12px;background:white;border:1px solid #e5eaf1;border-radius:13px;padding:17px 14px;text-decoration:none;color:inherit;min-width:0;transition:.2s}
.mw-kpi:hover{transform:translateY(-3px);box-shadow:0 8px 20px #17203312;border-color:#bfdbfe}
.mw-kpi-icon{width:43px;height:43px;display:grid;place-items:center;border-radius:12px;font-size:23px;flex-shrink:0}
.mw-kpi-icon.green{background:#dcfce7;color:#15803d}.mw-kpi-icon.orange{background:#ffedd5;color:#c2410c}.mw-kpi-icon.purple{background:#ede9fe;color:#6d28d9}.mw-kpi-icon.teal{background:#ccfbf1;color:#0f766e}.mw-kpi-icon.blue{background:#dbeafe;color:#1d4ed8}
.mw-kpi small,.mw-kpi strong,.mw-kpi div>span{display:block}
.mw-kpi small{font-size:11px;color:#64748b;font-weight:600}
.mw-kpi strong{font-size:23px;margin:4px 0;color:#142238;line-height:1.2}
.mw-kpi strong.mw-manage{font-size:15px;margin:8px 0}
.mw-kpi div>span{font-size:10px;color:#94a3b8}
.mw-section{display:flex;flex-direction:column;gap:12px}
.mw-section-heading{display:flex;justify-content:space-between;align-items:center}
.mw-section-heading h2{font-size:17px;font-weight:800;margin:0;color:#172033}
.mw-section-heading p{font-size:11px;color:#8491a3;margin:4px 0 0}
.mw-analytics{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}
.mw-card{background:white;border:1px solid #e5eaf1;border-radius:13px;padding:18px;min-width:0;box-shadow:0 2px 7px #17203305}
.mw-card-heading{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:15px}
.mw-card-heading h3{font-size:13px;font-weight:800;color:#172033;margin:0}
.mw-card-heading p{font-size:10px;color:#94a3b8;margin:4px 0 0}
.mw-card-heading>a{font-size:11px;color:#2563eb;text-decoration:none;font-weight:700;white-space:nowrap}
.mw-card-heading>a:hover{text-decoration:underline}
.mw-donut-area{display:flex;align-items:center;gap:14px;min-height:155px}
.mw-donut{width:140px;height:140px;position:relative;flex-shrink:0}
.mw-donut-center{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;pointer-events:none}
.mw-donut-center strong{font-size:17px;color:#172033}
.mw-donut-center small{font-size:9px;color:#94a3b8}
.mw-legend{display:flex;flex-direction:column;gap:9px;min-width:0;flex:1}
.mw-legend-row{display:flex;align-items:center;gap:7px;font-size:10px;color:#64748b}
.mw-legend-row strong{margin-left:auto;color:#172033}
.mw-dot{width:8px;height:8px;border-radius:50%;display:inline-block;flex-shrink:0}
.mw-movement-legend{display:flex;gap:14px;font-size:10px;color:#64748b;margin-bottom:8px}
.mw-movement-legend span{display:flex;align-items:center;gap:5px}
.mw-line-chart{height:145px;position:relative}
.mw-record-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
.mw-table-wrap{width:100%;overflow-x:auto}
.mw-table{width:100%;border-collapse:collapse;white-space:nowrap}
.mw-table th{text-align:left;font-size:9px;text-transform:uppercase;letter-spacing:.5px;color:#94a3b8;padding:9px 8px;border-bottom:1px solid #edf1f5}
.mw-table td{font-size:10px;color:#475569;padding:11px 8px;border-bottom:1px solid #f1f4f8}
.mw-table tr:last-child td{border-bottom:0}
.mw-status{display:inline-block;padding:4px 8px;border-radius:20px;font-size:9px;font-weight:700}
.mw-status.success{background:#dcfce7;color:#15803d}.mw-status.info{background:#dbeafe;color:#1d4ed8}.mw-status.purple{background:#f3e8ff;color:#7e22ce}.mw-status.danger{background:#fee2e2;color:#b91c1c}
.mw-bottom-grid{display:grid;grid-template-columns:1.2fr 1fr 1fr;gap:14px;align-items:start}
.mw-alert-list,.mw-quick-links,.mw-report-list{display:flex;flex-direction:column}
.mw-alert-row{display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid #f1f4f8}
.mw-alert-row:last-child{border:0}
.mw-alert-symbol{width:27px;height:27px;display:grid;place-items:center;border-radius:50%;font-weight:800;flex-shrink:0}
.mw-alert-symbol.alert-danger{background:#fee2e2;color:#dc2626}.mw-alert-symbol.alert-warning{background:#ffedd5;color:#c2410c}
.mw-alert-info{display:flex;flex-direction:column;gap:3px;min-width:0;flex:1}
.mw-alert-info strong{font-size:10px;color:#334155}
.mw-alert-info a{font-size:10px;color:#2563eb;text-decoration:none}
.mw-alert-row>small{font-size:9px;color:#94a3b8;white-space:nowrap}
.mw-quick-links a{display:flex;align-items:center;gap:11px;padding:10px 0;border-bottom:1px solid #f1f4f8;text-decoration:none}
.mw-quick-links a:last-child{border:0}
.mw-quick-links a>span{width:32px;height:32px;display:grid;place-items:center;border-radius:9px;background:#eff6ff;color:#2563eb;font-size:18px}
.mw-quick-links strong,.mw-quick-links small{display:block}
.mw-quick-links strong{font-size:11px;color:#334155}
.mw-quick-links small{font-size:9px;color:#94a3b8;margin-top:3px}
.mw-report-row{display:flex;justify-content:space-between;align-items:center;gap:8px;padding:11px 0;border-bottom:1px solid #f1f4f8}
.mw-report-row:last-child{border:0}
.mw-report-row>strong{font-size:10px;color:#475569}
.mw-report-row>div{display:flex;gap:5px}
.mw-report-row a{font-size:9px;font-weight:800;text-decoration:none;padding:5px 7px;border-radius:5px}
.mw-pdf{background:#fee2e2;color:#b91c1c}.mw-csv{background:#dbeafe;color:#1d4ed8}
.mw-empty{font-size:11px;color:#94a3b8;text-align:center;padding:18px 8px}
@media(max-width:1300px){.mw-kpis{grid-template-columns:repeat(3,minmax(0,1fr))}.mw-analytics{grid-template-columns:1fr 1fr}.mw-analytics .mw-card:last-child{grid-column:span 2}}
@media(max-width:950px){.mw-bottom-grid{grid-template-columns:1fr 1fr}.mw-bottom-grid .mw-card:first-child{grid-column:span 2}}
@media(max-width:700px){.mw-hero{align-items:flex-start;flex-direction:column;padding:22px}.mw-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}.mw-analytics,.mw-record-grid{grid-template-columns:1fr}.mw-analytics .mw-card:last-child{grid-column:auto}.mw-bottom-grid{grid-template-columns:1fr}.mw-bottom-grid .mw-card:first-child{grid-column:auto}}
@media(max-width:430px){.mw-kpis{grid-template-columns:1fr}.mw-hero h1{font-size:21px}.mw-donut-area{flex-wrap:wrap;justify-content:center}.mw-legend{width:100%;flex-basis:100%}}

/* ===== DASHBOARD CHART AND LAYOUT FIX ===== */

.mw-dashboard,
.mw-dashboard * {
    box-sizing: border-box;
}

.mw-dashboard {
    width: 100%;
    max-width: 100%;
    min-width: 0;
}

.mw-analytics {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16px;
    width: 100%;
    min-width: 0;
    align-items: stretch;
}

.mw-analytics > .mw-card,
.mw-record-grid > .mw-card,
.mw-bottom-grid > .mw-card {
    min-width: 0;
    max-width: 100%;
    overflow: hidden;
}

.mw-donut-area {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 16px;
    width: 100%;
    min-width: 0;
    min-height: 180px;
}

.mw-donut {
    position: relative;
    width: 155px;
    height: 155px;
    min-width: 155px;
    max-width: 155px;
    flex: 0 0 155px;
    overflow: hidden;
}

.mw-donut canvas {
    position: absolute !important;
    inset: 0;
    display: block;
    width: 100% !important;
    height: 100% !important;
    max-width: 100%;
    max-height: 100%;
}

.mw-donut-center {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    pointer-events: none;
}

.mw-legend {
    flex: 1 1 100px;
    min-width: 0;
    overflow-wrap: anywhere;
}

.mw-movement-legend {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px 14px;
    margin-bottom: 12px;
}

.mw-line-chart {
    position: relative;
    width: 100%;
    height: 210px;
    min-height: 210px;
    max-height: 210px;
    overflow: hidden;
}

.mw-line-chart canvas {
    position: absolute !important;
    inset: 0;
    display: block;
    width: 100% !important;
    height: 100% !important;
    max-width: 100%;
    max-height: 100%;
}

.mw-record-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
    width: 100%;
    min-width: 0;
}

.mw-bottom-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16px;
    width: 100%;
    min-width: 0;
}

.mw-table-wrap {
    width: 100%;
    max-width: 100%;
    overflow-x: auto;
}

@media (max-width: 1100px) {
    .mw-analytics {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .mw-analytics > .mw-card:last-child {
        grid-column: 1 / -1;
    }

    .mw-bottom-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 700px) {
    .mw-analytics,
    .mw-record-grid,
    .mw-bottom-grid {
        grid-template-columns: minmax(0, 1fr);
    }

    .mw-analytics > .mw-card:last-child {
        grid-column: auto;
    }

    .mw-donut-area {
        flex-wrap: wrap;
    }

    .mw-legend {
        flex-basis: 100%;
    }
}

/* ===== END DASHBOARD CHART FIX ===== */


/* FINAL HARD LIMITS FOR DASHBOARD CHARTS */

.mw-dashboard .mw-analytics {
    grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
    align-items: start !important;
}

.mw-dashboard .mw-analytics .mw-card {
    width: 100% !important;
    min-width: 0 !important;
    max-width: 100% !important;
    overflow: hidden !important;
}

.mw-dashboard .mw-donut-area {
    width: 100% !important;
    height: auto !important;
    min-height: 170px !important;
    max-height: 190px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    overflow: hidden !important;
}

.mw-dashboard .mw-donut {
    position: relative !important;
    display: block !important;
    width: 155px !important;
    height: 155px !important;
    min-width: 155px !important;
    max-width: 155px !important;
    flex: 0 0 155px !important;
    overflow: hidden !important;
}

.mw-dashboard #categoryDonutChart,
.mw-dashboard #expiryDonutChart {
    position: absolute !important;
    display: block !important;
    inset: 0 !important;
    width: 155px !important;
    height: 155px !important;
    min-width: 155px !important;
    max-width: 155px !important;
    min-height: 155px !important;
    max-height: 155px !important;
}

.mw-dashboard .mw-donut-center {
    position: absolute !important;
    inset: 0 !important;
    width: 155px !important;
    height: 155px !important;
    pointer-events: none !important;
}

.mw-dashboard .mw-line-chart {
    position: relative !important;
    display: block !important;
    width: 100% !important;
    height: 210px !important;
    min-height: 210px !important;
    max-height: 210px !important;
    overflow: hidden !important;
}

.mw-dashboard #stockMovementChart {
    position: absolute !important;
    inset: 0 !important;
    width: 100% !important;
    height: 210px !important;
    max-height: 210px !important;
}

@media (max-width: 1100px) {
    .mw-dashboard .mw-analytics {
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
    }
}

@media (max-width: 700px) {
    .mw-dashboard .mw-analytics {
        grid-template-columns: 1fr !important;
    }
}

/* END FINAL CHART LIMITS */


/* FINAL HARD LIMITS FOR DASHBOARD CHARTS */

.mw-dashboard .mw-analytics {
    grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
    align-items: start !important;
}

.mw-dashboard .mw-analytics .mw-card {
    width: 100% !important;
    min-width: 0 !important;
    max-width: 100% !important;
    overflow: hidden !important;
}

.mw-dashboard .mw-donut-area {
    width: 100% !important;
    height: auto !important;
    min-height: 170px !important;
    max-height: 190px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    overflow: hidden !important;
}

.mw-dashboard .mw-donut {
    position: relative !important;
    display: block !important;
    width: 155px !important;
    height: 155px !important;
    min-width: 155px !important;
    max-width: 155px !important;
    flex: 0 0 155px !important;
    overflow: hidden !important;
}

.mw-dashboard #categoryDonutChart,
.mw-dashboard #expiryDonutChart {
    position: absolute !important;
    display: block !important;
    inset: 0 !important;
    width: 155px !important;
    height: 155px !important;
    min-width: 155px !important;
    max-width: 155px !important;
    min-height: 155px !important;
    max-height: 155px !important;
}

.mw-dashboard .mw-donut-center {
    position: absolute !important;
    inset: 0 !important;
    width: 155px !important;
    height: 155px !important;
    pointer-events: none !important;
}

.mw-dashboard .mw-line-chart {
    position: relative !important;
    display: block !important;
    width: 100% !important;
    height: 210px !important;
    min-height: 210px !important;
    max-height: 210px !important;
    overflow: hidden !important;
}

.mw-dashboard #stockMovementChart {
    position: absolute !important;
    inset: 0 !important;
    width: 100% !important;
    height: 210px !important;
    max-height: 210px !important;
}

@media (max-width: 1100px) {
    .mw-dashboard .mw-analytics {
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
    }
}

@media (max-width: 700px) {
    .mw-dashboard .mw-analytics {
        grid-template-columns: 1fr !important;
    }
}

/* END FINAL CHART LIMITS */

</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (!window.Chart) return;

    const categoryCanvas = document.getElementById('categoryDonutChart');
    if (categoryCanvas) {
        new Chart(categoryCanvas, {
            type: 'doughnut',
            data: {
                labels: @json(collect($categoryBreakdown)->pluck('name')),
                datasets: [{
                    data: @json(collect($categoryBreakdown)->pluck('quantity')),
                    backgroundColor: @json(collect($categoryBreakdown)->pluck('color')),
                    borderWidth: 0,
                    hoverOffset: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: { legend: { display: false } }
            }
        });
    }

    const movementCanvas = document.getElementById('stockMovementChart');
    if (movementCanvas) {
        new Chart(movementCanvas, {
            type: 'line',
            data: {
                labels: @json($stockMovement7Days['labels']),
                datasets: [
                    {
                        label: 'Received',
                        data: @json($stockMovement7Days['received']),
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16,185,129,.10)',
                        fill: false,
                        tension: .35,
                        pointRadius: 3
                    },
                    {
                        label: 'Issued',
                        data: @json($stockMovement7Days['issued']),
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(37,99,235,.08)',
                        fill: false,
                        tension: .35,
                        pointRadius: 3
                    },
                    {
                        label: 'Put-away',
                        data: @json($stockMovement7Days['put_away']),
                        borderColor: '#f59e0b',
                        backgroundColor: 'rgba(245,158,11,.08)',
                        fill: false,
                        tension: .35,
                        pointRadius: 3
                    },
                    {
                        label: 'Transferred',
                        data: @json($stockMovement7Days['transferred']),
                        borderColor: '#8b5cf6',
                        backgroundColor: 'rgba(139,92,246,.08)',
                        fill: false,
                        tension: .35,
                        pointRadius: 3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { intersect: false, mode: 'index' },
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { color: '#94a3b8', font: { size: 9 } } },
                    y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { color: '#94a3b8', font: { size: 9 } } }
                }
            }
        });
    }

    const expiryCanvas = document.getElementById('expiryDonutChart');
    if (expiryCanvas) {
        new Chart(expiryCanvas, {
            type: 'doughnut',
            data: {
                labels: @json(collect($expiryBreakdown)->pluck('label')),
                datasets: [{
                    data: @json(collect($expiryBreakdown)->pluck('quantity')),
                    backgroundColor: @json(collect($expiryBreakdown)->pluck('color')),
                    borderWidth: 0,
                    hoverOffset: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: { legend: { display: false } }
            }
        });
    }
});
</script>
@endpush
