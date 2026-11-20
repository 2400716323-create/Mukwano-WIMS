 @extends('catalog.layout')

@section('title', 'Alert centre')

@section('breadcrumbs')
    <span class="current">Alert Centre</span>
@endsection

@section('content')
    <!-- Page Header -->
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
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7"/>
                    <rect x="14" y="3" width="7" height="7"/>
                    <rect x="14" y="14" width="7" height="7"/>
                    <rect x="3" y="14" width="7" height="7"/>
                </svg>
                <span>Dashboard</span>
            </a>
        </div>
    </div>

    <!-- Main Alert KPI Cards -->
    <div class="stat-grid-4">
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(239, 68, 68, 0.12); color: #ef4444;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Critical Severities</span>
                <span class="stat-num">{{ number_format($metrics['critical'] ?? 0) }}</span>
                <span class="stat-sub">Immediate action needed</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(245, 158, 11, 0.12); color: #f59e0b;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/>
                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">High Priority</span>
                <span class="stat-num">{{ number_format($metrics['high'] ?? 0) }}</span>
                <span class="stat-sub">Elevated risk items</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(37, 99, 235, 0.12); color: #2563eb;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4a2 2 0 0 0 1-1.73z"/>
                </svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Low Stock SKUs</span>
                <span class="stat-num">{{ number_format($metrics['low_stock'] ?? 0) }}</span>
                <span class="stat-sub">Under reorder threshold</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(124, 58, 237, 0.12); color: #7c3aed;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                </svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Expiry Notices</span>
                <span class="stat-num">{{ number_format($metrics['expiry'] ?? 0) }}</span>
                <span class="stat-sub">Lots approaching end-of-life</span>
            </div>
        </div>
    </div>

    <!-- Phase 9: Near-Expiry Overview -->
    <div class="filter-card" style="margin-top: 18px; margin-bottom: 16px;">
        <div style="margin-bottom: 14px;">
            <h2 style="font-size: 16px; margin: 0 0 5px; color: #0f172a;">
                Near-Expiry Overview
            </h2>
            <p style="font-size: 12px; color: #64748b; margin: 0;">
                Monitor stock approaching its expiry date. Expired stock is displayed separately
                from upcoming expiry warnings.
            </p>
        </div>

        <div class="stat-grid-4">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(220, 38, 38, 0.12); color: #dc2626;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="8" y1="8" x2="16" y2="16"/>
                        <line x1="16" y1="8" x2="8" y2="16"/>
                    </svg>
                </div>
                <div class="stat-body">
                    <span class="stat-title">Expired Stock</span>
                    <span class="stat-num" style="color: #dc2626;">
                        {{ number_format($metrics['expired'] ?? 0) }}
                    </span>
                    <span class="stat-sub">Already past expiry date</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(234, 88, 12, 0.12); color: #ea580c;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 6v6l4 2"/>
                    </svg>
                </div>
                <div class="stat-body">
                    <span class="stat-title">Within 30 Days</span>
                    <span class="stat-num" style="color: #ea580c;">
                        {{ number_format($metrics['expiry_30'] ?? 0) }}
                    </span>
                    <span class="stat-sub">Urgent expiry warning</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(217, 119, 6, 0.12); color: #d97706;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 6v6l4 2"/>
                    </svg>
                </div>
                <div class="stat-body">
                    <span class="stat-title">Within 60 Days</span>
                    <span class="stat-num" style="color: #d97706;">
                        {{ number_format($metrics['expiry_60'] ?? 0) }}
                    </span>
                    <span class="stat-sub">Upcoming expiry warning</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(37, 99, 235, 0.12); color: #2563eb;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 6v6l4 2"/>
                    </svg>
                </div>
                <div class="stat-body">
                    <span class="stat-title">Within 90 Days</span>
                    <span class="stat-num" style="color: #2563eb;">
                        {{ number_format($metrics['expiry_90'] ?? 0) }}
                    </span>
                    <span class="stat-sub">Expiry notice</span>
                </div>
            </div>
        </div>

        <div style="margin-top: 14px;">
            <a class="button secondary"
               href="{{ route('alerts.expiring') }}">
                View All Expiry Alerts
            </a>
        </div>
    </div>

    <!-- Alert Filters -->
    <div class="filter-card">
        <form method="GET"
              action="{{ route('alerts.index') }}"
              style="display: flex; align-items: center; gap: 8px; width: 100%; flex-wrap: wrap;">

            <select name="type" class="form-control">
                <option value="">All Alert Types</option>
                <option value="low_stock" @selected(($filters['type'] ?? '') === 'low_stock')>Low Stock</option>
                <option value="expiry" @selected(($filters['type'] ?? '') === 'expiry')>Expiry</option>
                <option value="pending_purchase_order" @selected(($filters['type'] ?? '') === 'pending_purchase_order')>Pending Purchase Orders</option>
                <option value="pending_stock_count" @selected(($filters['type'] ?? '') === 'pending_stock_count')>Pending Stock Counts</option>
            </select>

            <select name="severity" class="form-control">
                <option value="">All Severities</option>
                <option value="critical" @selected(($filters['severity'] ?? '') === 'critical')>Critical</option>
                <option value="high" @selected(($filters['severity'] ?? '') === 'high')>High</option>
                <option value="medium" @selected(($filters['severity'] ?? '') === 'medium')>Medium</option>
                <option value="low" @selected(($filters['severity'] ?? '') === 'low')>Low</option>
            </select>

            <select name="state" class="form-control">
                <option value="open" @selected($state === 'open')>Open Alerts</option>
                <option value="resolved" @selected($state === 'resolved')>Resolved Alerts</option>
                <option value="all" @selected($state === 'all')>All Alerts</option>
            </select>

            <button type="submit" class="button primary">Apply Filters</button>

            <a href="{{ route('alerts.index') }}" class="button secondary">
                Reset
            </a>
        </form>
    </div>

    <!-- Alert Records -->
    <div class="filter-card" style="margin-top: 18px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 15px;">
            <div>
                <h2 style="font-size: 17px; margin: 0 0 5px;">Alert Records</h2>
                <p style="font-size: 12px; color: #64748b; margin: 0;">
                    Review operational alerts and take the necessary action.
                </p>
            </div>

            <span class="stat-sub">
                {{ $alerts->total() }} alert(s) found
            </span>
        </div>

        @if(session('status'))
            <div class="filter-card" style="background: #ecfdf5; color: #047857; margin-bottom: 14px;">
                {{ session('status') }}
            </div>
        @endif

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; min-width: 850px;">
                <thead>
                    <tr style="text-align: left; border-bottom: 1px solid #e2e8f0;">
                        <th style="padding: 12px;">Alert</th>
                        <th style="padding: 12px;">Type</th>
                        <th style="padding: 12px;">Severity</th>
                        <th style="padding: 12px;">Status</th>
                        <th style="padding: 12px;">Last Detected</th>
                        <th style="padding: 12px;">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($alerts as $alert)
                        <tr style="border-bottom: 1px solid #e2e8f0;">
                            <td style="padding: 13px 12px;">
                                <strong>{{ $alert->title }}</strong>

                                @if($alert->description)
                                    <div style="font-size: 12px; color: #64748b; margin-top: 5px; max-width: 350px;">
                                        {{ $alert->description }}
                                    </div>
                                @endif
                            </td>

                            <td style="padding: 13px 12px;">
                                {{ ucwords(str_replace('_', ' ', $alert->type)) }}
                            </td>

                            <td style="padding: 13px 12px;">
                                @php
                                    $severityColors = [
                                        'critical' => '#dc2626',
                                        'high' => '#ea580c',
                                        'medium' => '#d97706',
                                        'low' => '#2563eb',
                                    ];

                                    $severityColor = $severityColors[$alert->severity] ?? '#64748b';
                                @endphp

                                <span style="display: inline-block; padding: 4px 9px; border-radius: 20px; background: {{ $severityColor }}18; color: {{ $severityColor }}; font-size: 11px; font-weight: 700;">
                                    {{ ucfirst($alert->severity) }}
                                </span>
                            </td>

                            <td style="padding: 13px 12px;">
                                @if($alert->resolved_at)
                                    <span style="color: #047857; font-weight: 600;">Resolved</span>
                                @elseif($alert->condition_active)
                                    <span style="color: #dc2626; font-weight: 600;">Open</span>
                                @else
                                    <span style="color: #64748b; font-weight: 600;">Inactive</span>
                                @endif
                            </td>

                            <td style="padding: 13px 12px; white-space: nowrap;">
                                {{ $alert->last_seen_at?->format('d M Y, H:i') ?? 'Not recorded' }}
                            </td>

                            <td style="padding: 13px 12px; white-space: nowrap;">
                                @if($alert->action_url)
                                    <a href="{{ $alert->action_url }}" class="button secondary">
                                        View
                                    </a>
                                @endif

                                @if(
                                    !$alert->resolved_at
                                    && $alert->condition_active
                                    && (
                                        auth()->user()->isAdministrator()
                                        || auth()->user()->isWarehouseManager()
                                    )
                                )
                                    <form method="POST"
                                          action="{{ route('alerts.resolve', $alert) }}"
                                          style="display: inline;">
                                        @csrf
                                        @method('PATCH')

                                        <button type="submit"
                                                class="button primary"
                                                onclick="return confirm('Are you sure you want to resolve this alert?')">
                                            Resolve
                                        </button>
                                    </form>
                                @endif

                                @if(!$alert->action_url && (
                                    $alert->resolved_at
                                    || !$alert->condition_active
                                    || !auth()->user()->isAdministrator() && !auth()->user()->isWarehouseManager()
                                ))
                                    <span style="color: #94a3b8;">No action</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding: 35px 15px; text-align: center;">
                                <strong>No alerts found.</strong>
                                <p style="color: #64748b; font-size: 12px; margin-top: 6px;">
                                    There are no alerts matching your selected filters.
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top: 18px;">
            {{ $alerts->links() }}
        </div>
    </div>
@endsection
