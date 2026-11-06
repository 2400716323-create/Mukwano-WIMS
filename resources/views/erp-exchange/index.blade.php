@extends('catalog.layout')

@section('title', 'ERP exchange')

@section('breadcrumbs')
    <span class="current">ERP Exchange</span>
@endsection

@section('content')
    <!-- Unified Page Header -->
    <div class="page-header-card">
        <div class="page-title-wrap">
            <nav class="breadcrumb-trail" aria-label="Breadcrumb">
                <a href="{{ route('dashboard') }}">Dashboard</a>
                <span class="separator">/</span>
                <span class="current">ERP Exchange</span>
            </nav>
            <h1>ERP exchange</h1>
            <p>Offline SAP file exchange only. Imports update product-master data; inventory balances and movements are never imported or overwritten.</p>
        </div>
        <div class="page-actions-wrap">
            <a class="button secondary" href="{{ route('erp-exchange.reconciliation.csv') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                <span>Stock Reconciliation CSV</span>
            </a>
        </div>
    </div>

    <!-- Import Form Card -->
    <div class="card" style="background: #ffffff; border-radius: 10px; border: 1px solid #e2e8f0; padding: 24px; margin-bottom: 20px;">
        <h2 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0 0 8px 0;">Product-master CSV import</h2>
        <p style="font-size: 13px; color: #64748b; margin: 0 0 18px 0;">Upload a UTF-8 CSV (maximum 5 MB and 1,000 data rows), map its columns, review validation and duplicates, then confirm valid rows.</p>
        
        <form method="post" action="{{ route('erp-exchange.products.preview') }}" enctype="multipart/form-data">
            @csrf
            <div class="field" style="margin-bottom: 18px;">
                <label for="file" style="display: block; font-size: 12.5px; font-weight: 600; color: #334155; margin-bottom: 6px;">Select CSV file <span style="color: #ef4444;">*</span></label>
                <input id="file" type="file" name="file" accept=".csv,text/csv,text/plain" required
                       style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; font-size: 13px; background: #f8fafc;">
            </div>
            <button type="submit" class="button" style="background: #2563eb; color: #ffffff; padding: 8px 20px; font-weight: 600;">
                Upload and map columns
            </button>
        </form>
    </div>

    <!-- Information Card -->
    <div class="card" style="background: #ffffff; border-radius: 10px; border: 1px solid #e2e8f0; padding: 24px;">
        <h2 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0 0 8px 0;">Product fields</h2>
        <p style="font-size: 13px; color: #475569; margin: 0 0 10px 0;">New products require a SAP material number, name, category ID, and unit. SKU defaults to the SAP material number. Unmapped master fields keep existing values; stock fields are not importable.</p>
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px 14px; font-size: 12.5px; color: #64748b;">
            <strong style="color: #334155;">Available active category IDs:</strong> {{ $categories->pluck('id', 'name')->map(fn ($id, $name) => "{$name}: {$id}")->implode(', ') ?: 'none' }}
        </div>
    </div>
@endsection
