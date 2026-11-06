@extends('catalog.layout')

@section('title', 'Dispatch requests')

@section('breadcrumbs')
    <span class="current">Dispatch Requests</span>
@endsection

@section('content')
    <!-- Unified Page Header -->
    <div class="page-header-card">
        <div class="page-title-wrap">
            <nav class="breadcrumb-trail" aria-label="Breadcrumb">
                <a href="{{ route('dashboard') }}">Dashboard</a>
                <span class="separator">/</span>
                <span class="current">Dispatch Requests</span>
            </nav>
            <h1>Dispatch Requests</h1>
            <p>Review outbound sales and transfer demands, run automated FEFO picking, and issue Goods Dispatch Notes (GDN).</p>
        </div>
        <div class="page-actions-wrap">
            <a class="button" href="{{ route('dispatch-requests.create') }}" style="background: #2563eb;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                <span>New Dispatch Request</span>
            </a>
        </div>
    </div>

    <!-- 4 Top KPI Cards -->
    <div class="stat-grid-4">
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(37, 99, 235, 0.12); color: #2563eb;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Total Requests</span>
                <span class="stat-num">{{ number_format($metrics['total'] ?? $dispatchRequests->total()) }}</span>
                <span class="stat-sub">Lifetime dispatch logs</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(245, 158, 11, 0.12); color: #f59e0b;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Pending Picking</span>
                <span class="stat-num">{{ number_format($metrics['pending'] ?? 0) }}</span>
                <span class="stat-sub">Awaiting pick validation</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(16, 185, 129, 0.12); color: #10b981;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Dispatched (GDN)</span>
                <span class="stat-num">{{ number_format($metrics['dispatched'] ?? 0) }}</span>
                <span class="stat-sub">Confirmed out the door</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(124, 58, 237, 0.12); color: #7c3aed;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Today's Orders</span>
                <span class="stat-num">{{ number_format($metrics['today'] ?? 0) }}</span>
                <span class="stat-sub">New outbound entries</span>
            </div>
        </div>
    </div>

    <!-- Modern Table Card -->
    <div class="modern-table-card">
        <table class="clean-table">
            <thead>
                <tr>
                    <th style="width: 10%;">Request ID</th>
                    <th style="width: 14%;">Requested At</th>
                    <th style="width: 25%;">Product / SKU</th>
                    <th style="width: 13%; text-align: right;">Quantity</th>
                    <th style="width: 10%;">Status</th>
                    <th style="width: 12%;">Requested By</th>
                    <th style="width: 10%;">GDN Ref</th>
                    <th style="width: 6%; text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($dispatchRequests as $dispatchRequest)
                <tr>
                    <td>
                        <span class="pill-code" style="font-weight: 700;">DR-{{ $dispatchRequest->id }}</span>
                    </td>
                    <td>
                        <span style="color: #64748b; font-size: 12.5px;">{{ $dispatchRequest->requested_at->format('d M Y, H:i') }}</span>
                    </td>
                    <td>
                        <div style="display: flex; flex-direction: column;">
                            <a href="{{ route('products.show', $dispatchRequest->product) }}" style="font-weight: 700; color: #0f172a; text-decoration: none;">
                                {{ $dispatchRequest->product->name }}
                            </a>
                            <span class="pill-code" style="width: fit-content; margin-top: 2px;">{{ $dispatchRequest->product->sku }}</span>
                        </div>
                    </td>
                    <td style="text-align: right; font-weight: 700; font-size: 13.5px; color: #0f172a;">
                        {{ number_format($dispatchRequest->requested_quantity) }} <span style="font-size: 11px; font-weight: normal; color: #64748b;">{{ $dispatchRequest->product->unit }}</span>
                    </td>
                    <td>
                        @if ($dispatchRequest->status === 'dispatched')
                            <span class="badge-success">Dispatched</span>
                        @elseif ($dispatchRequest->status === 'cancelled')
                            <span class="badge-danger">Cancelled</span>
                        @else
                            <span class="badge-warning">Pending</span>
                        @endif
                    </td>
                    <td>
                        <span style="color: #334155; font-size: 12.5px;">{{ $dispatchRequest->requester->name }}</span>
                    </td>
                    <td>
                        @if ($dispatchRequest->gdn_number)
                            <span style="font-family: monospace; font-weight: 700; color: #2563eb; font-size: 12px;">{{ $dispatchRequest->gdn_number }}</span>
                        @else
                            <span style="color: #94a3b8;">—</span>
                        @endif
                    </td>
                    <td style="text-align: right;">
                        @if ($dispatchRequest->status === 'dispatched')
                            <a href="{{ route('dispatch-requests.print', $dispatchRequest) }}" class="button secondary" style="padding: 4px 10px; font-size: 12px;" target="_blank">View GDN</a>
                        @else
                            <a href="{{ route('dispatch-requests.pick-list', $dispatchRequest) }}" class="button" style="padding: 4px 10px; font-size: 12px; background: #2563eb;">Pick List</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center; padding: 36px 20px; color: #64748b;">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" style="margin-bottom: 8px;"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                        <p style="margin: 0; font-weight: 600; color: #334155;">No dispatch requests recorded</p>
                        <small style="color: #94a3b8;">Create a dispatch request to allocate items via FEFO logic.</small>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div style="margin-top: 14px;">
        {{ $dispatchRequests->links() }}
    </div>
@endsection
