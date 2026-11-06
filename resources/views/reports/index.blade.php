@extends('catalog.layout')

@section('title', 'Reports')

@section('breadcrumbs')
    <span class="current">Reports & Analytics</span>
@endsection

@section('content')
    <!-- Unified Page Header -->
    <div class="page-header-card">
        <div class="page-title-wrap">
            <nav class="breadcrumb-trail" aria-label="Breadcrumb">
                <a href="{{ route('dashboard') }}">Dashboard</a>
                <span class="separator">/</span>
                <span class="current">Reports & Analytics</span>
            </nav>
            <h1>Reports & Analytics</h1>
            <p>Exportable operational reporting calculated from real-time stock balances and transaction ledger records.</p>
        </div>
        <div class="page-actions-wrap">
            <a class="button secondary" href="{{ route('dashboard') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                <span>Dashboard</span>
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
                <span class="stat-title">Standard Reports</span>
                <span class="stat-num">{{ count($reports) }}</span>
                <span class="stat-sub">Audited reporting modules</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(16, 185, 129, 0.12); color: #10b981;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Stock Valuation</span>
                <span class="stat-num">Live</span>
                <span class="stat-sub">FIFO / FEFO valuation</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(245, 158, 11, 0.12); color: #f59e0b;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Export Formats</span>
                <span class="stat-num">PDF · CSV</span>
                <span class="stat-sub">Direct file downloads</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(124, 58, 237, 0.12); color: #7c3aed;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Audit Trail</span>
                <span class="stat-num">100%</span>
                <span class="stat-sub">Full chain of custody</span>
            </div>
        </div>
    </div>

    <!-- Reports Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 14px;">
        @foreach ($reports as $key => $title)
            @php
                $color = match($key) {
                    'stock-on-hand' => ['#10b981', 'rgba(16, 185, 129, 0.12)'],
                    'goods-received' => ['#2563eb', 'rgba(37, 99, 235, 0.12)'],
                    'goods-dispatched' => ['#7c3aed', 'rgba(124, 58, 237, 0.12)'],
                    'expiry-ageing' => ['#f59e0b', 'rgba(245, 158, 11, 0.12)'],
                    'low-stock' => ['#ef4444', 'rgba(239, 68, 68, 0.12)'],
                    'cycle-count' => ['#0284c7', 'rgba(2, 132, 199, 0.12)'],
                    default => ['#475569', '#f1f5f9'],
                };
            @endphp
            <div class="card" style="display: flex; flex-direction: column; justify-content: space-between; padding: 18px;">
                <div>
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                        <div style="width: 38px; height: 38px; border-radius: 8px; background: {{ $color[1] }}; color: {{ $color[0] }}; display: flex; align-items: center; justify-content: center;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        </div>
                        <span class="pill-code" style="font-size: 10px;">{{ $key }}</span>
                    </div>
                    <h2 style="font-size: 15px; font-weight: 700; color: #0f172a; margin: 0 0 6px;">{{ $title }}</h2>
                    <p style="font-size: 12px; color: #64748b; margin: 0 0 16px; line-height: 1.4;">
                        Generate detailed summary and analytical breakdown for {{ strtolower($title) }}.
                    </p>
                </div>
                <div style="display: flex; align-items: center; gap: 8px; border-top: 1px solid #f1f5f9; padding-top: 12px;">
                    <a class="button" href="{{ route('reports.show', $key) }}" style="flex: 1; padding: 6px 12px; font-size: 12px; background: #2563eb;">
                        <span>View Online</span>
                    </a>
                    <a class="button secondary" href="{{ route('reports.pdf', $key) }}" style="padding: 6px 10px; font-size: 11px; color: #dc2626;" title="Download PDF">
                        PDF
                    </a>
                    <a class="button secondary" href="{{ route('reports.csv', $key) }}" style="padding: 6px 10px; font-size: 11px; color: #16a34a;" title="Export CSV">
                        CSV
                    </a>
                </div>
            </div>
        @endforeach
    </div>
@endsection
