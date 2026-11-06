@extends('catalog.layout')

@section('title', 'Cycle counts')

@section('breadcrumbs')
    <span class="current">Cycle Counts</span>
@endsection

@section('content')
    <!-- Unified Page Header -->
    <div class="page-header-card">
        <div class="page-title-wrap">
            <nav class="breadcrumb-trail" aria-label="Breadcrumb">
                <a href="{{ route('dashboard') }}">Dashboard</a>
                <span class="separator">/</span>
                <span class="current">Cycle Counts</span>
            </nav>
            <h1>Cycle Counts</h1>
            <p>Conduct blind physical audits by bin, product, or batch. Variances require supervisor verification.</p>
        </div>
        <div class="page-actions-wrap">
            @if (auth()->user()->isSupervisor())
                <a class="button secondary" href="{{ route('stock-counts.settings') }}">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    <span>Count Settings</span>
                </a>
            @endif
            <a class="button" href="{{ route('stock-counts.create') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                <span>Create Count List</span>
            </a>
        </div>
    </div>

    <!-- 4 Top KPI Cards -->
    <div class="stat-grid-4">
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(37, 99, 235, 0.12); color: #2563eb;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Total Audits</span>
                <span class="stat-num">{{ number_format($metrics['total'] ?? $stockCounts->total()) }}</span>
                <span class="stat-sub">Lifetime count sheets</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(245, 158, 11, 0.12); color: #f59e0b;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Pending Approval</span>
                <span class="stat-num">{{ number_format($metrics['pending_review'] ?? 0) }}</span>
                <span class="stat-sub">Submitted variance reviews</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(16, 185, 129, 0.12); color: #10b981;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Approved & Posted</span>
                <span class="stat-num">{{ number_format($metrics['approved'] ?? 0) }}</span>
                <span class="stat-sub">Adjustments synchronized</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(100, 116, 139, 0.12); color: #64748b;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">In Progress Drafts</span>
                <span class="stat-num">{{ number_format($metrics['in_progress'] ?? 0) }}</span>
                <span class="stat-sub">Counting underway</span>
            </div>
        </div>
    </div>

    <!-- Modern Table Card -->
    <div class="modern-table-card">
        <table class="clean-table">
            <thead>
                <tr>
                    <th style="width: 10%;">Count ID</th>
                    <th style="width: 20%;">Bin Location</th>
                    <th style="width: 20%;">Product / Scope</th>
                    <th style="width: 10%;">Status</th>
                    <th style="width: 12%;">Created By</th>
                    <th style="width: 8%; text-align: right;">Records</th>
                    <th style="width: 10%; text-align: right;">Discrepancies</th>
                    <th style="width: 10%; text-align: right;">Accuracy</th>
                    <th style="width: 6%; text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($stockCounts as $stockCount)
                @php
                    $accuracy = $accuracyByCount[$stockCount->id];
                @endphp
                <tr>
                    <td>
                        <a href="{{ route('stock-counts.show', $stockCount) }}" style="font-family: monospace; font-weight: 700; color: #2563eb; text-decoration: none;">
                            SC-{{ $stockCount->id }}
                        </a>
                    </td>
                    <td>
                        <a href="{{ route('locations.show', $stockCount->location) }}" style="font-weight: 600; color: #0f172a; text-decoration: none;">
                            {{ $stockCount->location->code }}
                        </a>
                        <small style="display: block; color: #64748b; font-size: 11px;">{{ $stockCount->location->name }}</small>
                    </td>
                    <td>
                        <span style="font-size: 12.5px; color: #334155; font-weight: 500;">
                            {{ $stockCount->product?->sku ?? 'All products' }}
                            @if ($stockCount->batch)
                                <span class="pill-code" style="font-size: 10.5px; margin-left: 4px;">{{ $stockCount->batch->batch_number }}</span>
                            @endif
                        </span>
                    </td>
                    <td>
                        @php
                            $statusBadge = match($stockCount->status) {
                                'approved' => 'badge-success',
                                'submitted' => 'badge-warning',
                                'rejected' => 'badge-danger',
                                default => 'badge-info',
                            };
                        @endphp
                        <span class="{{ $statusBadge }}">{{ ucfirst($stockCount->status) }}</span>
                    </td>
                    <td>
                        <span style="color: #64748b; font-size: 12.5px;">{{ $stockCount->creator->name }}</span>
                    </td>
                    <td style="text-align: right; font-weight: 600;">
                        {{ $accuracy['counted'] }}
                    </td>
                    <td style="text-align: right; font-weight: 700; color: {{ $accuracy['discrepancies'] > 0 ? '#dc2626' : '#16a34a' }};">
                        {{ $accuracy['discrepancies'] }}
                    </td>
                    <td style="text-align: right; font-weight: 700; font-size: 13px; color: {{ $accuracy['accuracy_percent'] >= 98 ? '#16a34a' : ($accuracy['accuracy_percent'] >= 90 ? '#ea580c' : '#dc2626') }};">
                        {{ number_format($accuracy['accuracy_percent'], 1) }}%
                    </td>
                    <td style="text-align: right;">
                        <a href="{{ route('stock-counts.show', $stockCount) }}" class="button secondary" style="padding: 4px 10px; font-size: 12px;">Open</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align: center; padding: 36px 20px; color: #64748b;">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" style="margin-bottom: 8px;"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                        <p style="margin: 0; font-weight: 600; color: #334155;">No cycle counts scheduled</p>
                        <small style="color: #94a3b8;">Start a physical inventory check by clicking "Create count list".</small>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div style="margin-top: 14px;">
        {{ $stockCounts->links() }}
    </div>
@endsection
