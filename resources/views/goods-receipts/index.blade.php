@extends('catalog.layout')

@section('title', 'Goods receipts')

@section('breadcrumbs')
    <span class="breadcrumbs-separator">/</span>
    <span style="color: #0f172a; font-weight: 600;">Goods Receipts (GRN)</span>
@endsection

@section('content')
    <!-- Inline Breadcrumbs Navigation -->
    <nav style="display: flex; align-items: center; gap: 8px; font-size: 12.5px; color: #64748b; margin-bottom: 14px;" aria-label="Breadcrumb">
        <a href="{{ route('dashboard') }}" style="color: #64748b; text-decoration: none; display: flex; align-items: center; gap: 4px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
            <span>Dashboard</span>
        </a>
        <span style="color: #cbd5e1;">/</span>
        <span style="color: #0f172a; font-weight: 600;">Goods Receipts (GRN)</span>
    </nav>

    <!-- Unified Page Header -->
    <div class="page-header-card">
        <div class="page-title-wrap">
            <h1>Goods Receipts (GRN)</h1>
            <p>Log incoming deliveries, track batch allocations, and inspect verified inbound stock.</p>
        </div>
        <div class="page-actions-wrap">
            <a class="button" href="{{ route('goods-receipts.create') }}" style="background: #10b981;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                <span>Receive Goods (Create GRN)</span>
            </a>
        </div>
    </div>

    <!-- 4 Top KPI Cards -->
    <div class="stat-grid-4">
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(16, 185, 129, 0.12); color: #10b981;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Total Receipts</span>
                <span class="stat-num">{{ number_format($metrics['total'] ?? $goodsReceipts->total()) }}</span>
                <span class="stat-sub">Lifetime confirmed GRNs</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(37, 99, 235, 0.12); color: #2563eb;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Received Today</span>
                <span class="stat-num">{{ number_format($metrics['today'] ?? 0) }}</span>
                <span class="stat-sub">Today's dock arrivals</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(245, 158, 11, 0.12); color: #f59e0b;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">This Month</span>
                <span class="stat-num">{{ number_format($metrics['this_month'] ?? 0) }}</span>
                <span class="stat-sub">Monthly inbound volume</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(124, 58, 237, 0.12); color: #7c3aed;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Active Suppliers</span>
                <span class="stat-num">{{ number_format($metrics['suppliers_count'] ?? 0) }}</span>
                <span class="stat-sub">Vendors delivered</span>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="filter-card">
        <form method="GET" action="{{ route('goods-receipts.index') }}" style="display: flex; align-items: center; gap: 10px; width: 100%; flex-wrap: wrap; margin: 0;">
            <div style="position: relative; flex: 1; min-width: 260px;">
                <svg style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none;" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search GRN, PO number, supplier, batch, or receiving officer..." aria-label="Search goods receipts" style="width: 100%;">
            </div>
            <button type="submit" class="button">Filter</button>
            @if (!empty($filters['search']))
                <a class="button secondary" href="{{ route('goods-receipts.index') }}">Reset</a>
            @endif
        </form>
    </div>

    <!-- Modern Table Card -->
    <div class="modern-table-card">
        <table class="clean-table">
            <thead>
                <tr>
                    <th style="width: 18%;">GRN Number</th>
                    <th style="width: 24%;">Supplier</th>
                    <th style="width: 16%;">Purchase Order</th>
                    <th style="width: 16%;">Received By</th>
                    <th style="width: 14%;">Received At</th>
                    <th style="width: 6%;">Items</th>
                    <th style="width: 6%; text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($goodsReceipts as $goodsReceipt)
                <tr>
                    <td>
                        <a href="{{ route('goods-receipts.show', $goodsReceipt) }}" style="font-weight: 700; color: #2563eb; text-decoration: none; font-family: monospace; font-size: 13px;">
                            {{ $goodsReceipt->receipt_number }}
                        </a>
                    </td>
                    <td>
                        <span style="font-weight: 600; color: #0f172a;">{{ $goodsReceipt->supplier?->name ?? 'Direct Receipt' }}</span>
                    </td>
                    <td>
                        @if ($goodsReceipt->purchaseOrder)
                            <a href="{{ route('purchase-orders.show', $goodsReceipt->purchaseOrder) }}" style="color: #475569; text-decoration: none; font-weight: 500; font-size: 12.5px;">
                                {{ $goodsReceipt->purchaseOrder->order_number }}
                            </a>
                        @else
                            <span style="color: #94a3b8;">—</span>
                        @endif
                    </td>
                    <td>
                        <span style="color: #334155; font-size: 12.5px;">{{ $goodsReceipt->receiver?->name ?? 'Officer' }}</span>
                    </td>
                    <td>
                        <span style="color: #64748b; font-size: 12.5px;">{{ $goodsReceipt->received_at->format('d M Y, H:i') }}</span>
                    </td>
                    <td>
                        <span class="pill-code">{{ $goodsReceipt->lines_count }}</span>
                    </td>
                    <td style="text-align: right;">
                        <a href="{{ route('goods-receipts.show', $goodsReceipt) }}" class="button secondary" style="padding: 4px 10px; font-size: 12px;">View</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 36px 20px; color: #64748b;">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" style="margin-bottom: 8px;"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                        <p style="margin: 0; font-weight: 600; color: #334155;">No goods receipts found</p>
                        <small style="color: #94a3b8;">Record incoming shipments by clicking "Receive Goods".</small>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div style="margin-top: 14px;">
        {{ $goodsReceipts->links() }}
    </div>
@endsection
