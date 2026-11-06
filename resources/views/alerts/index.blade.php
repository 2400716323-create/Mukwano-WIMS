@extends('catalog.layout')

@section('title', 'Alert centre')

@section('breadcrumbs')
    <span class="current">Alert Centre</span>
@endsection

@section('content')
    <!-- Unified Page Header -->
    <div class="page-header-card">
        <div class="page-title-wrap">
            <nav class="breadcrumb-trail" aria-label="Breadcrumb">
                <a href="{{ route('dashboard') }}">Dashboard</a>
                <span class="separator">/</span>
                <span class="current">Alert Centre</span>
            </nav>
            <h1>Alert Centre</h1>
            <p>Operational exception monitor: low safety stock, nearing expirations, and awaiting approvals.</p>
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
            <div class="stat-icon" style="background: rgba(239, 68, 68, 0.12); color: #ef4444;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Critical Severities</span>
                <span class="stat-num">{{ number_format($metrics['critical'] ?? 0) }}</span>
                <span class="stat-sub">Immediate action needed</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(245, 158, 11, 0.12); color: #f59e0b;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">High Priority</span>
                <span class="stat-num">{{ number_format($metrics['high'] ?? 0) }}</span>
                <span class="stat-sub">Elevated risk items</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(37, 99, 235, 0.12); color: #2563eb;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Low Stock SKUs</span>
                <span class="stat-num">{{ number_format($metrics['low_stock'] ?? 0) }}</span>
                <span class="stat-sub">Under reorder threshold</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(124, 58, 237, 0.12); color: #7c3aed;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Expiry Notices</span>
                <span class="stat-num">{{ number_format($metrics['expiry'] ?? 0) }}</span>
                <span class="stat-sub">Lots approaching end-of-life</span>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="filter-card">
        <form method="GET" action="{{ route('alerts.index') }}" style="display: flex; align-items: center; gap: 8px; width: 100%; flex-wrap: wrap; margin: 0;">
            <select id="state" name="state" style="font-size: 12px; padding: 6px 10px;">
                @foreach (['open' => 'Unresolved Only', 'resolved' => 'Resolved Only', 'all' => 'All Alerts'] as $value => $label)
                    <option value="{{ $value }}" @selected($state === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select id="type" name="type" style="font-size: 12px; padding: 6px 10px;">
                <option value="">All Conditions</option>
                @foreach (['low_stock' => 'Low Stock', 'expiry' => 'Near Expiry / Expired', 'pending_purchase_order' => 'Pending PO Approval', 'pending_stock_count' => 'Pending Count Approval'] as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['type'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select id="severity" name="severity" style="font-size: 12px; padding: 6px 10px;">
                <option value="">All Severities</option>
                @foreach (['critical', 'high', 'medium', 'low'] as $sev)
                    <option value="{{ $sev }}" @selected(($filters['severity'] ?? '') === $sev)>{{ ucfirst($sev) }}</option>
                @endforeach
            </select>
            <button type="submit" class="button" style="padding: 6px 14px; font-size: 12px;">Filter</button>
            @if (!empty($filters['type']) || !empty($filters['severity']) || ($filters['state'] ?? 'open') !== 'open')
                <a class="button secondary" href="{{ route('alerts.index') }}" style="padding: 6px 12px; font-size: 12px;">Reset</a>
            @endif
        </form>
    </div>

    <!-- Modern Table Card -->
    <div class="modern-table-card">
        <table class="clean-table">
            <thead>
                <tr>
                    <th style="width: 10%;">Severity</th>
                    <th style="width: 16%;">Condition</th>
                    <th style="width: 42%;">Alert Details & Recommended Action</th>
                    <th style="width: 14%;">Detected At</th>
                    <th style="width: 10%;">Status</th>
                    <th style="width: 8%; text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($alerts as $alert)
                <tr>
                    <td>
                        @php
                            $sevClass = match($alert->severity) {
                                'critical' => 'badge-danger',
                                'high' => 'badge-warning',
                                'medium' => 'badge-info',
                                default => 'badge-muted',
                            };
                        @endphp
                        <span class="{{ $sevClass }}" style="font-weight: 700;">{{ strtoupper($alert->severity) }}</span>
                    </td>
                    <td>
                        <span style="font-weight: 600; color: #0f172a; font-size: 12.5px;">{{ ucwords(str_replace('_', ' ', $alert->type)) }}</span>
                    </td>
                    <td>
                        <div style="display: flex; flex-direction: column; gap: 2px;">
                            <strong style="color: #0f172a; font-size: 13px;">{{ $alert->title }}</strong>
                            <p style="margin: 0; color: #64748b; font-size: 12px; line-height: 1.4;">{{ $alert->description }}</p>
                            @if ($alert->action_url)
                                <a href="{{ $alert->action_url }}" style="font-size: 11.5px; color: #2563eb; font-weight: 600; text-decoration: none; margin-top: 3px; display: inline-flex; align-items: center; gap: 4px;">
                                    <span>View Related Record</span>
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                                </a>
                            @endif
                        </div>
                    </td>
                    <td>
                        <span style="color: #64748b; font-size: 12px;">{{ $alert->last_seen_at->format('d M Y, H:i') }}</span>
                    </td>
                    <td>
                        @if ($alert->resolved_at)
                            <span class="badge-success">Resolved</span>
                        @elseif ($alert->condition_active)
                            <span class="badge-danger">Active</span>
                        @else
                            <span class="badge-muted">Cleared</span>
                        @endif
                    </td>
                    <td style="text-align: right;">
                        @if ($alert->resolved_at === null && $alert->condition_active)
                            <form method="POST" action="{{ route('alerts.resolve', $alert) }}" style="margin: 0;">
                                @csrf
                                @method('PATCH')
                                <button class="button secondary" type="submit" style="padding: 4px 8px; font-size: 11.5px; color: #16a34a;">Resolve</button>
                            </form>
                        @else
                            <span style="color: #94a3b8;">—</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 36px 20px; color: #64748b;">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" style="margin-bottom: 8px;"><polyline points="20 6 9 17 4 12"/></svg>
                        <p style="margin: 0; font-weight: 600; color: #334155;">No unresolved alerts detected</p>
                        <small style="color: #94a3b8;">Operational stock parameters are nominal.</small>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div style="margin-top: 14px;">
        {{ $alerts->links() }}
    </div>
@endsection
