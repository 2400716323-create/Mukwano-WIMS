@extends('catalog.layout')

@section('title', 'Purchase orders')

@section('breadcrumbs')
    <span class="breadcrumbs-separator">/</span>
    <span style="color: #0f172a; font-weight: 600;">Purchase Orders</span>
@endsection

@section('content')
    <!-- Inline Breadcrumbs Navigation -->
    <nav style="display: flex; align-items: center; gap: 8px; font-size: 12.5px; color: #64748b; margin-bottom: 14px;" aria-label="Breadcrumb">
        <a href="{{ route('dashboard') }}" style="color: #64748b; text-decoration: none; display: flex; align-items: center; gap: 4px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
            <span>Dashboard</span>
        </a>
        <span style="color: #cbd5e1;">/</span>
        <span style="color: #0f172a; font-weight: 600;">Purchase Orders</span>
    </nav>

    <!-- Unified Page Header -->
    <div class="page-header-card">
        <div class="page-title-wrap">
            <h1>Purchase Orders</h1>
            <p>Generate, authorize and monitor vendor procurement orders against deliveries.</p>
        </div>
        <div class="page-actions-wrap">
            <a class="button" href="{{ route('purchase-orders.create') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                <span>Create Purchase Order</span>
            </a>
        </div>
    </div>

    <!-- 4 Top KPI Cards -->
    <div class="stat-grid-4">
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(37, 99, 235, 0.12); color: #2563eb;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Total Orders</span>
                <span class="stat-num">{{ number_format($metrics['total'] ?? $purchaseOrders->total()) }}</span>
                <span class="stat-sub">All PO documents</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(16, 185, 129, 0.12); color: #10b981;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Approved & Active</span>
                <span class="stat-num">{{ number_format($metrics['approved'] ?? 0) }}</span>
                <span class="stat-sub">Awaiting inbound delivery</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(245, 158, 11, 0.12); color: #f59e0b;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Pending Drafts</span>
                <span class="stat-num">{{ number_format($metrics['draft'] ?? 0) }}</span>
                <span class="stat-sub">Requires manager approval</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(124, 58, 237, 0.12); color: #7c3aed;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Received Orders</span>
                <span class="stat-num">{{ number_format($metrics['received'] ?? 0) }}</span>
                <span class="stat-sub">Fully or partially fulfilled</span>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="filter-card">
        <form method="GET" action="{{ route('purchase-orders.index') }}" style="display: flex; align-items: center; gap: 10px; width: 100%; flex-wrap: wrap; margin: 0;">
            <div style="position: relative; flex: 1; min-width: 260px;">
                <svg style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none;" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search by PO number or supplier name..." aria-label="Search purchase orders" style="width: 100%;">
            </div>
            <select name="status" aria-label="Filter by status">
                <option value="">All Statuses</option>
                @foreach (['draft', 'approved', 'partially_received', 'received', 'cancelled'] as $status)
                    <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                @endforeach
            </select>
            <button type="submit" class="button">Filter</button>
            @if (!empty($filters['search']) || !empty($filters['status']))
                <a class="button secondary" href="{{ route('purchase-orders.index') }}">Reset</a>
            @endif
        </form>
    </div>

    <!-- Modern Table Card -->
    <div class="modern-table-card">
        <table class="clean-table">
            <thead>
                <tr>
                    <th style="width: 18%;">PO Number</th>
                    <th style="width: 26%;">Supplier</th>
                    <th style="width: 14%;">Order Date</th>
                    <th style="width: 14%;">Expected Date</th>
                    <th style="width: 10%;">Items</th>
                    <th style="width: 12%;">Status</th>
                    <th style="width: 6%; text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($purchaseOrders as $purchaseOrder)
                <tr>
                    <td>
                        <a href="{{ route('purchase-orders.show', $purchaseOrder) }}" style="font-weight: 700; color: #2563eb; text-decoration: none; font-family: monospace; font-size: 13px;">
                            {{ $purchaseOrder->order_number }}
                        </a>
                    </td>
                    <td>
                        <span style="font-weight: 600; color: #0f172a;">{{ $purchaseOrder->supplier->name }}</span>
                    </td>
                    <td>
                        <span style="color: #64748b; font-size: 12.5px;">{{ $purchaseOrder->order_date->format('d M Y') }}</span>
                    </td>
                    <td>
                        <span style="color: #64748b; font-size: 12.5px;">{{ $purchaseOrder->expected_date ? $purchaseOrder->expected_date->format('d M Y') : '—' }}</span>
                    </td>
                    <td>
                        <span class="pill-code">{{ $purchaseOrder->lines_count }} {{ Str::plural('line', $purchaseOrder->lines_count) }}</span>
                    </td>
                    <td>
                        @php
                            $badgeClass = match($purchaseOrder->status) {
                                'approved' => 'badge-info',
                                'received' => 'badge-success',
                                'partially_received' => 'badge-warning',
                                'cancelled' => 'badge-danger',
                                default => 'badge-purple',
                            };
                        @endphp
                        <span class="{{ $badgeClass }}">{{ ucwords(str_replace('_', ' ', $purchaseOrder->status)) }}</span>
                    </td>
                    <td style="text-align: right;">
                        <a href="{{ route('purchase-orders.show', $purchaseOrder) }}" class="button secondary" style="padding: 4px 10px; font-size: 12px;">View</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 36px 20px; color: #64748b;">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" style="margin-bottom: 8px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg>
                        <p style="margin: 0; font-weight: 600; color: #334155;">No purchase orders found</p>
                        <small style="color: #94a3b8;">Try clearing the search query or generate a new purchase order.</small>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div style="margin-top: 14px;">
        {{ $purchaseOrders->links() }}
    </div>
@endsection
