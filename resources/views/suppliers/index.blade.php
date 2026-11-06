@extends('catalog.layout')

@section('title', 'Suppliers')

@section('breadcrumbs')
    <span class="breadcrumbs-separator">/</span>
    <span style="color: #0f172a; font-weight: 600;">Suppliers</span>
@endsection

@section('content')
    <!-- Inline Breadcrumbs Navigation -->
    <nav style="display: flex; align-items: center; gap: 8px; font-size: 12.5px; color: #64748b; margin-bottom: 14px;" aria-label="Breadcrumb">
        <a href="{{ route('dashboard') }}" style="color: #64748b; text-decoration: none; display: flex; align-items: center; gap: 4px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
            <span>Dashboard</span>
        </a>
        <span style="color: #cbd5e1;">/</span>
        <span style="color: #0f172a; font-weight: 600;">Suppliers</span>
    </nav>

    <!-- Unified Page Header -->
    <div class="page-header-card">
        <div class="page-title-wrap">
            <h1>Suppliers</h1>
            <p>Maintain vendor master records, procurement contacts, and vendor performance history.</p>
        </div>
        <div class="page-actions-wrap">
            <a class="button" href="{{ route('suppliers.create') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                <span>Add Supplier</span>
            </a>
        </div>
    </div>

    <!-- 4 Top KPI Cards -->
    <div class="stat-grid-4">
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(37, 99, 235, 0.12); color: #2563eb;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Total Suppliers</span>
                <span class="stat-num">{{ number_format($metrics['total'] ?? $suppliers->total()) }}</span>
                <span class="stat-sub">Registered vendors</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(16, 185, 129, 0.12); color: #10b981;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Active Suppliers</span>
                <span class="stat-num">{{ number_format($metrics['active'] ?? 0) }}</span>
                <span class="stat-sub">Approved for procurement</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(245, 158, 11, 0.12); color: #f59e0b;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Purchase Orders</span>
                <span class="stat-num">{{ number_format($metrics['purchase_orders'] ?? 0) }}</span>
                <span class="stat-sub">Lifetime vendor orders</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(100, 116, 139, 0.12); color: #64748b;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Inactive Suppliers</span>
                <span class="stat-num">{{ number_format($metrics['inactive'] ?? 0) }}</span>
                <span class="stat-sub">Blacklisted or dormant</span>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="filter-card">
        <form method="GET" action="{{ route('suppliers.index') }}" style="display: flex; align-items: center; gap: 10px; width: 100%; flex-wrap: wrap; margin: 0;">
            <div style="position: relative; flex: 1; min-width: 260px;">
                <svg style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none;" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search supplier name, code, contact or email..." aria-label="Search suppliers" style="width: 100%;">
            </div>
            <select name="status" aria-label="Filter by status">
                <option value="">All Statuses</option>
                <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
            </select>
            <button type="submit" class="button">Filter</button>
            @if (!empty($filters['search']) || !empty($filters['status']))
                <a class="button secondary" href="{{ route('suppliers.index') }}">Reset</a>
            @endif
        </form>
    </div>

    <!-- Modern Table Card -->
    <div class="modern-table-card">
        <table class="clean-table">
            <thead>
                <tr>
                    <th style="width: 25%;">Supplier & Code</th>
                    <th style="width: 20%;">Contact Person</th>
                    <th style="width: 25%;">Phone & Email</th>
                    <th style="width: 12%;">Status</th>
                    <th style="width: 18%; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($suppliers as $supplier)
                <tr>
                    <td>
                        <div style="display: flex; flex-direction: column; gap: 2px;">
                            <a href="{{ route('suppliers.show', $supplier) }}" style="font-weight: 700; color: #0f172a; text-decoration: none; font-size: 13.5px;">
                                {{ $supplier->name }}
                            </a>
                            <span class="pill-code" style="width: fit-content; font-size: 11px;">{{ $supplier->code }}</span>
                        </div>
                    </td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <div style="width: 24px; height: 24px; border-radius: 50%; background: #e2e8f0; color: #475569; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 700;">
                                {{ strtoupper(substr($supplier->contact_person ?: $supplier->name, 0, 1)) }}
                            </div>
                            <span style="font-weight: 600; color: #334155; font-size: 12.5px;">{{ $supplier->contact_person ?: '—' }}</span>
                        </div>
                    </td>
                    <td>
                        <div style="display: flex; flex-direction: column; gap: 2px; font-size: 12px; color: #475569;">
                            @if ($supplier->phone)
                                <span style="display: flex; align-items: center; gap: 4px;">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                    {{ $supplier->phone }}
                                </span>
                            @endif
                            @if ($supplier->email)
                                <span style="display: flex; align-items: center; gap: 4px; color: #64748b;">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                                    {{ $supplier->email }}
                                </span>
                            @endif
                            @if (!$supplier->phone && !$supplier->email)
                                <span style="color: #94a3b8;">—</span>
                            @endif
                        </div>
                    </td>
                    <td>
                        @if ($supplier->status === 'active')
                            <span class="badge-success">Active</span>
                        @else
                            <span class="badge-muted">Inactive</span>
                        @endif
                    </td>
                    <td style="text-align: right;">
                        <div style="display: inline-flex; align-items: center; gap: 6px; justify-content: flex-end;">
                            <a href="{{ route('suppliers.edit', $supplier) }}" class="button secondary" style="padding: 4px 10px; font-size: 12px;">Edit</a>
                            @if ($supplier->status === 'active')
                                <form class="inline-form" method="POST" action="{{ route('suppliers.deactivate', $supplier) }}" style="margin: 0;">
                                    @csrf
                                    @method('PATCH')
                                    <button class="button secondary" type="submit" style="padding: 4px 8px; font-size: 12px; color: #d97706;">Deactivate</button>
                                </form>
                            @else
                                <form class="inline-form" method="POST" action="{{ route('suppliers.activate', $supplier) }}" style="margin: 0;">
                                    @csrf
                                    @method('PATCH')
                                    <button class="button secondary" type="submit" style="padding: 4px 8px; font-size: 12px; color: #16a34a;">Activate</button>
                                </form>
                            @endif
                            <form class="inline-form" method="POST" action="{{ route('suppliers.destroy', $supplier) }}" onsubmit="return confirm('Delete this supplier record permanently?')" style="margin: 0;">
                                @csrf
                                @method('DELETE')
                                <button class="button danger" type="submit" style="padding: 4px 8px; font-size: 12px;">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; padding: 36px 20px; color: #64748b;">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" style="margin-bottom: 8px;"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        <p style="margin: 0; font-weight: 600; color: #334155;">No suppliers found matching your criteria</p>
                        <small style="color: #94a3b8;">Try clearing the search query or create a new supplier.</small>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div style="margin-top: 14px;">
        {{ $suppliers->links() }}
    </div>
@endsection
