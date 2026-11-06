@extends('catalog.layout')

@section('title', 'Map product-master columns')

@section('breadcrumbs')
    <a href="{{ route('erp-exchange.index') }}">ERP Exchange</a>
    <span class="separator">/</span>
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
            <p>Import <code style="font-size: 11.5px; background: #e2e8f0; padding: 2px 6px; border-radius: 4px;">{{ $import_id }}</code> · {{ count($sample_rows) }} sample rows shown · {{ count($headers) }} CSV columns detected</p>
        </div>
        <div class="page-actions-wrap">
            <a class="button secondary" href="{{ route('erp-exchange.index') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                <span>Cancel Import</span>
            </a>
        </div>
    </div>

    <!-- Mapping Form Card -->
    <div class="card" style="background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 24px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
        <div style="margin-bottom: 18px; padding-bottom: 14px; border-bottom: 1px solid #f1f5f9;">
            <h2 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0 0 4px 0;">Target Schema Mapping</h2>
            <p style="font-size: 12.5px; color: #64748b; margin: 0;">Map your CSV columns to the WIMS master product fields. Existing products are matched by SAP material number; unmapped fields retain existing values.</p>
        </div>
        
        <form method="post" action="{{ route('erp-exchange.products.validate', $import_id) }}">
            @csrf
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 18px; margin-bottom: 24px;">
                @foreach ($fields as $field)
                    <div class="field" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px;">
                        <label for="mapping-{{ $field }}" style="display: flex; align-items: center; justify-content: space-between; font-size: 12.5px; font-weight: 700; color: #0f172a; margin-bottom: 6px;">
                            <span>{{ str($field)->replace('_', ' ')->title() }}</span>
                            @if (in_array($field, ['sap_material_number', 'name']))
                                <span style="color: #ef4444; font-size: 11px;">Required</span>
                            @endif
                        </label>
                        <select id="mapping-{{ $field }}" name="mapping[{{ $field }}]" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 7px 10px; font-size: 13px; background: #ffffff;">
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

            <div style="display: flex; justify-content: flex-end; padding-top: 14px; border-top: 1px solid #f1f5f9;">
                <button type="submit" class="button" style="background: #2563eb; color: #ffffff; padding: 9px 24px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                    <span>Validate rows</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Preview Table Card -->
    <div class="modern-table-card">
        <div style="padding: 14px 18px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between;">
            <div>
                <h2 style="margin: 0; font-size: 15px; color: #0f172a;">Source CSV Preview</h2>
                <p style="margin: 2px 0 0; font-size: 12px; color: #64748b;">Displaying the first {{ count($sample_rows) }} records from your uploaded file</p>
            </div>
            <span class="pill-code">{{ count($headers) }} Columns</span>
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
                            <td style="font-family: monospace; font-size: 12px;">{{ $value }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
