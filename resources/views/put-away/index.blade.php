@extends('catalog.layout')

@section('title', 'Put-away Tasks')

@section('breadcrumbs')
    <span class="breadcrumbs-separator">/</span>
    <span style="color: #0f172a; font-weight: 600;">Put-away Tasks</span>
@endsection

@section('content')
    <!-- Inline Breadcrumbs Navigation -->
    <nav style="display: flex; align-items: center; gap: 8px; font-size: 12.5px; color: #64748b; margin-bottom: 14px;" aria-label="Breadcrumb">
        <a href="{{ route('dashboard') }}" style="color: #64748b; text-decoration: none; display: flex; align-items: center; gap: 4px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
            <span>Dashboard</span>
        </a>
        <span style="color: #cbd5e1;">/</span>
        <span style="color: #0f172a; font-weight: 600;">Put-away Tasks</span>
    </nav>

    <!-- Unified Page Header -->
    <div class="page-header-card">
        <div class="page-title-wrap">
            <h1>Put-away Tasks</h1>
            <p>Relocate received inventory from staging and buffer bays into designated storage bins and racks.</p>
        </div>
        <div class="page-actions-wrap">
            <a class="button secondary" href="{{ route('goods-receipts.index') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                <span>Goods Receipts</span>
            </a>
            <a class="button secondary" href="{{ route('inventory.index') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                <span>Stock Overview</span>
            </a>
        </div>
    </div>

    <!-- 4 Top KPI Cards -->
    <div class="stat-grid-4">
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(37, 99, 235, 0.12); color: #2563eb;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Moveable Balances</span>
                <span class="stat-num">{{ number_format($metrics['total_tasks'] ?? $stockBalances->total()) }}</span>
                <span class="stat-sub">Active location holdings</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(16, 185, 129, 0.12); color: #10b981;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Total Units</span>
                <span class="stat-num">{{ number_format($metrics['total_units'] ?? 0) }}</span>
                <span class="stat-sub">Units across bins</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(245, 158, 11, 0.12); color: #f59e0b;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Active Batches</span>
                <span class="stat-num">{{ number_format($metrics['distinct_batches'] ?? 0) }}</span>
                <span class="stat-sub">Distinct stock lots</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(124, 58, 237, 0.12); color: #7c3aed;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Available Bins</span>
                <span class="stat-num">{{ number_format($metrics['bins_count'] ?? 0) }}</span>
                <span class="stat-sub">Storage locations</span>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="filter-card">
        <form method="GET" action="{{ route('put-away.index') }}" style="display: flex; align-items: center; gap: 10px; width: 100%; flex-wrap: wrap; margin: 0;">
            <div style="position: relative; flex: 1; min-width: 260px;">
                <svg style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none;" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Search by product SKU, name, batch number, or location code..." aria-label="Search put-away tasks" style="width: 100%;">
            </div>
            <button type="submit" class="button">Filter</button>
            @if(request()->filled('search'))
                <a class="button secondary" href="{{ route('put-away.index') }}">Reset</a>
            @endif
        </form>
    </div>

    <!-- Modern Table Card -->
    <div class="modern-table-card">
        <table class="clean-table">
            <thead>
                <tr>
                    <th style="width: 25%;">Product</th>
                    <th style="width: 15%;">Batch Details</th>
                    <th style="width: 20%;">Current Location</th>
                    <th style="width: 12%; text-align: right;">Quantity</th>
                    <th style="width: 14%;">Expiry Status</th>
                    <th style="width: 14%; text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($stockBalances as $balance)
                <tr>
                    <td>
                        <div style="display: flex; flex-direction: column;">
                            <a href="{{ route('products.show', $balance->batch->product) }}" style="font-weight: 700; color: #0f172a; text-decoration: none;">
                                {{ $balance->batch->product->name }}
                            </a>
                            <div style="display: flex; align-items: center; gap: 6px; margin-top: 2px;">
                                <span class="pill-code">{{ $balance->batch->product->sku }}</span>
                                <span style="font-size: 11px; color: #64748b;">{{ $balance->batch->product->category->name ?? '' }}</span>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div style="display: flex; flex-direction: column;">
                            <a href="{{ route('batches.show', $balance->batch) }}" style="font-family: monospace; font-weight: 700; color: #2563eb; text-decoration: none;">
                                {{ $balance->batch->batch_number }}
                            </a>
                            <span style="font-size: 11px; color: #64748b;">Exp: {{ $balance->batch->expires_at?->format('d M Y') ?? 'N/A' }}</span>
                        </div>
                    </td>
                    <td>
                        <div style="display: flex; flex-direction: column;">
                            <a href="{{ route('locations.show', $balance->location) }}" style="font-weight: 600; color: #334155; text-decoration: none;">
                                {{ $balance->location->name }} ({{ $balance->location->code }})
                            </a>
                            <span style="font-size: 11px; color: #64748b;">
                                {{ $balance->location->parent?->name ?? 'Zone' }} &rsaquo; {{ $balance->location->type }}
                            </span>
                        </div>
                    </td>
                    <td style="text-align: right; font-weight: 700; font-size: 14px; color: #0f172a;">
                        {{ number_format($balance->quantity) }} <span style="font-size: 11px; font-weight: normal; color: #64748b;">{{ $balance->batch->product->unit }}</span>
                    </td>
                    <td>
                        @php
                            $expiryClass = match($balance->batch->expiry_status) {
                                'good' => 'badge-success',
                                'expiring_soon' => 'badge-warning',
                                'expired' => 'badge-danger',
                                default => 'badge-info',
                            };
                        @endphp
                        <span class="{{ $expiryClass }}">{{ ucwords(str_replace('_', ' ', $balance->batch->expiry_status)) }}</span>
                    </td>
                    <td style="text-align: right;">
                        <a href="{{ route('put-away.show', $balance) }}" class="button" style="padding: 5px 12px; font-size: 12px; background: #2563eb;">
                            <span>Assign Bin</span>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 36px 20px; color: #64748b;">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" style="margin-bottom: 8px;"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
                        <p style="margin: 0; font-weight: 600; color: #334155;">No items awaiting put-away</p>
                        <small style="color: #94a3b8;">All warehouse inventory is currently allocated or no search results found.</small>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div style="margin-top: 14px;">
        {{ $stockBalances->links() }}
    </div>
@endsection
