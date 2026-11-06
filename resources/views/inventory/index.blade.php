@extends('catalog.layout')

@section('title', 'Stock and batches')

@section('breadcrumbs')
    <span class="breadcrumbs-separator">/</span>
    <span style="color: #0f172a; font-weight: 600;">Stock & Batches</span>
@endsection

@section('content')
    <!-- Inline Breadcrumbs Navigation -->
    <nav style="display: flex; align-items: center; gap: 8px; font-size: 12.5px; color: #64748b; margin-bottom: 14px;" aria-label="Breadcrumb">
        <a href="{{ route('dashboard') }}" style="color: #64748b; text-decoration: none; display: flex; align-items: center; gap: 4px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
            <span>Dashboard</span>
        </a>
        <span style="color: #cbd5e1;">/</span>
        <span style="color: #0f172a; font-weight: 600;">Stock & Batches</span>
    </nav>

    <!-- Unified Page Header -->
    <div class="page-header-card">
        <div class="page-title-wrap">
            <h1>Stock & Batches</h1>
            <p>Real-time bin balances, lot allocations, expiry monitoring, and direct relocation.</p>
        </div>
        <div class="page-actions-wrap">
            <a class="button secondary" href="{{ route('inventory.enquiry') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <span>Stock Enquiry</span>
            </a>
            <a class="button secondary" href="{{ route('inventory.ledger') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                <span>Movement Ledger</span>
            </a>
            <a class="button" href="{{ route('stock-transfers.create') }}" style="background: #2563eb;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
                <span>Transfer Stock</span>
            </a>
            <a class="button secondary" href="{{ route('goods-receipts.create') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                <span>Receive Goods</span>
            </a>
        </div>
    </div>

    <!-- 4 Top KPI Cards -->
    <div class="stat-grid-4">
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(37, 99, 235, 0.12); color: #2563eb;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Total Stock Units</span>
                <span class="stat-num">{{ number_format($metrics['total_units'] ?? 0) }}</span>
                <span class="stat-sub">Across all warehouse bins</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(16, 185, 129, 0.12); color: #10b981;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Active Batches</span>
                <span class="stat-num">{{ number_format($metrics['active_batches'] ?? 0) }}</span>
                <span class="stat-sub">Positive inventory lots</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(245, 158, 11, 0.12); color: #f59e0b;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Occupied Bins</span>
                <span class="stat-num">{{ number_format($metrics['occupied_bins'] ?? 0) }}</span>
                <span class="stat-sub">Active storage points</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(124, 58, 237, 0.12); color: #7c3aed;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Active SKUs</span>
                <span class="stat-num">{{ number_format($metrics['total_skus'] ?? 0) }}</span>
                <span class="stat-sub">Distinct products on hand</span>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="filter-card">
        <form method="GET" action="{{ route('inventory.index') }}" style="display: flex; align-items: center; gap: 8px; width: 100%; flex-wrap: wrap; margin: 0;">
            <div style="position: relative; flex: 1; min-width: 220px;">
                <svg style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none;" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input id="stock-search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search SKU, product, batch or bin..." aria-label="Search inventory" style="width: 100%; font-size: 12px; padding: 6px 12px 6px 30px;">
            </div>
            <select id="stock-product" name="product_id" style="font-size: 12px; padding: 6px 10px;">
                <option value="">All Products</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}" @selected(($filters['product_id'] ?? '') == $product->id)>{{ $product->sku }} — {{ $product->name }}</option>
                @endforeach
            </select>
            <select id="stock-category" name="category_id" style="font-size: 12px; padding: 6px 10px;">
                <option value="">All Categories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected(($filters['category_id'] ?? '') == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            <select id="stock-location" name="location_id" style="font-size: 12px; padding: 6px 10px;">
                <option value="">All Bins</option>
                @foreach ($locations as $location)
                    <option value="{{ $location->id }}" @selected(($filters['location_id'] ?? '') == $location->id)>{{ $location->code }} — {{ $location->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="button" style="padding: 6px 14px; font-size: 12px;">Filter</button>
            @if (!empty($filters['search']) || !empty($filters['product_id']) || !empty($filters['category_id']) || !empty($filters['location_id']) || !empty($filters['batch_id']))
                <a class="button secondary" href="{{ route('inventory.index') }}" style="padding: 6px 12px; font-size: 12px;">Clear</a>
            @endif
        </form>
    </div>

    <!-- Modern Table Card: Current Balances -->
    <div class="modern-table-card">
        <div style="padding: 14px 18px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between;">
            <h2 style="margin: 0; font-size: 15px; color: #0f172a;">Bin Balances & Put-Away</h2>
            <span style="font-size: 12px; color: #64748b;">Showing {{ $balances->count() }} of {{ $balances->total() }} records</span>
        </div>
        <table class="clean-table">
            <thead>
                <tr>
                    <th style="width: 24%;">Product / SKU</th>
                    <th style="width: 14%;">Batch No</th>
                    <th style="width: 12%;">Expiry Status</th>
                    <th style="width: 10%;">Expiry Date</th>
                    <th style="width: 12%;">Location Bin</th>
                    <th style="width: 10%; text-align: right;">Balance</th>
                    <th style="width: 18%; text-align: right;">Direct Relocate</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($balances as $balance)
                <tr>
                    <td>
                        <div style="display: flex; flex-direction: column;">
                            <a href="{{ route('products.show', $balance->batch->product) }}" style="font-weight: 700; color: #0f172a; text-decoration: none;">
                                {{ $balance->batch->product->name }}
                            </a>
                            <span class="pill-code" style="width: fit-content; margin-top: 2px;">{{ $balance->batch->product->sku }}</span>
                        </div>
                    </td>
                    <td>
                        <a href="{{ route('batches.show', $balance->batch) }}" style="font-family: monospace; font-weight: 700; color: #2563eb; text-decoration: none;">
                            {{ $balance->batch->batch_number }}
                        </a>
                    </td>
                    <td>
                        @php
                            $expStatus = $balance->batch->expiry_status;
                            $expClass = match($expStatus) {
                                'good' => 'badge-success',
                                'expiring_soon' => 'badge-warning',
                                'expired' => 'badge-danger',
                                default => 'badge-info',
                            };
                        @endphp
                        <span class="{{ $expClass }}">{{ ucwords(str_replace('_', ' ', $expStatus)) }}</span>
                    </td>
                    <td>
                        <span style="color: #64748b; font-size: 12px;">{{ $balance->batch->expires_at?->format('d M Y') ?? '—' }}</span>
                    </td>
                    <td>
                        <a href="{{ route('locations.show', $balance->location) }}" style="font-weight: 600; color: #334155; text-decoration: none;">
                            {{ $balance->location->code }}
                        </a>
                        <small style="display: block; color: #94a3b8; font-size: 11px;">{{ $balance->location->name }}</small>
                    </td>
                    <td style="text-align: right; font-weight: 700; color: #0f172a; font-size: 13.5px;">
                        {{ number_format($balance->quantity) }} <span style="font-size: 11px; font-weight: normal; color: #64748b;">{{ $balance->batch->product->unit }}</span>
                    </td>
                    <td style="text-align: right;">
                        @if ($destinationBins->where('id', '!=', $balance->location_id)->isNotEmpty())
                            <form class="inline-form" method="POST" action="{{ route('stock-balances.put-away', $balance) }}" style="justify-content: flex-end; margin: 0;">
                                @csrf
                                <input type="hidden" name="idempotency_key" value="{{ \Illuminate\Support\Str::uuid() }}">
                                <input name="quantity" type="number" step="0.001" min="0.001" max="{{ $balance->quantity }}" value="{{ $balance->quantity }}" required aria-label="Quantity to move" style="width: 65px; padding: 3px 6px; font-size: 11px;">
                                <select name="destination_location_id" required aria-label="Put-away destination" style="max-width: 110px; padding: 3px 6px; font-size: 11px;">
                                    <option value="">To Bin...</option>
                                    @foreach ($destinationBins->where('id', '!=', $balance->location_id) as $bin)
                                        <option value="{{ $bin->id }}">{{ $bin->code }}</option>
                                    @endforeach
                                </select>
                                <button class="button secondary" type="submit" style="padding: 4px 8px; font-size: 11px;">Move</button>
                            </form>
                        @else
                            <span style="color: #94a3b8;">—</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 36px 20px; color: #64748b;">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" style="margin-bottom: 8px;"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                        <p style="margin: 0; font-weight: 600; color: #334155;">No stock balances match your search criteria</p>
                        <small style="color: #94a3b8;">Try adjusting the filter options above or receive new inventory.</small>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div style="margin-top: 14px;">
        {{ $balances->links() }}
    </div>

    <!-- Product Aggregations Card -->
    <div class="modern-table-card" style="margin-top: 20px;">
        <div style="padding: 14px 18px; border-bottom: 1px solid #f1f5f9;">
            <h2 style="margin: 0; font-size: 15px; color: #0f172a;">Stock overview by product</h2>
        </div>
        <table class="clean-table">
            <thead>
                <tr>
                    <th style="width: 20%;">SKU</th>
                    <th style="width: 50%;">Product Name</th>
                    <th style="width: 15%; text-align: right;">Total Balance</th>
                    <th style="width: 15%;">Unit of Measure</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($productTotals as $productTotal)
                <tr>
                    <td><span class="pill-code">{{ $productTotal->sku }}</span></td>
                    <td>
                        <a href="{{ route('products.show', $productTotal->id) }}" style="font-weight: 600; color: #0f172a; text-decoration: none;">
                            {{ $productTotal->name }}
                        </a>
                    </td>
                    <td style="text-align: right; font-weight: 700; color: #0f172a; font-size: 13.5px;">{{ number_format($productTotal->quantity) }}</td>
                    <td><span style="color: #64748b; font-size: 12px;">{{ $productTotal->unit }}</span></td>
                </tr>
            @empty
                <tr><td colspan="4" style="text-align: center; padding: 20px; color: #64748b;">No product aggregate data available.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
