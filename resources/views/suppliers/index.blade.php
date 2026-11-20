
@extends('catalog.layout')

@section('title', 'Suppliers')

@section('breadcrumbs')
    <span class="breadcrumbs-separator">/</span>
    <span style="color:#0f172a;font-weight:700;">Suppliers</span>
@endsection

@section('content')

<style>
    .supplier-page { color:#1e293b; }
    .supplier-page * { box-sizing:border-box; }

    .supplier-header {
        display:flex;justify-content:space-between;align-items:center;
        gap:18px;flex-wrap:wrap;padding:25px;margin-bottom:22px;
        color:white;border-radius:16px;
        background:linear-gradient(120deg,#102b46,#174d69);
        box-shadow:0 8px 22px rgba(15,45,70,.12);
    }
    .supplier-header h1 { margin:0 0 7px;font-size:25px;font-weight:800; }
    .supplier-header p { margin:0;color:#d5e6f1;font-size:13px; }
    .supplier-header .button { display:inline-flex;align-items:center;gap:7px;text-decoration:none; }

    .supplier-metrics {
        display:grid;grid-template-columns:repeat(4,minmax(0,1fr));
        gap:15px;margin-bottom:22px;
    }
    .supplier-metric {
        display:flex;align-items:center;gap:13px;padding:18px;
        background:white;border:1px solid #e8edf3;border-radius:13px;
        box-shadow:0 3px 12px rgba(15,23,42,.035);
    }
    .supplier-metric-icon {
        width:45px;height:45px;border-radius:12px;
        display:flex;align-items:center;justify-content:center;flex-shrink:0;
    }
    .supplier-metric-label {
        color:#64748b;font-size:10.5px;font-weight:800;
        text-transform:uppercase;letter-spacing:.5px;
    }
    .supplier-metric-value {
        display:block;font-size:25px;font-weight:800;
        color:#0f172a;margin-top:3px;
    }
    .supplier-metric-note { display:block;font-size:11px;color:#94a3b8;margin-top:2px; }

    .supplier-panel {
        background:white;border:1px solid #e8edf3;border-radius:13px;
        padding:18px;margin-bottom:18px;
        box-shadow:0 3px 12px rgba(15,23,42,.025);
    }
    .supplier-panel-heading { margin-bottom:15px; }
    .supplier-panel-heading h3 { margin:0;color:#0f172a;font-size:14px;font-weight:800; }
    .supplier-panel-heading p { margin:4px 0 0;color:#94a3b8;font-size:11.5px; }

    .supplier-filter {
        display:grid;grid-template-columns:minmax(220px,2fr) 1fr auto auto;
        gap:10px;align-items:center;
    }
    .supplier-filter input,.supplier-filter select {
        width:100%;min-height:41px;padding:9px 12px;
        border:1px solid #dce4ec;border-radius:8px;
        background:white;color:#334155;font-size:12px;
    }
    .supplier-filter input:focus,.supplier-filter select:focus {
        outline:none;border-color:#2563eb;
        box-shadow:0 0 0 3px rgba(37,99,235,.09);
    }
    .supplier-filter .button { min-height:41px; }

    .supplier-table-card {
        background:white;border:1px solid #e8edf3;border-radius:13px;
        overflow:hidden;box-shadow:0 3px 12px rgba(15,23,42,.025);
    }
    .supplier-table-heading {
        display:flex;justify-content:space-between;align-items:center;
        gap:10px;flex-wrap:wrap;padding:17px 19px;
        border-bottom:1px solid #edf1f5;
    }
    .supplier-table-heading strong { color:#0f172a;font-size:14px; }
    .supplier-table-heading span { color:#64748b;font-size:11.5px; }

    .supplier-table-wrap { overflow-x:auto; }
    .supplier-table { width:100%;border-collapse:collapse;min-width:800px; }
    .supplier-table th {
        padding:13px 15px;background:#f8fafc;text-align:left;
        color:#64748b;font-size:10.5px;text-transform:uppercase;
        letter-spacing:.5px;font-weight:800;white-space:nowrap;
    }
    .supplier-table td {
        padding:14px 15px;border-top:1px solid #f1f5f9;
        font-size:12px;vertical-align:middle;
    }
    .supplier-table tbody tr:hover { background:#f8fbff; }

    .supplier-name {
        display:block;color:#0f172a;font-size:12.5px;
        font-weight:800;text-decoration:none;
    }
    .supplier-name:hover { color:#2563eb; }
    .supplier-code {
        display:inline-block;margin-top:5px;padding:4px 8px;
        border-radius:5px;background:#f1f5f9;
        color:#475569;font-size:10px;font-weight:700;
    }
    .supplier-contact { display:flex;align-items:center;gap:8px; }
    .supplier-avatar {
        width:31px;height:31px;display:flex;align-items:center;
        justify-content:center;border-radius:50%;flex-shrink:0;
        background:#eaf2ff;color:#2563eb;font-size:11px;font-weight:800;
    }
    .supplier-contact-name { color:#334155;font-weight:600;font-size:11.5px; }
    .supplier-contact-details {
        display:flex;flex-direction:column;gap:5px;
        color:#475569;font-size:11px;overflow-wrap:anywhere;
    }
    .supplier-contact-details span { display:flex;align-items:center;gap:6px; }
    .supplier-contact-details svg { flex-shrink:0; }

    .supplier-status {
        display:inline-flex;align-items:center;gap:6px;
        padding:5px 9px;border-radius:20px;font-size:10.5px;font-weight:700;
    }
    .supplier-status:before {
        content:"";width:6px;height:6px;
        border-radius:50%;background:currentColor;
    }
    .supplier-status.active { background:#e8f8ef;color:#15803d; }
    .supplier-status.inactive { background:#fff0f0;color:#dc2626; }

    .supplier-actions {
        display:flex;justify-content:flex-end;
        align-items:center;gap:6px;flex-wrap:wrap;
    }
    .supplier-actions .button { padding:6px 9px;font-size:10.5px;white-space:nowrap; }

    .supplier-empty { padding:45px 20px;text-align:center;color:#94a3b8; }
    .supplier-empty strong { display:block;color:#334155;margin:9px 0 5px;font-size:13px; }
    .supplier-empty p { margin:0;font-size:11.5px; }

    .supplier-pagination { margin-top:17px; }

    @media(max-width:950px) {
        .supplier-metrics { grid-template-columns:repeat(2,minmax(0,1fr)); }
        .supplier-filter { grid-template-columns:1fr 1fr; }
        .supplier-filter input { grid-column:1/-1; }
    }
    @media(max-width:600px) {
        .supplier-header { padding:19px; }
        .supplier-header h1 { font-size:21px; }
        .supplier-header .button { width:100%;justify-content:center; }
        .supplier-metrics { gap:9px; }
        .supplier-metric { padding:12px;gap:9px; }
        .supplier-metric-icon { width:36px;height:36px; }
        .supplier-metric-value { font-size:20px; }
        .supplier-filter { grid-template-columns:1fr; }
        .supplier-filter input { grid-column:auto; }
    }
</style>

<div class="supplier-page">

    <nav style="display:flex;align-items:center;gap:8px;font-size:12px;color:#94a3b8;margin-bottom:15px;" aria-label="Breadcrumb">
        <a href="{{ route('dashboard') }}" style="color:#64748b;text-decoration:none;">Dashboard</a>
        <span>/</span>
        <strong style="color:#0f172a;">Suppliers</strong>
    </nav>

    <header class="supplier-header">
        <div>
            <h1>Supplier Management</h1>
            <p>Manage supplier profiles, contact information and procurement records.</p>
        </div>

        <a class="button" href="{{ route('suppliers.create') }}">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <line x1="12" y1="5" x2="12" y2="19"/>
                <line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            Add Supplier
        </a>
    </header>

    <section class="supplier-metrics" aria-label="Supplier statistics">
        <div class="supplier-metric">
            <div class="supplier-metric-icon" style="background:#eaf2ff;color:#2563eb;">
                <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                </svg>
            </div>
            <div>
                <span class="supplier-metric-label">Total Suppliers</span>
                <strong class="supplier-metric-value">{{ number_format($metrics['total'] ?? $suppliers->total()) }}</strong>
                <span class="supplier-metric-note">Registered vendors</span>
            </div>
        </div>

        <div class="supplier-metric">
            <div class="supplier-metric-icon" style="background:#e8f8ef;color:#15945b;">
                <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
            </div>
            <div>
                <span class="supplier-metric-label">Active Suppliers</span>
                <strong class="supplier-metric-value">{{ number_format($metrics['active'] ?? 0) }}</strong>
                <span class="supplier-metric-note">Active vendor records</span>
            </div>
        </div>

        <div class="supplier-metric">
            <div class="supplier-metric-icon" style="background:#fff5e5;color:#d97706;">
                <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/>
                </svg>
            </div>
            <div>
                <span class="supplier-metric-label">Purchase Orders</span>
                <strong class="supplier-metric-value">{{ number_format($metrics['purchase_orders'] ?? 0) }}</strong>
                <span class="supplier-metric-note">Recorded vendor orders</span>
            </div>
        </div>

        <div class="supplier-metric">
            <div class="supplier-metric-icon" style="background:#f1f5f9;color:#64748b;">
                <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>
                </svg>
            </div>
            <div>
                <span class="supplier-metric-label">Inactive Suppliers</span>
                <strong class="supplier-metric-value">{{ number_format($metrics['inactive'] ?? 0) }}</strong>
                <span class="supplier-metric-note">Inactive vendor records</span>
            </div>
        </div>
    </section>

    <section class="supplier-panel">
        <div class="supplier-panel-heading">
            <h3>Search and Filter Suppliers</h3>
            <p>Find suppliers using their name, supplier code, contact details or status.</p>
        </div>

        <form class="supplier-filter" method="GET" action="{{ route('suppliers.index') }}">
            <input
                type="search"
                name="search"
                value="{{ $filters['search'] ?? '' }}"
                placeholder="Search supplier name, code, contact or email..."
                aria-label="Search suppliers"
            >

            <select name="status" aria-label="Filter by status">
                <option value="">All Statuses</option>
                <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
            </select>

            <button type="submit" class="button">Apply Filters</button>

            @if (!empty($filters['search']) || !empty($filters['status']))
                <a class="button secondary" href="{{ route('suppliers.index') }}">Clear Filters</a>
            @endif
        </form>
    </section>

    <section class="supplier-table-card">
        <div class="supplier-table-heading">
            <strong>Supplier Register</strong>
            <span>
                Showing {{ $suppliers->firstItem() ?? 0 }}–{{ $suppliers->lastItem() ?? 0 }}
                of {{ $suppliers->total() }} suppliers
            </span>
        </div>

        <div class="supplier-table-wrap">
            <table class="supplier-table">
                <thead>
                    <tr>
                        <th style="width:23%;">Supplier & Code</th>
                        <th style="width:20%;">Contact Person</th>
                        <th style="width:27%;">Phone & Email</th>
                        <th style="width:13%;">Status</th>
                        <th style="width:17%;text-align:right;">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($suppliers as $supplier)
                        <tr>
                            <td>
                                <a class="supplier-name" href="{{ route('suppliers.show', $supplier) }}">
                                    {{ $supplier->name }}
                                </a>
                                <span class="supplier-code">{{ $supplier->code }}</span>
                            </td>

                            <td>
                                <div class="supplier-contact">
                                    <div class="supplier-avatar">
                                        {{ strtoupper(substr($supplier->contact_person ?: $supplier->name, 0, 1)) }}
                                    </div>
                                    <span class="supplier-contact-name">
                                        {{ $supplier->contact_person ?: 'Not provided' }}
                                    </span>
                                </div>
                            </td>

                            <td>
                                <div class="supplier-contact-details">
                                    @if ($supplier->phone)
                                        <span>{{ $supplier->phone }}</span>
                                    @endif

                                    @if ($supplier->email)
                                        <span>{{ $supplier->email }}</span>
                                    @endif

                                    @if (!$supplier->phone && !$supplier->email)
                                        <span style="color:#94a3b8;">No contact details</span>
                                    @endif
                                </div>
                            </td>

                            <td>
                                @if ($supplier->status === 'active')
                                    <span class="supplier-status active">Active</span>
                                @else
                                    <span class="supplier-status inactive">Inactive</span>
                                @endif
                            </td>

                            <td>
                                <div class="supplier-actions">
                                    <a href="{{ route('suppliers.show', $supplier) }}" class="button secondary">View</a>
                                    <a href="{{ route('suppliers.edit', $supplier) }}" class="button secondary">Edit</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="supplier-empty">
                                    <svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                        <circle cx="9" cy="7" r="4"/>
                                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                                    </svg>
                                    <strong>No suppliers found</strong>
                                    <p>Try changing your search or filters.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="supplier-pagination">
        {{ $suppliers->links() }}
    </div>

</div>
@endsection
