@extends('catalog.layout')

@section('title', 'Map product-master columns')

@section('breadcrumbs')
    <a href="{{ route('erp-exchange.index') }}">ERP Exchange</a>
    <span class="sep">/</span>
    <span class="current">Column Mapping</span>
@endsection

@section('content')
    <!-- Unified Page Header -->
    <div class="page-header-card">
        <div class="page-title-wrap">
            <nav class="breadcrumb-trail" aria-label="Breadcrumb">
                <a href="{{ route('dashboard') }}">Dashboard</a>
                <span class="separator">/</span>
                <a href="{{ route('erp-exchange.index') }}">ERP Exchange</a>
                <span class="separator">/</span>
                <span class="current">Column Mapping</span>
            </nav>
            <h1>Map product-master columns</h1>
            <p>Import {{ $import_id }} · {{ count($sample_rows) }} sample rows shown · {{ count($headers) }} CSV columns detected</p>
        </div>
        <div class="page-actions-wrap">
            <a class="button secondary" href="{{ route('erp-exchange.index') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                <span>Cancel</span>
            </a>
        </div>
    </div>

    <!-- Mapping Form Card -->
    <div class="card" style="background: #ffffff; border-radius: 10px; border: 1px solid #e2e8f0; padding: 24px; margin-bottom: 24px;">
        <p style="font-size: 13px; color: #475569; margin: 0 0 20px 0;">Map SAP material number and product name. For a new product, category ID and unit are also required. Existing products are matched by SAP material number; unmapped fields are left unchanged.</p>
        
        <form method="post" action="{{ route('erp-exchange.products.validate', $import_id) }}">
            @csrf
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 22px;">
                @foreach ($fields as $field)
                    <div class="field">
                        <label for="mapping-{{ $field }}" style="display: block; font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 4px;">{{ str($field)->replace('_', ' ')->title() }}</label>
                        <select id="mapping-{{ $field }}" name="mapping[{{ $field }}]" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 7px 10px; font-size: 13px;">
                            <option value="">Do not map</option>
                            @foreach ($headers as $header)
                                <option value="{{ $header }}" @selected(($suggested_mapping[$field] ?? '') === $header)>
                                    {{ $header }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endforeach
            </div>
            <button type="submit" class="button" style="background: #2563eb; color: #ffffff; padding: 8px 20px; font-weight: 600;">Validate rows</button>
        </form>
    </div>

    <!-- Preview Table Card -->
    <div class="modern-table-card">
        <div style="padding: 14px 18px; border-bottom: 1px solid #f1f5f9;">
            <h2 style="margin: 0; font-size: 15px; color: #0f172a;">Upload preview</h2>
        </div>
        <table class="clean-table">
            <thead>
                <tr>
                    @foreach ($headers as $header)
                        <th>{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($sample_rows as $row)
                    <tr>
                        @foreach ($row as $value)
                            <td>{{ $value }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
