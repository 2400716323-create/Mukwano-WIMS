@extends('catalog.layout')

@section('title', $confirmed ? 'Import summary' : 'Review product import')

@section('breadcrumbs')
    <a href="{{ route('erp-exchange.index') }}">ERP Exchange</a>
    <span class="separator">/</span>
    <span class="current">{{ $confirmed ? 'Import Summary' : 'Review Import' }}</span>
@endsection

@section('content')
    @php
        $validCount = count(array_filter($staged['validated_rows'], fn ($row) => $row['errors'] === []));
        $rejectedCount = count($staged['validated_rows']) - $validCount;
    @endphp

    <!-- Unified Page Header -->
    <div class="page-header-card">
        <div class="page-title-wrap">
            <nav class="breadcrumb-trail" aria-label="Breadcrumb">
                <a href="{{ route('dashboard') }}">Dashboard</a>
                <span class="separator">/</span>
                <a href="{{ route('erp-exchange.index') }}">ERP Exchange</a>
                <span class="separator">/</span>
                <span class="current">{{ $confirmed ? 'Import Summary' : 'Review Import' }}</span>
            </nav>
            <h1>{{ $confirmed ? 'Product import summary' : 'Review product import' }}</h1>
            <p>Import <code style="font-size: 11.5px; background: #e2e8f0; padding: 2px 6px; border-radius: 4px;">{{ $importId }}</code> · {{ count($staged['validated_rows']) }} source rows processed</p>
        </div>
        <div class="page-actions-wrap">
            <a class="button secondary" href="{{ route('erp-exchange.index') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                <span>ERP exchange</span>
            </a>
            @if ($rejectedCount > 0 || (isset($staged['summary']) && $staged['summary']['rejected'] > 0))
                <a class="button secondary" href="{{ route('erp-exchange.products.errors', $importId) }}">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    <span>Download Error CSV</span>
                </a>
            @endif
        </div>
    </div>

    <!-- Status KPI summary cards -->
    <div style="display: flex; flex-wrap: wrap; gap: 14px; margin-bottom: 20px;">
        @if ($confirmed)
            <div class="stat-card" style="flex: 1; min-width: 200px;">
                <div class="stat-icon" style="background: rgba(16, 185, 129, 0.12); color: #10b981;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <div class="stat-body">
                    <span class="stat-title">Created SKUs</span>
                    <span class="stat-num badge-success">Created: {{ $staged['summary']['created'] }}</span>
                    <span class="stat-sub">New master products</span>
                </div>
            </div>

            <div class="stat-card" style="flex: 1; min-width: 200px;">
                <div class="stat-icon" style="background: rgba(37, 99, 235, 0.12); color: #2563eb;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                </div>
                <div class="stat-body">
                    <span class="stat-title">Updated SKUs</span>
                    <span class="stat-num badge-info">Updated: {{ $staged['summary']['updated'] }}</span>
                    <span class="stat-sub">Attributes refreshed</span>
                </div>
            </div>

            <div class="stat-card" style="flex: 1; min-width: 200px;">
                <div class="stat-icon" style="background: rgba(239, 68, 68, 0.12); color: #ef4444;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                </div>
                <div class="stat-body">
                    <span class="stat-title">Rejected Rows</span>
                    <span class="stat-num badge-danger">Rejected: {{ $staged['summary']['rejected'] }}</span>
                    <span class="stat-sub">Errors or duplicates</span>
                </div>
            </div>
        @else
            <div class="stat-card" style="flex: 1; min-width: 220px;">
                <div class="stat-icon" style="background: rgba(16, 185, 129, 0.12); color: #10b981;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <div class="stat-body">
                    <span class="stat-title">Verification Passed</span>
                    <span class="stat-num badge-success">Valid rows: {{ $validCount }}</span>
                    <span class="stat-sub">Ready to synchronize</span>
                </div>
            </div>

            <div class="stat-card" style="flex: 1; min-width: 220px;">
                <div class="stat-icon" style="background: rgba(239, 68, 68, 0.12); color: #ef4444;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                </div>
                <div class="stat-body">
                    <span class="stat-title">Invalid / Collision</span>
                    <span class="stat-num badge-danger">Rows with errors: {{ $rejectedCount }}</span>
                    <span class="stat-sub">Will be skipped safely</span>
                </div>
            </div>
        @endif
    </div>

    @unless ($confirmed)
        <div class="card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px 20px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <span style="display: inline-flex; width: 36px; height: 36px; border-radius: 8px; background: rgba(16, 185, 129, 0.1); color: #10b981; align-items: center; justify-content: center; flex-shrink: 0;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                </span>
                <div>
                    <strong style="font-size: 13.5px; color: #0f172a; display: block;">Safe Staged Execution</strong>
                    <span style="font-size: 12px; color: #64748b;">Only valid rows will be applied. Physical warehouse stock balances and movements cannot be changed by this import.</span>
                </div>
            </div>
            @if ($validCount > 0)
                <form method="post" action="{{ route('erp-exchange.products.confirm', $importId) }}" style="margin: 0;">
                    @csrf
                    <button type="submit" class="button" style="background: #10b981; color: #ffffff; padding: 9px 20px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        <span>Confirm & Apply Valid Rows</span>
                    </button>
                </form>
            @endif
        </div>
    @endunless

    <!-- Validated Rows Table Card -->
    <div class="modern-table-card">
        <div style="padding: 14px 18px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between;">
            <div>
                <h2 style="margin: 0; font-size: 15px; color: #0f172a;">Processed Rows Breakdown</h2>
                <p style="margin: 2px 0 0; font-size: 12px; color: #64748b;">Detailed row-by-row status and validation diagnostics</p>
            </div>
            <span class="pill-code">{{ count($staged['validated_rows']) }} Total</span>
        </div>
        <table class="clean-table">
            <thead>
                <tr>
                    <th style="width: 8%;">CSV row</th>
                    <th style="width: 14%;">Result</th>
                    <th style="width: 14%;">SAP material</th>
                    <th style="width: 14%;">SKU</th>
                    <th style="width: 20%;">Name</th>
                    <th style="width: 10%;">Category ID</th>
                    <th style="width: 8%;">Unit</th>
                    <th style="width: 12%;">Validation errors</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($staged['validated_rows'] as $row)
                    <tr>
                        <td style="font-family: monospace; font-size: 12px;">#{{ $row['row_number'] }}</td>
                        <td>
                            @if ($confirmed)
                                @if ($row['result'] === 'created')
                                    <span class="badge-success">Created</span>
                                @elseif ($row['result'] === 'updated')
                                    <span class="badge-info">Updated</span>
                                @else
                                    <span class="badge-danger">Rejected</span>
                                @endif
                            @else
                                @if ($row['errors'] === [])
                                    <span class="badge-success">{{ ($row['existing_product_id'] ?? null) === null ? 'Ready to create' : 'Ready to update' }}</span>
                                @else
                                    <span class="badge-danger">Rejected</span>
                                @endif
                            @endif
                        </td>
                        <td style="font-family: monospace; font-weight: 700; color: #0f172a;">{{ $row['data']['sap_material_number'] ?? '—' }}</td>
                        <td style="font-family: monospace; font-size: 12px;">{{ $row['data']['sku'] ?? '—' }}</td>
                        <td style="font-weight: 600; color: #1e293b;">{{ $row['data']['name'] ?? '—' }}</td>
                        <td>
                            @if (isset($row['data']['category_id']))
                                <span class="pill-code">#{{ $row['data']['category_id'] }}</span>
                            @else
                                <span style="color: #94a3b8;">—</span>
                            @endif
                        </td>
                        <td>{{ $row['data']['unit'] ?? '—' }}</td>
                        <td>
                            @if ($row['errors'] !== [])
                                <span style="color: #ef4444; font-size: 11.5px; font-weight: 600; line-height: 1.3; display: block;">
                                    {{ implode('; ', $row['errors']) }}
                                </span>
                            @else
                                <span style="color: #10b981; font-size: 12px; font-weight: 600;">✓ Pass</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
