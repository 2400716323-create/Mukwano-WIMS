@extends('catalog.layout')

@section('title', 'Batch Management — Mukwano WIMS')

@section('breadcrumbs')
    <span class="breadcrumbs-separator">/</span>
    <span style="color: #0f172a; font-weight: 600;">Batch Management</span>
@endsection

@section('content')
    <!-- Inline Breadcrumbs Navigation -->
    <nav style="display: flex; align-items: center; gap: 8px; font-size: 12.5px; color: #64748b; margin-bottom: 14px;" aria-label="Breadcrumb">
        <a href="{{ route('dashboard') }}" style="color: #64748b; text-decoration: none; display: flex; align-items: center; gap: 4px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
            <span>Dashboard</span>
        </a>
        <span style="color: #cbd5e1;">/</span>
        <span style="color: #0f172a; font-weight: 600;">Batch Management</span>
    </nav>

    <!-- Unified Page Header -->
    <div class="page-header-card">
        <div class="page-title-wrap">
            <h1>Batch Management</h1>
            <p>Track production lots, shelf-life horizons, inspection histories, and on-hand inventory across storage bins.</p>
        </div>
        <div class="page-actions-wrap">
            <a class="button secondary" href="{{ route('products.index') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                <span>Products Register</span>
            </a>
            <a class="button secondary" href="{{ route('alerts.index') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <span>Expiry Alerts</span>
            </a>
        </div>
    </div>

    <!-- 4 Top KPI Cards -->
    <div class="stat-grid-4">
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(37, 99, 235, 0.12); color: #2563eb;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Total Batches</span>
                <span class="stat-num">{{ number_format($metrics['total'] ?? $batches->total()) }}</span>
                <span class="stat-sub">Lifetime registered lots</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(16, 185, 129, 0.12); color: #10b981;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">In Stock</span>
                <span class="stat-num">{{ number_format($metrics['active_stock'] ?? 0) }}</span>
                <span class="stat-sub">Positive bin inventory</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(245, 158, 11, 0.12); color: #f59e0b;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Expiring Soon</span>
                <span class="stat-num">{{ number_format($metrics['near_expiry'] ?? 0) }}</span>
                <span class="stat-sub">Within next 30 days</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(239, 68, 68, 0.12); color: #ef4444;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Expired Lots</span>
                <span class="stat-num">{{ number_format($metrics['expired'] ?? 0) }}</span>
                <span class="stat-sub">Quarantined from picking</span>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="filter-card">
        <form method="GET" action="{{ route('batches.index') }}" style="display: flex; align-items: center; gap: 8px; width: 100%; flex-wrap: wrap; margin: 0;">
            <div style="position: relative; flex: 1; min-width: 240px;">
                <svg style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none;" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Search batch number, SKU or product name..." aria-label="Search batches" style="width: 100%; font-size: 12px; padding: 6px 12px 6px 30px;">
            </div>
            <select name="product_id" style="font-size: 12px; padding: 6px 10px;">
                <option value="">All Products</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>
                        {{ $product->sku }} — {{ $product->name }}
                    </option>
                @endforeach
            </select>
            <select name="expiry_status" style="font-size: 12px; padding: 6px 10px;">
                <option value="">All Expiry Horizons</option>
                <option value="near_expiry" {{ request('expiry_status') === 'near_expiry' ? 'selected' : '' }}>Expiring in 30 days</option>
                <option value="expired" {{ request('expiry_status') === 'expired' ? 'selected' : '' }}>Expired</option>
            </select>
            <button type="submit" class="button" style="padding: 6px 14px; font-size: 12px;">Filter</button>
            @if(request()->filled('search') || request()->filled('product_id') || request()->filled('expiry_status'))
                <a class="button secondary" href="{{ route('batches.index') }}" style="padding: 6px 12px; font-size: 12px;">Reset</a>
            @endif
        </form>
    </div>

    <!-- Modern Table Card -->
    <div class="modern-table-card">
        <table class="clean-table">
            <thead>
                <tr>
                    <th style="width: 18%;">Batch Number</th>
                    <th style="width: 28%;">Product</th>
                    <th style="width: 12%;">Mfg Date</th>
                    <th style="width: 12%;">Expiry Date</th>
                    <th style="width: 12%; text-align: right;">Total Stock</th>
                    <th style="width: 12%;">Expiry Status</th>
                    <th style="width: 6%; text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
            @forelse($batches as $batch)
                <tr>
                    <td>
                        <a href="{{ route('batches.show', $batch) }}" style="font-family: monospace; font-weight: 700; color: #2563eb; text-decoration: none; font-size: 13px;">
                            {{ $batch->batch_number }}
                        </a>
                    </td>
                    <td>
                        <div style="display: flex; flex-direction: column;">
                            <a href="{{ route('products.show', $batch->product) }}" style="font-weight: 700; color: #0f172a; text-decoration: none;">
                                {{ $batch->product->name }}
                            </a>
                            <span class="pill-code" style="width: fit-content; margin-top: 2px;">{{ $batch->product->sku }}</span>
                        </div>
                    </td>
                    <td><span style="color: #64748b; font-size: 12px;">{{ $batch->manufactured_at ? $batch->manufactured_at->format('d M Y') : '—' }}</span></td>
                    <td><span style="color: #64748b; font-size: 12px;">{{ $batch->expires_at ? $batch->expires_at->format('d M Y') : '—' }}</span></td>
                    <td style="text-align: right; font-weight: 700; color: #0f172a; font-size: 13.5px;">
                        {{ number_format($batch->total_quantity ?? 0) }} <span style="font-size: 11px; font-weight: normal; color: #64748b;">{{ $batch->product->unit }}</span>
                    </td>
                    <td>
                        @php
                            $expStatus = $batch->expiry_status;
                            $badgeClass = match($expStatus) {
                                'good' => 'badge-success',
                                'expiring_soon' => 'badge-warning',
                                'expired' => 'badge-danger',
                                default => 'badge-info',
                            };
                        @endphp
                        <span class="{{ $badgeClass }}">{{ ucwords(str_replace('_', ' ', $expStatus)) }}</span>
                    </td>
                    <td style="text-align: right;">
                        <a href="{{ route('batches.show', $batch) }}" class="button secondary" style="padding: 4px 10px; font-size: 12px;">View</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 36px 20px; color: #64748b;">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" style="margin-bottom: 8px;"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                        <p style="margin: 0; font-weight: 600; color: #334155;">No batches found</p>
                        <small style="color: #94a3b8;">Try clearing the search query or receiving new products.</small>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div style="margin-top: 14px;">
        {{ $batches->links() }}
    </div>
@endsection
