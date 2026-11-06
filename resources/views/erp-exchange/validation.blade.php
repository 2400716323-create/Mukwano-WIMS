@extends('catalog.layout')

@section('title', $confirmed ? 'Import summary' : 'Review product import')

@section('breadcrumbs')
    <a href="{{ route('erp-exchange.index') }}">ERP Exchange</a>
    <span class="sep">/</span>
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
            <p>Import {{ $importId }} · {{ count($staged['validated_rows']) }} source rows processed</p>
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
    <div style="display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 20px;">
        @if ($confirmed)
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 16px; display: inline-flex; align-items: center; gap: 8px;">
                <span class="badge-success">Created: {{ $staged['summary']['created'] }}</span>
            </div>
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 16px; display: inline-flex; align-items: center; gap: 8px;">
                <span class="badge-info">Updated: {{ $staged['summary']['updated'] }}</span>
            </div>
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 16px; display: inline-flex; align-items: center; gap: 8px;">
                <span class="badge-danger">Rejected: {{ $staged['summary']['rejected'] }}</span>
            </div>
        @else
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 16px; display: inline-flex; align-items: center; gap: 8px;">
                <span class="badge-success">Valid rows: {{ $validCount }}</span>
            </div>
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 16px; display: inline-flex; align-items: center; gap: 8px;">
                <span class="badge-danger">Rows with errors: {{ $rejectedCount }}</span>
            </div>
        @endif
    </div>

    @unless ($confirmed)
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px 18px; margin-bottom: 20px; font-size: 13px; color: #64748b; display: flex; align-items: center; justify-content: space-between;">
            <span>Only valid rows will be applied. No live stock balance or stock movement can be changed by this import.</span>
            @if ($validCount > 0)
                <form method="post" action="{{ route('erp-exchange.products.confirm', $importId) }}" style="margin: 0;">
                    @csrf
                    <button type="submit" class="button" style="background: #10b981; color: #ffffff; padding: 6px 16px; font-weight: 600;">Confirm & Apply Valid Rows</button>
                </form>
            @endif
        </div>
    @endunless

    <!-- Validated Rows Table Card -->
    <div class="modern-table-card">
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
                        <td>{{ $row['row_number'] }}</td>
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
                                    <span class="badge-success">{{ $row['existing_product_id'] === null ? 'Ready to create' : 'Ready to update' }}</span>
                                @else
                                    <span class="badge-danger">Rejected</span>
                                @endif
                            @endif
                        </td>
                        <td><span style="font-family: monospace; font-weight: 700; color: #2563eb;">{{ $row['data']['sap_material_number'] ?? '-' }}</span></td>
                        <td><span class="pill-code">{{ $row['data']['sku'] ?? '-' }}</span></td>
                        <td><strong style="color: #0f172a;">{{ $row['data']['name'] ?? '-' }}</strong></td>
                        <td>{{ $row['data']['category_id'] ?? '-' }}</td>
                        <td>{{ $row['data']['unit'] ?? '-' }}</td>
                        <td>
                            @if ($row['errors'] !== [])
                                <span style="color: #ef4444; font-size: 11.5px; font-weight: 500;">{{ implode('; ', $row['errors']) }}</span>
                            @else
                                <span style="color: #10b981; font-size: 12px;">Valid</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
