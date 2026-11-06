@extends('catalog.layout')

@section('title', 'Batch ' . $batch->batch_number . ' — Mukwano WIMS')

@section('breadcrumbs')
    <span class="breadcrumbs-separator">/</span>
    <a href="{{ route('batches.index') }}">Batches</a>
    <span class="breadcrumbs-separator">/</span>
    <span style="color: #0f172a; font-weight: 600;">{{ $batch->batch_number }}</span>
@endsection

@section('content')
    <!-- Inline Breadcrumbs Navigation -->
    <nav style="display: flex; align-items: center; gap: 8px; font-size: 12.5px; color: #64748b; margin-bottom: 14px;" aria-label="Breadcrumb">
        <a href="{{ route('dashboard') }}" style="color: #64748b; text-decoration: none; display: flex; align-items: center; gap: 4px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
            <span>Dashboard</span>
        </a>
        <span style="color: #cbd5e1;">/</span>
        <a href="{{ route('batches.index') }}" style="color: #64748b; text-decoration: none;">Batches</a>
        <span style="color: #cbd5e1;">/</span>
        <span style="color: #0f172a; font-weight: 600;">{{ $batch->batch_number }}</span>
    </nav>

    <!-- Unified Page Header Card -->
    <div class="page-header-card">
        <div class="page-title-wrap">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                <span class="pill-code" style="font-size: 12px; font-weight: 700;">{{ $batch->batch_number }}</span>
                @php
                    $status = $batch->expiry_status;
                    $badgeClass = match($status) {
                        'Expired' => 'badge-danger',
                        '30 days' => 'badge-warning',
                        '60 days' => 'badge-warning',
                        '90 days' => 'badge-info',
                        default => 'badge-success',
                    };
                @endphp
                <span class="{{ $badgeClass }}">{{ $status }}</span>
            </div>
            <h1>{{ $batch->product->name }}</h1>
            <p>SKU: {{ $batch->product->sku }} · {{ optional($batch->product->category)->name ?? 'General' }}</p>
        </div>
        <div class="page-actions-wrap">
            <a class="button secondary" href="{{ route('products.show', $batch->product_id) }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                <span>Product View</span>
            </a>
            <a class="button secondary" href="{{ route('batches.index') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                <span>All Batches</span>
            </a>
        </div>
    </div>

    <!-- 4 KPI Summary Cards -->
    <div class="stat-grid-4">
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(16, 185, 129, 0.12); color: #10b981;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Total On-Hand</span>
                <span class="stat-num">{{ rtrim(rtrim(number_format((float) $totalOnHand, 3), '0'), '.') }}</span>
                <span class="stat-sub">{{ $batch->product->unit ?? 'units' }} across {{ $balances->count() }} bin(s)</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(37, 99, 235, 0.12); color: #2563eb;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Manufacture Date</span>
                <span class="stat-num" style="font-size: 18px;">{{ $batch->manufactured_at ? $batch->manufactured_at->format('d M Y') : '—' }}</span>
                <span class="stat-sub">{{ $batch->manufactured_at ? $batch->manufactured_at->diffForHumans() : 'Date unrecorded' }}</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(245, 158, 11, 0.12); color: #f59e0b;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Expiry Date</span>
                <span class="stat-num" style="font-size: 18px;">{{ $batch->expires_at ? $batch->expires_at->format('d M Y') : 'Indefinite' }}</span>
                <span class="stat-sub">{{ $batch->expires_at ? $batch->expires_at->diffForHumans() : 'No expiration date' }}</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(124, 58, 237, 0.12); color: #7c3aed;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Category</span>
                <span class="stat-num" style="font-size: 18px;">{{ optional($batch->product->category)->name ?? 'General' }}</span>
                <span class="stat-sub">SKU: {{ $batch->product->sku }}</span>
            </div>
        </div>
    </div>

    <!-- Active Bin Distribution Table -->
    <div class="modern-table-card">
        <div style="padding: 14px 18px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between;">
            <h2 style="margin: 0; font-size: 15px; color: #0f172a;">Storage Bin Allocation</h2>
            <span style="font-size: 12px; color: #64748b;">{{ $balances->count() }} active location(s)</span>
        </div>
        <table class="clean-table">
            <thead>
                <tr>
                    <th style="width: 25%;">Location Code</th>
                    <th style="width: 45%;">Zone & Hierarchy</th>
                    <th style="width: 15%; text-align: right;">Quantity</th>
                    <th style="width: 15%; text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($balances as $balance)
                    <tr>
                        <td>
                            <a href="{{ route('locations.show', $balance->location) }}" style="font-family: monospace; font-weight: 700; color: #2563eb; text-decoration: none;">
                                {{ $balance->location->code ?? 'N/A' }}
                            </a>
                        </td>
                        <td>
                            <span style="font-weight: 600; color: #0f172a;">{{ $balance->location->name ?? 'Unnamed' }}</span>
                            <small style="display: block; color: #64748b; font-size: 11px;">
                                {{ optional($balance->location->parent)->name }} / {{ optional(optional($balance->location->parent)->parent)->name }}
                            </small>
                        </td>
                        <td style="text-align: right; font-weight: 700; color: #16a34a; font-size: 13.5px;">
                            {{ rtrim(rtrim(number_format((float) $balance->quantity, 3), '0'), '.') }}
                            <span style="font-size: 11px; font-weight: normal; color: #64748b;">{{ $batch->product->unit ?? 'units' }}</span>
                        </td>
                        <td style="text-align: right;">
                            <a href="{{ route('put-away.show', $balance) }}" class="button secondary" style="padding: 4px 10px; font-size: 12px;">Relocate</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 32px 20px; color: #64748b;">
                            No active storage balances found for this batch.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Movements Ledger History -->
    <div class="modern-table-card" style="margin-top: 16px;">
        <div style="padding: 14px 18px; border-bottom: 1px solid #f1f5f9;">
            <h2 style="margin: 0; font-size: 15px; color: #0f172a;">Stock Movement Ledger</h2>
        </div>
        <table class="clean-table">
            <thead>
                <tr>
                    <th>Date & Time</th>
                    <th>Type</th>
                    <th>From &rarr; To</th>
                    <th style="text-align: right;">Quantity</th>
                    <th>Operator</th>
                </tr>
            </thead>
            <tbody>
                @forelse($movements ?? [] as $movement)
                    <tr>
                        <td><span style="color: #64748b; font-size: 12px;">{{ $movement->moved_at->format('d M Y, H:i') }}</span></td>
                        <td><span class="pill-code">{{ ucfirst($movement->type) }}</span></td>
                        <td>
                            <span style="font-size: 12.5px; color: #334155;">
                                {{ $movement->fromLocation->code ?? 'External' }} &rarr; {{ $movement->toLocation->code ?? 'Outbound' }}
                            </span>
                        </td>
                        <td style="text-align: right; font-weight: 700;">
                            {{ rtrim(rtrim(number_format((float) $movement->quantity, 3), '0'), '.') }}
                        </td>
                        <td><span style="color: #64748b; font-size: 12px;">{{ $movement->performer->name ?? 'System' }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 24px; color: #64748b;">
                            No stock movement transactions recorded for this batch yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
