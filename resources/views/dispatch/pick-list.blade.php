@extends('catalog.layout')

@section('title', 'FEFO pick list')

@section('breadcrumbs')
    <a href="{{ route('dispatch-requests.index') }}">Dispatch Requests</a>
    <span class="sep">/</span>
    <span class="current">Pick List DR-{{ $dispatchRequest->id }}</span>
@endsection

@section('content')
    <!-- Unified Page Header -->
    <div class="page-header-card">
        <div class="page-title-wrap">
            <nav class="breadcrumb-trail" aria-label="Breadcrumb">
                <a href="{{ route('dashboard') }}">Dashboard</a>
                <span class="separator">/</span>
                <a href="{{ route('dispatch-requests.index') }}">Dispatch Requests</a>
                <span class="separator">/</span>
                <span class="current">DR-{{ $dispatchRequest->id }}</span>
            </nav>
            <h1>FEFO pick list · DR-{{ $dispatchRequest->id }}</h1>
            <p>{{ $dispatchRequest->product->sku }} — {{ $dispatchRequest->product->name }} · Requested {{ $dispatchRequest->requested_quantity }} {{ $dispatchRequest->product->unit }}</p>
        </div>
        <div class="page-actions-wrap">
            <a class="button secondary" href="{{ route('dispatch-requests.index') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                <span>Dispatch Requests</span>
            </a>
        </div>
    </div>

    <!-- 4 Summary KPI Cards -->
    <div class="stat-grid-4">
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(37, 99, 235, 0.12); color: #2563eb;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="9" y1="3" x2="9" y2="21"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Product Details</span>
                <span class="stat-num" style="font-size: 16px;">{{ $dispatchRequest->product->sku }}</span>
                <span class="stat-sub">{{ Str::limit($dispatchRequest->product->name, 25) }}</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(124, 58, 237, 0.12); color: #7c3aed;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 14 14"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Requested Demand</span>
                <span class="stat-num">{{ $dispatchRequest->requested_quantity }}</span>
                <span class="stat-sub">{{ $dispatchRequest->product->unit }}</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: {{ $proposal['sufficient'] ? 'rgba(16, 185, 129, 0.12)' : 'rgba(239, 68, 68, 0.12)' }}; color: {{ $proposal['sufficient'] ? '#10b981' : '#ef4444' }};">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Allocated Stock</span>
                <span class="stat-num">{{ $proposal['allocated_quantity'] }}</span>
                <span class="stat-sub">{{ $dispatchRequest->product->unit }} proposed</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: {{ $proposal['sufficient'] ? 'rgba(16, 185, 129, 0.12)' : 'rgba(239, 68, 68, 0.12)' }}; color: {{ $proposal['sufficient'] ? '#10b981' : '#ef4444' }};">
                @if ($proposal['sufficient'])
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                @else
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                @endif
            </div>
            <div class="stat-body">
                <span class="stat-title">Stock Coverage</span>
                <span class="stat-num" style="font-size: 16px;">{{ $proposal['sufficient'] ? '100% Sufficient' : 'Deficit' }}</span>
                <span class="stat-sub">{{ $proposal['sufficient'] ? 'Ready for confirmation' : 'Insufficient stock' }}</span>
            </div>
        </div>
    </div>

    @unless ($proposal['sufficient'])
        <div class="notice errors" role="alert" style="margin-bottom: 20px; background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 14px 18px; border-radius: 8px;">
            Eligible stock is short by {{ number_format((float) $proposal['requested_quantity'] - (float) $proposal['allocated_quantity'], 3, '.', '') }} {{ $dispatchRequest->product->unit }}. This request cannot be posted until sufficient unexpired stock is available.
        </div>
    @endunless

    <!-- FEFO Proposed Allocation Table -->
    <div class="modern-table-card" style="margin-bottom: 24px;">
        <div style="padding: 14px 18px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between;">
            <h2 style="margin: 0; font-size: 15px; color: #0f172a;">FEFO Allocation Order (First-Expiry-First-Out)</h2>
            <span style="font-size: 12px; color: #64748b;">{{ count($proposal['allocations']) }} proposed batch allocation(s)</span>
        </div>
        <table class="clean-table">
            <thead>
                <tr>
                    <th style="width: 10%;">FEFO Priority</th>
                    <th style="width: 25%;">Batch Number</th>
                    <th style="width: 18%;">Expiry Date</th>
                    <th style="width: 15%;">Storage Bin</th>
                    <th style="width: 16%; text-align: right;">Available in Bin</th>
                    <th style="width: 16%; text-align: right;">Proposed Pick</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($proposal['allocations'] as $index => $allocation)
                <tr>
                    <td>
                        <span class="pill-code" style="font-weight: 700; background: #e0f2fe; color: #0369a1;">#{{ $index + 1 }}</span>
                    </td>
                    <td>
                        <span style="font-family: monospace; font-weight: 700; color: #2563eb;">{{ $allocation['batch_number'] }}</span>
                    </td>
                    <td>
                        @if ($allocation['expires_at'])
                            <span style="color: #334155; font-size: 12.5px;">{{ $allocation['expires_at'] }}</span>
                        @else
                            <span style="color: #94a3b8;">No expiry date</span>
                        @endif
                    </td>
                    <td>
                        @if ($allocation['location_code'])
                            <span class="pill-code">{{ $allocation['location_code'] }}</span>
                        @else
                            <span style="color: #94a3b8;">—</span>
                        @endif
                    </td>
                    <td style="text-align: right; color: #64748b; font-size: 13px;">
                        {{ $allocation['available_quantity'] }} <span style="font-size: 11px;">{{ $dispatchRequest->product->unit }}</span>
                    </td>
                    <td style="text-align: right; font-weight: 700; color: #16a34a; font-size: 13.5px;">
                        {{ $allocation['proposed_quantity'] }} <span style="font-size: 11px; font-weight: normal; color: #64748b;">{{ $dispatchRequest->product->unit }}</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 32px 20px; color: #64748b;">
                        No eligible stock is available for this product.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if ($canDispatch && $proposal['allocations'] !== [])
        <!-- Post Picking Form -->
        <div class="card" style="background: #ffffff; border-radius: 10px; border: 1px solid #e2e8f0; padding: 24px;">
            <form method="POST" action="{{ route('dispatch-requests.post', $dispatchRequest) }}"
                data-offline-operation="Dispatch posting"
                data-offline-sync-url="{{ route('offline.sync.dispatch', $dispatchRequest) }}">
                @csrf
                <input type="hidden" name="client_id" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                
                <div style="margin-bottom: 16px;">
                    <h2 style="margin: 0 0 6px 0; font-size: 16px; color: #0f172a;">Confirm picked quantities</h2>
                    <p style="margin: 0; font-size: 12.5px; color: #64748b;">Enter actual physical quantities picked per batch and bin. Any change from the FEFO allocation requires an authorized reason.</p>
                </div>

                <div class="modern-table-card" style="margin-bottom: 20px; border: 1px solid #e2e8f0;">
                    <table class="clean-table">
                        <thead>
                            <tr>
                                <th style="width: 45%;">Batch & Expiry</th>
                                <th style="width: 25%;">Bin Location</th>
                                <th style="width: 30%; text-align: right;">Actual Picked Quantity</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach ($proposal['allocations'] as $allocation)
                            <tr>
                                <td>
                                    <span style="font-family: monospace; font-weight: 700; color: #2563eb;">{{ $allocation['batch_number'] }}</span>
                                    <span style="display: block; font-size: 11.5px; color: #64748b;">Expires: {{ $allocation['expires_at'] ?? '—' }}</span>
                                </td>
                                <td>
                                    <span class="pill-code">{{ $allocation['location_code'] ?? '—' }}</span>
                                </td>
                                <td style="text-align: right;">
                                    <input name="picks[{{ $allocation['balance_id'] }}]" type="number" min="0" max="{{ $allocation['available_quantity'] }}" step="0.001"
                                           value="{{ old('picks.'.$allocation['balance_id'], $allocation['proposed_quantity']) }}" required
                                           aria-label="Picked quantity for {{ $allocation['batch_number'] }}"
                                           style="width: 140px; text-align: right; font-weight: 700; border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 10px; font-size: 13.5px;">
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="field" style="margin-bottom: 20px;">
                    <label for="deviation_reason" style="display: block; font-size: 12.5px; font-weight: 600; color: #334155; margin-bottom: 6px;">Authorized reason for FEFO deviation (if applicable)</label>
                    <textarea id="deviation_reason" name="deviation_reason" maxlength="2000" rows="3"
                              placeholder="Required only if actual picks deviate from the automated FEFO order..."
                              style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; font-size: 13px;">{{ old('deviation_reason') }}</textarea>
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end; padding-top: 16px; border-top: 1px solid #f1f5f9;">
                    <a href="{{ route('dispatch-requests.index') }}" class="button secondary" style="padding: 8px 16px;">Cancel</a>
                    <button type="submit" class="button" @disabled(! $proposal['sufficient']) style="background: #2563eb; color: #ffffff; padding: 8px 20px; font-weight: 600;">
                        Post dispatch and generate GDN
                    </button>
                </div>
            </form>
        </div>
    @elseif (! $canDispatch)
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px 18px; color: #64748b; font-size: 13px;">
            Only an authorized dispatch officer can confirm picks and post this dispatch.
        </div>
    @endif
@endsection
