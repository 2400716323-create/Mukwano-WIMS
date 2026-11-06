@extends('catalog.layout')

@section('title', 'Dashboard')

@section('content')
    <div class="dashboard-wrap">
        <!-- 1. Hero Welcome Banner -->
        <section class="hero-banner" aria-label="Warehouse status overview">
            <div class="hero-content">
                <div class="hero-text">
                    <h1 class="hero-title">Welcome back, {{ auth()->user()->first_name ?? explode(' ', auth()->user()->name)[0] }}!</h1>
                    <p class="hero-subtitle">Here's what's happening with your warehouse today.</p>
                    <div class="hero-badges">
                        <span class="hero-badge">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                            Tuesday, 22 April 2025
                        </span>
                        <span class="hero-badge">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                            Namanve Warehouse
                        </span>
                    </div>
                </div>
                <div class="hero-widget">
                    <div class="hero-location">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#38bdf8" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                        <div>
                            <span class="widget-head">Namanve Warehouse</span>
                            <span class="widget-sub">Kampala, Uganda</span>
                        </div>
                    </div>
                    <div class="hero-weather">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fbbf24" stroke-width="2"><circle cx="12" cy="12" r="4"></circle><path d="M12 2v2"></path><path d="M12 20v2"></path><path d="m4.93 4.93 1.41 1.41"></path><path d="m17.66 17.66 1.41 1.41"></path><path d="M2 12h2"></path><path d="M20 12h2"></path><path d="m6.34 17.66-1.41 1.41"></path><path d="m19.07 4.93-1.41 1.41"></path></svg>
                        <div>
                            <span class="widget-head">24°C</span>
                            <span class="widget-sub">Partly Cloudy</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 2. Four Top KPI Cards -->
        <section class="kpi-grid" aria-label="Key Performance Indicators">
            <!-- Total Products -->
            <a class="kpi-card" href="{{ route('products.index') }}">
                <div class="kpi-icon-wrap" style="background: #dcfce7; color: #16a34a;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                </div>
                <div class="kpi-body">
                    <span class="kpi-title">Total Products</span>
                    <strong class="kpi-num">{{ number_format($metrics['total_products'] ?? 245) }}</strong>
                    <span class="kpi-caption">Active Products</span>
                </div>
            </a>

            <!-- Total Stock Quantity -->
            <a class="kpi-card" href="{{ route('inventory.index') }}">
                <div class="kpi-icon-wrap" style="background: #ffedd5; color: #ea580c;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                </div>
                <div class="kpi-body">
                    <span class="kpi-title">Total Stock Quantity</span>
                    <strong class="kpi-num">{{ number_format($metrics['stock_quantity'] ?? 125480) }}</strong>
                    <span class="kpi-caption">Units</span>
                </div>
            </a>

            <!-- Active Batches -->
            <a class="kpi-card" href="{{ route('batches.index') }}">
                <div class="kpi-icon-wrap" style="background: #ccfbf1; color: #0d9488;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                </div>
                <div class="kpi-body">
                    <span class="kpi-title">Active Batches</span>
                    <strong class="kpi-num">{{ number_format($metrics['active_batches'] ?? 612) }}</strong>
                    <span class="kpi-caption">Batches</span>
                </div>
            </a>

            <!-- Warehouse Locations -->
            <a class="kpi-card" href="{{ route('locations.index') }}">
                <div class="kpi-icon-wrap" style="background: #dbeafe; color: #2563eb;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                </div>
                <div class="kpi-body">
                    <span class="kpi-title">Warehouse Locations</span>
                    <strong class="kpi-num">{{ number_format($metrics['warehouse_locations'] ?? 48) }}</strong>
                    <span class="kpi-caption">Locations</span>
                </div>
            </a>
        </section>

        <!-- 3. Analytics Charts Row (3 Cards) -->
        <section class="analytics-grid" aria-label="Warehouse Analytics and Trends">
            <!-- Stock by Category -->
            <div class="card chart-box">
                <div class="chart-header">
                    <h2>Stock by Category</h2>
                    <select class="chart-select" aria-label="Category filter">
                        <option>All Categories</option>
                        @foreach ($categories as $cat)
                            <option>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="chart-content doughnut-layout">
                    <div class="doughnut-canvas-wrap">
                        <canvas id="categoryDonutChart"></canvas>
                        <div class="chart-center-label">
                            <span class="center-val">{{ number_format($metrics['stock_quantity'] ?? 125480) }}</span>
                            <span class="center-sub">Total Quantity</span>
                        </div>
                    </div>
                    <div class="chart-legend-list">
                        @foreach ($categoryBreakdown as $catItem)
                            <div class="legend-row">
                                <span class="legend-dot" style="background: {{ $catItem['color'] }};"></span>
                                <span class="legend-name">{{ $catItem['name'] }}</span>
                                <span class="legend-pct">{{ $catItem['percentage'] }}%</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Stock Movement (Last 7 Days) -->
            <div class="card chart-box">
                <div class="chart-header">
                    <h2>Stock Movement <small>(Last 7 Days)</small></h2>
                    <div class="chart-header-actions">
                        <div class="movement-legend-inline">
                            <span><span class="legend-dot" style="background: #10b981;"></span> Received</span>
                            <span><span class="legend-dot" style="background: #2563eb;"></span> Issued</span>
                        </div>
                        <select class="chart-select" aria-label="Warehouse filter">
                            <option>All Warehouses</option>
                            <option>Namanve Warehouse</option>
                        </select>
                    </div>
                </div>
                <div class="chart-content line-layout">
                    <canvas id="stockMovementChart"></canvas>
                </div>
            </div>

            <!-- Expiry Status -->
            <div class="card chart-box">
                <div class="chart-header">
                    <h2>Expiry Status</h2>
                </div>
                <div class="chart-content doughnut-layout">
                    <div class="doughnut-canvas-wrap">
                        <canvas id="expiryDonutChart"></canvas>
                        <div class="chart-center-label">
                            <span class="center-val">{{ number_format($metrics['active_batches'] ?? 612) }}</span>
                            <span class="center-sub">Batches</span>
                        </div>
                    </div>
                    <div class="chart-legend-list">
                        @foreach ($expiryBreakdown as $expItem)
                            <div class="legend-row">
                                <span class="legend-dot" style="background: {{ $expItem['color'] }};"></span>
                                <span class="legend-name">{{ $expItem['label'] }}</span>
                                <span class="legend-pct">{{ $expItem['percentage'] }}%</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <!-- 4. Main 2-Column Split: Tables (Left) & Widgets (Right) -->
        <div class="main-dashboard-columns">
            <!-- Left Column: 2x2 Data Tables Grid -->
            <div class="tables-column">
                <!-- Row 1: Movements & Receipts -->
                <div class="tables-row-grid">
                    <!-- Recent Stock Movements -->
                    <div class="card table-card">
                        <div class="table-card-head">
                            <h2>Recent Stock Movements</h2>
                            <a href="{{ route('inventory.ledger') }}" class="view-all-link">View All</a>
                        </div>
                        <div class="table-wrap">
                            <table class="clean-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Product</th>
                                        <th>Type</th>
                                        <th>Quantity</th>
                                        <th>Location</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($targetMovements as $mov)
                                        <tr>
                                            <td class="text-muted text-nowrap">{{ $mov['date'] }}</td>
                                            <td>
                                                <div class="product-cell">
                                                    <div class="prod-thumb-mini">
                                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>
                                                    </div>
                                                    <span class="prod-name-text">{{ $mov['product'] }}</span>
                                                </div>
                                            </td>
                                            <td>
                                                @if ($mov['badge'] === 'received')
                                                    <span class="badge badge-success">Received</span>
                                                @elseif ($mov['badge'] === 'issued')
                                                    <span class="badge badge-info">Issued</span>
                                                @else
                                                    <span class="badge badge-purple">Transferred</span>
                                                @endif
                                            </td>
                                            <td class="font-bold {{ str_starts_with($mov['quantity'], '+') ? 'text-success' : 'text-slate' }}">{{ $mov['quantity'] }}</td>
                                            <td><span class="location-pill">{{ $mov['location'] }}</span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Recent Goods Receipts -->
                    <div class="card table-card">
                        <div class="table-card-head">
                            <h2>Recent Goods Receipts</h2>
                            <a href="{{ route('goods-receipts.index') }}" class="view-all-link">View All</a>
                        </div>
                        <div class="table-wrap">
                            <table class="clean-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>GRN No.</th>
                                        <th>Supplier</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($targetReceipts as $rec)
                                        <tr>
                                            <td class="text-muted text-nowrap">{{ $rec['date'] }}</td>
                                            <td><a href="{{ route('goods-receipts.index') }}" class="code-link">{{ $rec['receipt_number'] }}</a></td>
                                            <td class="text-dark font-medium">{{ $rec['supplier'] }}</td>
                                            <td><span class="badge badge-success">{{ $rec['status'] }}</span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Row 2: Dispatches & Low Stock -->
                <div class="tables-row-grid">
                    <!-- Recent Dispatches -->
                    <div class="card table-card">
                        <div class="table-card-head">
                            <h2>Recent Dispatches</h2>
                            <a href="{{ route('dispatch-requests.index') }}" class="view-all-link">View All</a>
                        </div>
                        <div class="table-wrap">
                            <table class="clean-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>GDN No.</th>
                                        <th>Customer / Department</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($targetDispatches as $disp)
                                        <tr>
                                            <td class="text-muted text-nowrap">{{ $disp['date'] }}</td>
                                            <td><a href="{{ route('dispatch-requests.index') }}" class="code-link">{{ $disp['gdn_number'] }}</a></td>
                                            <td class="text-dark font-medium">{{ $disp['customer'] }}</td>
                                            <td><span class="badge badge-info">{{ $disp['status'] }}</span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Low Stock Products -->
                    <div class="card table-card">
                        <div class="table-card-head">
                            <h2>Low Stock Products</h2>
                            <a href="{{ route('inventory.index', ['filter' => 'low_stock']) }}" class="view-all-link">View All</a>
                        </div>
                        <div class="table-wrap">
                            <table class="clean-table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Current Stock</th>
                                        <th>Min Level</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($targetLowStock as $low)
                                        <tr>
                                            <td>
                                                <div class="product-cell">
                                                    <div class="prod-thumb-mini" style="color: {{ $low['color'] }};">
                                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                                                    </div>
                                                    <span class="prod-name-text">{{ $low['product'] }}</span>
                                                </div>
                                            </td>
                                            <td class="font-bold text-dark">{{ $low['current_stock'] }}</td>
                                            <td class="text-muted">{{ $low['min_level'] }}</td>
                                            <td><span class="badge badge-danger">Low</span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Alerts, Quick Actions, Download Reports, Install App -->
            <div class="sidebar-column">
                <!-- Alerts Widget -->
                <div class="card widget-card">
                    <div class="widget-head-flex">
                        <div class="widget-title-iconic">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                            <h2>Alerts</h2>
                        </div>
                        <a href="{{ route('alerts.index') }}" class="view-all-link">View All</a>
                    </div>
                    <div class="alerts-stack">
                        @foreach ($targetAlerts as $alert)
                            <div class="alert-row">
                                <div class="alert-icon-wrap alert-{{ $alert['severity'] }}">
                                    @if ($alert['icon'] === 'alert')
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                                    @elseif ($alert['icon'] === 'clock')
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                    @elseif ($alert['icon'] === 'order')
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                                    @else
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
                                    @endif
                                </div>
                                <div class="alert-content">
                                    <div class="alert-title-text">{{ $alert['title'] }}</div>
                                    <a href="{{ $alert['link'] }}" class="alert-action-link">View details</a>
                                </div>
                                <span class="alert-time">{{ $alert['time'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Quick Actions Widget -->
                <div class="card widget-card">
                    <div class="widget-head-flex">
                        <h2>Quick Actions</h2>
                    </div>
                    <div class="quick-actions-2x2">
                        <a href="{{ route('goods-receipts.create') }}" class="action-tile">
                            <div class="action-icon" style="background: #dcfce7; color: #16a34a;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                            </div>
                            <div class="action-text">
                                <strong>Receive Goods</strong>
                                <span>Create GRN</span>
                            </div>
                        </a>
                        <a href="{{ route('dispatch-requests.create') }}" class="action-tile">
                            <div class="action-icon" style="background: #dbeafe; color: #2563eb;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
                            </div>
                            <div class="action-text">
                                <strong>Dispatch Goods</strong>
                                <span>Create GDN</span>
                            </div>
                        </a>
                        <a href="{{ route('stock-transfers.create') }}" class="action-tile">
                            <div class="action-icon" style="background: #f3e8ff; color: #7c3aed;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="17 1 21 5 17 9"></polyline><path d="M3 11V9a4 4 0 0 1 4-4h14"></path><polyline points="7 23 3 19 7 15"></polyline><path d="M21 13v2a4 4 0 0 1-4 4H3"></path></svg>
                            </div>
                            <div class="action-text">
                                <strong>Transfer Stock</strong>
                                <span>Between Locations</span>
                            </div>
                        </a>
                        <a href="{{ route('reports.index') }}" class="action-tile">
                            <div class="action-icon" style="background: #ffedd5; color: #ea580c;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                            </div>
                            <div class="action-text">
                                <strong>View Reports</strong>
                                <span>Download & Print</span>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Download Reports Widget -->
                <div class="card widget-card">
                    <div class="widget-head-flex">
                        <h2>Download Reports</h2>
                        <a href="{{ route('reports.index') }}" class="view-all-link">View All</a>
                    </div>
                    <div class="reports-download-list">
                        @foreach ($downloadReports as $rep)
                            <div class="report-download-row">
                                <div class="report-lead">
                                    <div class="report-type-icon" style="color: {{ $rep['color'] }};">
                                        @if ($rep['icon'] === 'truck')
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
                                        @elseif ($rep['icon'] === 'arrows')
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="17 1 21 5 17 9"></polyline><path d="M3 11V9a4 4 0 0 1 4-4h14"></path><polyline points="7 23 3 19 7 15"></polyline><path d="M21 13v2a4 4 0 0 1-4 4H3"></path></svg>
                                        @elseif ($rep['icon'] === 'warning')
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                                        @elseif ($rep['icon'] === 'chart')
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                                        @else
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                                        @endif
                                    </div>
                                    <span class="report-name-label">{{ $rep['title'] }}</span>
                                </div>
                                <div class="report-btns">
                                    <a href="{{ route('reports.pdf', ['report' => $rep['key']]) }}" class="btn-badge-pdf" title="Download PDF">PDF</a>
                                    <a href="{{ route('reports.csv', ['report' => $rep['key']]) }}" class="btn-badge-csv" title="Download CSV">CSV</a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Install as App Widget -->
                <div class="card widget-card install-app-card">
                    <div class="install-card-lead">
                        <div class="install-icon-wrap">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg>
                        </div>
                        <div class="install-text">
                            <strong>Install as App</strong>
                            <span>Get quick access on your device</span>
                        </div>
                    </div>
                    <button type="button" class="btn-install" onclick="triggerPwaInstall()">Install</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <style>
        .dashboard-wrap {
            display: flex;
            flex-direction: column;
            gap: 16px;
            width: 100%;
            max-width: 100%;
        }

        /* 1. Hero Welcome Banner */
        .hero-banner {
            border-radius: 12px;
            overflow: hidden;
            background-color: #0f172a;
            background-image: linear-gradient(90deg, rgba(15, 23, 42, 0.94) 0%, rgba(15, 23, 42, 0.82) 55%, rgba(15, 23, 42, 0.92) 100%),
                        url('https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?auto=format&fit=crop&w=1600&q=80');
            background-position: center;
            background-size: cover;
            background-repeat: no-repeat;
            color: #ffffff;
            padding: 20px 24px;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.12);
            width: 100%;
        }

        .hero-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
        }

        .hero-title {
            margin: 0 0 4px;
            font-size: 24px;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -0.02em;
        }

        .hero-subtitle {
            margin: 0 0 12px;
            font-size: 13px;
            color: #cbd5e1;
        }

        .hero-badges {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 9999px;
            padding: 4px 10px;
            font-size: 11.5px;
            font-weight: 500;
            color: #f8fafc;
        }

        .hero-widget {
            display: flex;
            align-items: center;
            gap: 20px;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 10px;
            padding: 10px 16px;
        }

        .hero-location, .hero-weather {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .widget-head {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: #ffffff;
            line-height: 1.2;
        }

        .widget-sub {
            display: block;
            font-size: 11px;
            color: #94a3b8;
            line-height: 1.2;
        }

        /* 2. Top KPI Cards Grid */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            width: 100%;
        }

        .kpi-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px 18px;
            display: flex;
            align-items: center;
            gap: 14px;
            text-decoration: none;
            color: inherit;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
            transition: all 0.15s ease;
        }

        .kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(0,0,0,0.06);
            text-decoration: none;
            border-color: #cbd5e1;
        }

        .kpi-icon-wrap {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .kpi-body {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .kpi-title {
            font-size: 12.5px;
            font-weight: 600;
            color: #64748b;
        }

        .kpi-num {
            font-size: 24px;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.15;
            letter-spacing: -0.02em;
        }

        .kpi-caption {
            font-size: 11.5px;
            color: #94a3b8;
        }

        /* 3. Analytics Charts Grid */
        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
            width: 100%;
        }

        .chart-box {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px 18px;
            display: flex;
            flex-direction: column;
        }

        .chart-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
            gap: 8px;
        }

        .chart-header h2 {
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
            letter-spacing: -0.01em;
        }

        .chart-header h2 small {
            font-size: 11.5px;
            color: #94a3b8;
            font-weight: 500;
        }

        .chart-header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .movement-legend-inline {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 11px;
            font-weight: 600;
            color: #64748b;
        }

        .movement-legend-inline span {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .chart-select {
            font-size: 11.5px;
            padding: 3px 8px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #475569;
            cursor: pointer;
        }

        .doughnut-layout {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            flex: 1;
        }

        .doughnut-canvas-wrap {
            position: relative;
            width: 140px;
            height: 140px;
            flex-shrink: 0;
            margin: auto;
        }

        .chart-center-label {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            pointer-events: none;
            text-align: center;
        }

        .center-val {
            font-size: 15px;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.1;
        }

        .center-sub {
            font-size: 10px;
            color: #94a3b8;
            text-transform: capitalize;
        }

        .chart-legend-list {
            display: flex;
            flex-direction: column;
            gap: 7px;
            flex: 1;
        }

        .legend-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 11.5px;
            color: #475569;
        }

        .legend-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 6px;
            flex-shrink: 0;
        }

        .legend-name {
            flex: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .legend-pct {
            font-weight: 700;
            color: #0f172a;
            margin-left: 8px;
        }

        .line-layout {
            height: 140px;
            position: relative;
            width: 100%;
        }

        /* 4. Main 2-Column Split */
        .main-dashboard-columns {
            display: grid;
            grid-template-columns: 2.2fr 1fr;
            gap: 14px;
            align-items: start;
            width: 100%;
        }

        .tables-column {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .tables-row-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .table-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px 18px;
            display: flex;
            flex-direction: column;
        }

        .table-card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .table-card-head h2 {
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
        }

        .view-all-link {
            font-size: 11.5px;
            font-weight: 600;
            color: #2563eb;
            text-decoration: none;
        }

        .view-all-link:hover { text-decoration: underline; }

        .clean-table {
            width: 100%;
            border-collapse: collapse;
        }

        .clean-table th {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            color: #94a3b8;
            border-bottom: 1px solid #f1f5f9;
            padding: 7px 8px;
            text-align: left;
            background: #ffffff;
        }

        .clean-table td {
            font-size: 12px;
            padding: 9px 8px;
            border-bottom: 1px solid #f8fafc;
            vertical-align: middle;
        }

        .clean-table tr:last-child td { border-bottom: 0; }

        .product-cell {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .prod-thumb-mini {
            width: 22px;
            height: 22px;
            border-radius: 4px;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .prod-name-text {
            font-weight: 600;
            color: #1e293b;
            font-size: 11.5px;
            max-width: 130px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .badge {
            display: inline-block;
            font-size: 10.5px;
            font-weight: 600;
            padding: 2px 7px;
            border-radius: 9999px;
            text-align: center;
            white-space: nowrap;
        }

        .badge-success { background: #dcfce7; color: #16a34a; }
        .badge-info { background: #dbeafe; color: #2563eb; }
        .badge-purple { background: #f3e8ff; color: #7c3aed; }
        .badge-danger { background: #fee2e2; color: #dc2626; }

        .location-pill {
            display: inline-block;
            background: #f1f5f9;
            color: #475569;
            font-size: 10.5px;
            font-weight: 600;
            padding: 2px 6px;
            border-radius: 4px;
        }

        .code-link {
            font-weight: 600;
            color: #2563eb;
            font-size: 11.5px;
            text-decoration: none;
        }

        .code-link:hover { text-decoration: underline; }

        .text-muted { color: #94a3b8; font-size: 11.5px; }
        .text-dark { color: #0f172a; }
        .text-success { color: #16a34a; }
        .text-slate { color: #475569; }
        .font-bold { font-weight: 700; }
        .font-medium { font-weight: 500; font-size: 11.5px; }
        .text-nowrap { white-space: nowrap; }

        /* Right Column Widgets */
        .sidebar-column {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .widget-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px 18px;
            display: flex;
            flex-direction: column;
        }

        .widget-head-flex {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .widget-head-flex h2 {
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
        }

        .widget-title-iconic {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .alerts-stack {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .alert-row {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 4px 0;
            border-bottom: 1px solid #f8fafc;
        }

        .alert-row:last-child { border-bottom: 0; }

        .alert-icon-wrap {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .alert-warning { background: #ffedd5; color: #ea580c; }
        .alert-danger { background: #fee2e2; color: #dc2626; }

        .alert-content {
            display: flex;
            flex-direction: column;
            flex: 1;
            min-width: 0;
        }

        .alert-title-text {
            font-size: 11.5px;
            font-weight: 600;
            color: #1e293b;
            line-height: 1.3;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .alert-action-link {
            font-size: 10.5px;
            color: #2563eb;
            text-decoration: none;
        }

        .alert-action-link:hover { text-decoration: underline; }

        .alert-time {
            font-size: 10.5px;
            color: #94a3b8;
            white-space: nowrap;
        }

        /* Quick Actions Grid */
        .quick-actions-2x2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .action-tile {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            color: inherit;
            background: #ffffff;
            transition: all 0.15s ease;
        }

        .action-tile:hover {
            border-color: #cbd5e1;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.04);
            text-decoration: none;
        }

        .action-icon {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .action-text {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
        }

        .action-text strong {
            font-size: 11.5px;
            color: #0f172a;
        }

        .action-text span {
            font-size: 9.5px;
            color: #94a3b8;
        }

        /* Download Reports List */
        .reports-download-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .report-download-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 3px 0;
            border-bottom: 1px solid #f8fafc;
        }

        .report-download-row:last-child { border-bottom: 0; }

        .report-lead {
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 1;
            min-width: 0;
        }

        .report-name-label {
            font-size: 11.5px;
            font-weight: 600;
            color: #1e293b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .report-btns {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .btn-badge-pdf {
            font-size: 9.5px;
            font-weight: 700;
            color: #ef4444;
            background: #fee2e2;
            padding: 2px 6px;
            border-radius: 4px;
            text-decoration: none;
        }

        .btn-badge-csv {
            font-size: 9.5px;
            font-weight: 700;
            color: #2563eb;
            background: #dbeafe;
            padding: 2px 6px;
            border-radius: 4px;
            text-decoration: none;
        }

        .btn-badge-pdf:hover, .btn-badge-csv:hover { opacity: 0.85; text-decoration: none; }

        /* Install App Card */
        .install-app-card {
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
        }

        .install-card-lead {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .install-icon-wrap {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: #eff6ff;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .install-text {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
        }

        .install-text strong {
            font-size: 12px;
            color: #0f172a;
        }

        .install-text span {
            font-size: 10.5px;
            color: #94a3b8;
        }

        .btn-install {
            background: #eff6ff;
            color: #2563eb;
            border: 1px solid #bfdbfe;
            border-radius: 6px;
            font-size: 11.5px;
            font-weight: 600;
            padding: 5px 12px;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .btn-install:hover {
            background: #2563eb;
            color: #ffffff;
        }

        @media (max-width: 1200px) {
            .analytics-grid { grid-template-columns: 1fr; }
            .main-dashboard-columns { grid-template-columns: 1fr; }
            .kpi-grid { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 768px) {
            .kpi-grid { grid-template-columns: 1fr; }
            .tables-row-grid { grid-template-columns: 1fr; }
            .hero-content { flex-direction: column; align-items: flex-start; }
            .hero-widget { width: 100%; justify-content: space-between; }
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <script>
        let pwaInstallPrompt = null;
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            pwaInstallPrompt = e;
            const globalInstallBtn = document.getElementById('install-app');
            if (globalInstallBtn) globalInstallBtn.removeAttribute('hidden');
        });

        window.triggerPwaInstall = async function() {
            if (pwaInstallPrompt) {
                pwaInstallPrompt.prompt();
                const choice = await pwaInstallPrompt.userChoice;
                if (choice.outcome === 'accepted') {
                    pwaInstallPrompt = null;
                }
            } else {
                alert('Mukwano WIMS is progressive-web-app ready. You can install it from your browser address bar icon or menu.');
            }
        };

        window.addEventListener('DOMContentLoaded', () => {
            if (!window.Chart) return;

            // 1. Stock by Category Donut Chart
            const catCtx = document.getElementById('categoryDonutChart');
            if (catCtx) {
                const catLabels = @json(collect($categoryBreakdown)->pluck('name'));
                const catData = @json(collect($categoryBreakdown)->pluck('quantity'));
                const catColors = @json(collect($categoryBreakdown)->pluck('color'));

                new Chart(catCtx, {
                    type: 'doughnut',
                    data: {
                        labels: catLabels,
                        datasets: [{
                            data: catData,
                            backgroundColor: catColors,
                            borderWidth: 0,
                            hoverOffset: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '72%',
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: function(ctx) {
                                        return ' ' + ctx.label + ': ' + Number(ctx.raw).toLocaleString() + ' units';
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // 2. Stock Movement Last 7 Days Area Chart
            const movCtx = document.getElementById('stockMovementChart');
            if (movCtx) {
                const movLabels = @json($stockMovement7Days['labels']);
                const recData = @json($stockMovement7Days['received']);
                const issData = @json($stockMovement7Days['issued']);

                new Chart(movCtx, {
                    type: 'line',
                    data: {
                        labels: movLabels,
                        datasets: [
                            {
                                label: 'Received',
                                data: recData,
                                borderColor: '#10b981',
                                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                fill: true,
                                tension: 0.35,
                                pointRadius: 3,
                                pointHoverRadius: 5
                            },
                            {
                                label: 'Issued',
                                data: issData,
                                borderColor: '#2563eb',
                                backgroundColor: 'rgba(37, 99, 235, 0.08)',
                                fill: true,
                                tension: 0.35,
                                pointRadius: 3,
                                pointHoverRadius: 5
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: { font: { size: 10 }, color: '#94a3b8' }
                            },
                            y: {
                                beginAtZero: true,
                                grid: { color: '#f1f5f9' },
                                ticks: { font: { size: 10 }, color: '#94a3b8' }
                            }
                        }
                    }
                });
            }

            // 3. Expiry Status Donut Chart
            const expCtx = document.getElementById('expiryDonutChart');
            if (expCtx) {
                const expLabels = @json(collect($expiryBreakdown)->pluck('label'));
                const expData = @json(collect($expiryBreakdown)->pluck('quantity'));
                const expColors = @json(collect($expiryBreakdown)->pluck('color'));

                new Chart(expCtx, {
                    type: 'doughnut',
                    data: {
                        labels: expLabels,
                        datasets: [{
                            data: expData,
                            backgroundColor: expColors,
                            borderWidth: 0,
                            hoverOffset: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '72%',
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: function(ctx) {
                                        return ' ' + ctx.label + ': ' + Number(ctx.raw).toLocaleString() + ' batches';
                                    }
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>
@endpush
