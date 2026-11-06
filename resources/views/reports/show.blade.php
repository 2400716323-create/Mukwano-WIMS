@extends('catalog.layout')

@section('title', $report['title'])

@section('breadcrumbs')
    <a href="{{ route('reports.index') }}">Reports & Analytics</a>
    <span class="sep">/</span>
    <span class="current">{{ $report['title'] }}</span>
@endsection

@section('content')
    <!-- Unified Page Header -->
    <div class="page-header-card">
        <div class="page-title-wrap">
            <nav class="breadcrumb-trail" aria-label="Breadcrumb">
                <a href="{{ route('dashboard') }}">Dashboard</a>
                <span class="separator">/</span>
                <a href="{{ route('reports.index') }}">Reports & Analytics</a>
                <span class="separator">/</span>
                <span class="current">{{ $report['title'] }}</span>
            </nav>
            <h1>{{ $report['title'] }}</h1>
            <p>{{ number_format(count($report['rows'])) }} records match the selected operational filters.</p>
        </div>
        <div class="page-actions-wrap">
            <a class="button secondary" href="{{ route('reports.index') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                <span>All reports</span>
            </a>
            <a class="button secondary" href="{{ route('reports.csv', ['report' => $report['key']] + $filters) }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                <span>Download CSV</span>
            </a>
            <a class="button" href="{{ route('reports.pdf', ['report' => $report['key']] + $filters) }}" style="background: #2563eb; color: #ffffff;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                <span>Download PDF</span>
            </a>
        </div>
    </div>

    <!-- Filter Form Card -->
    <div class="card" style="background: #ffffff; border-radius: 10px; border: 1px solid #e2e8f0; padding: 20px; margin-bottom: 20px;">
        <form method="get" action="{{ route('reports.show', $report['key']) }}">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; align-items: flex-end;">
                @if (in_array($report['key'], ['goods-received', 'goods-dispatched', 'movement', 'transfers', 'expiry-ageing', 'cycle-count', 'purchase-orders', 'audit-log'], true))
                    <div class="field">
                        <label for="from" style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 4px;">{{ $report['key'] === 'expiry-ageing' ? 'Expiry from' : 'From date' }}</label>
                        <input id="from" type="date" name="from" value="{{ $filters['from'] ?? '' }}"
                               style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 10px; font-size: 13px;">
                    </div>
                    <div class="field">
                        <label for="to" style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 4px;">{{ $report['key'] === 'expiry-ageing' ? 'Expiry to' : 'To date' }}</label>
                        <input id="to" type="date" name="to" value="{{ $filters['to'] ?? '' }}"
                               style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 10px; font-size: 13px;">
                    </div>
                @endif

                <div class="field">
                    <label for="product_id" style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 4px;">Product</label>
                    <select id="product_id" name="product_id" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 10px; font-size: 13px;">
                        <option value="">All products</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" @selected(($filters['product_id'] ?? '') == $product->id)>
                                {{ $product->sku }} · {{ $product->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                @if (in_array($report['key'], ['stock-on-hand', 'goods-received', 'goods-dispatched', 'movement', 'transfers', 'expiry-ageing', 'low-stock', 'cycle-count', 'ira'], true))
                    <div class="field">
                        <label for="batch_id" style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 4px;">Batch</label>
                        <select id="batch_id" name="batch_id" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 10px; font-size: 13px;">
                            <option value="">All batches</option>
                            @foreach ($batches as $batch)
                                <option value="{{ $batch->id }}" @selected(($filters['batch_id'] ?? '') == $batch->id)>
                                    {{ $batch->batch_number }} · {{ $batch->product->sku }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field">
                        <label for="location_id" style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 4px;">Location</label>
                        <select id="location_id" name="location_id" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 10px; font-size: 13px;">
                            <option value="">All locations</option>
                            @foreach ($locations as $location)
                                <option value="{{ $location->id }}" @selected(($filters['location_id'] ?? '') == $location->id)>
                                    {{ $location->code }} · {{ $location->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if (in_array($report['key'], ['purchase-orders', 'cycle-count'], true))
                    <div class="field">
                        <label for="status" style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 4px;">Status</label>
                        <select id="status" name="status" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 10px; font-size: 13px;">
                            <option value="">All statuses</option>
                            @foreach (['draft', 'approved', 'partially_received', 'received', 'cancelled', 'submitted', 'rejected'] as $status)
                                <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>
                                    {{ str($status)->replace('_', ' ')->title() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div style="display: flex; gap: 8px;">
                    <button type="submit" class="button" style="background: #2563eb; color: #ffffff; padding: 6px 16px; font-size: 13px;">Apply filters</button>
                    <a class="button secondary" href="{{ route('reports.show', $report['key']) }}" style="padding: 6px 12px; font-size: 13px;">Clear</a>
                </div>
            </div>
        </form>
    </div>

    @if (!empty($report['totals']))
        <!-- Totals summary strip -->
        <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 16px;">
            @foreach ($report['totals'] as $label => $value)
                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 14px; display: inline-flex; align-items: center; gap: 8px;">
                    <span style="font-size: 11.5px; text-transform: uppercase; font-weight: 700; color: #64748b; letter-spacing: 0.04em;">{{ str($label)->replace('_', ' ')->title() }}:</span>
                    <span style="font-size: 13.5px; font-weight: 700; color: #0f172a;">{{ $value }}</span>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Report Data Table -->
    <div class="modern-table-card">
        <table class="clean-table">
            <thead>
                <tr>
                    @foreach ($report['columns'] as $label)
                        <th>{{ $label }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($report['rows'] as $row)
                    <tr>
                        @foreach ($report['columns'] as $key => $label)
                            <td>{{ $row[$key] ?? '-' }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($report['columns']) }}" style="text-align: center; padding: 32px 20px; color: #64748b;">
                            No records match the selected filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
