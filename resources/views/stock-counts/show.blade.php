
@extends('catalog.layout')

@section('title', 'Stock count SC-'.$stockCount->id)

@section('breadcrumbs')
    <a href="{{ route('stock-counts.index') }}">Cycle Counts</a>
    <span class="sep">/</span>
    <span class="current">SC-{{ $stockCount->id }}</span>
@endsection

@section('content')
    <!-- Unified Page Header -->
    <div class="page-header-card">
        <div class="page-title-wrap">
            <nav class="breadcrumb-trail" aria-label="Breadcrumb">
                <a href="{{ route('dashboard') }}">Dashboard</a>
                <span class="separator">/</span>
                <a href="{{ route('stock-counts.index') }}">Cycle Counts</a>
                <span class="separator">/</span>
                <span class="current">SC-{{ $stockCount->id }}</span>
            </nav>
            <h1>Stock count SC-{{ $stockCount->id }}</h1>
            <p>{{ $stockCount->location->code }} — {{ $stockCount->location->name }} · {{ $stockCount->product?->name ?? 'All products' }}{{ $stockCount->batch ? ' · '.$stockCount->batch->batch_number : '' }}</p>
        </div>
        <div class="page-actions-wrap">
            @if (auth()->user()->isSupervisor())
                <a class="button secondary" href="{{ route('stock-counts.settings') }}">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    <span>Count settings</span>
                </a>
            @endif
            <a class="button secondary" href="{{ route('stock-counts.index') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                <span>All counts</span>
            </a>
        </div>
    </div>

    <!-- Metadata Grid Card -->
    <div class="card" style="background: #ffffff; border-radius: 10px; border: 1px solid #e2e8f0; padding: 20px; margin-bottom: 20px;">
        <div class="detail-grid">
            <div>
                <span style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: #64748b; letter-spacing: 0.04em;">Status</span>
                <div style="margin-top: 4px;">
                    @if ($stockCount->status === 'approved')
                        <span class="badge-success">Approved</span>
                    @elseif ($stockCount->status === 'rejected')
                        <span class="badge-danger">Rejected</span>
                    @elseif ($stockCount->status === 'submitted')
                        <span class="badge-warning">Submitted / Pending Review</span>
                    @else
                        <span class="badge-info">Draft In Progress</span>
                    @endif
                </div>
            </div>
            <div>
                <span style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: #64748b; letter-spacing: 0.04em;">Count type</span>
                <div style="font-weight: 600; color: #0f172a; margin-top: 4px;">{{ $stockCount->blind_count ? 'Blind count (Hidden targets)' : 'System quantities visible' }}</div>
            </div>
            <div>
                <span style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: #64748b; letter-spacing: 0.04em;">Created by</span>
                <div style="font-weight: 600; color: #0f172a; margin-top: 4px;">{{ $stockCount->creator->name }}</div>
            </div>
            <div>
                <span style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: #64748b; letter-spacing: 0.04em;">Submitted by</span>
                <div style="font-weight: 600; color: #0f172a; margin-top: 4px;">{{ $stockCount->submitter?->name ?? '—' }}</div>
            </div>
            <div>
                <span style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: #64748b; letter-spacing: 0.04em;">Reviewed by</span>
                <div style="font-weight: 600; color: #0f172a; margin-top: 4px;">{{ $stockCount->reviewer?->name ?? '—' }}</div>
            </div>
            <div>
                <span style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: #64748b; letter-spacing: 0.04em;">Review note</span>
                <div style="font-weight: 500; color: #475569; margin-top: 4px;">{{ $stockCount->review_note ?? '—' }}</div>
            </div>
        </div>
    </div>

    @if ($stockCount->status !== 'draft')
        <!-- Inventory Record Accuracy KPI Section -->
        <div class="card" style="background: #ffffff; border-radius: 10px; border: 1px solid #e2e8f0; padding: 20px; margin-bottom: 20px;">
            <div style="margin-bottom: 14px;">
                <h2 style="margin: 0 0 4px 0; font-size: 16px; color: #0f172a;">Inventory Record Accuracy</h2>
                <p style="margin: 0; font-size: 12.5px; color: #64748b;">Accuracy = matching records / counted records × 100. A record matches when its absolute variance is within the configured {{ $inventorySetting->accuracy_tolerance_percent }}% tolerance.</p>
            </div>
            <div class="stat-grid-4">
                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(37, 99, 235, 0.12); color: #2563eb;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                    </div>
                    <div class="stat-body">
                        <span class="stat-title">Counted records</span>
                        <span class="stat-num">{{ $accuracy['counted'] }}</span>
                        <span class="stat-sub">Audited batch items</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(16, 185, 129, 0.12); color: #10b981;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                    <div class="stat-body">
                        <span class="stat-title">Matching records</span>
                        <span class="stat-num">{{ $accuracy['matching'] }}</span>
                        <span class="stat-sub">Within tolerance</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(239, 68, 68, 0.12); color: #ef4444;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    </div>
                    <div class="stat-body">
                        <span class="stat-title">Discrepancies</span>
                        <span class="stat-num">{{ $accuracy['discrepancies'] }}</span>
                        <span class="stat-sub">Variance flagged</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(124, 58, 237, 0.12); color: #7c3aed;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M16 12l-4-4-4 4M12 16V8"/></svg>
                    </div>
                    <div class="stat-body">
                        <span class="stat-title">Inventory record accuracy</span>
                        <span class="stat-num">{{ number_format($accuracy['accuracy_percent'], 2) }}%</span>
                        <span class="stat-sub">Overall score</span>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Count Records Table & Submission Form -->
    <div class="modern-table-card" style="margin-bottom: 20px;">
        <form method="POST" action="{{ $canSubmit ? route('stock-counts.submit', $stockCount) : '#' }}"
            @if ($canSubmit)
                data-offline-operation="Count submission"
                data-offline-sync-url="{{ route('offline.sync.count', $stockCount) }}"
            @endif>
            @csrf
            <div style="padding: 16px 20px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <h2 style="margin: 0; font-size: 15px; color: #0f172a;">Count records</h2>
                    @if ($canSubmit)
                        <input type="hidden" name="client_id" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                        <p style="margin: 4px 0 0 0; font-size: 12.5px; color: #64748b;">Enter a physical quantity for every line. A discrepancy reason is required when the count differs from the recorded quantity.</p>
                    @endif
                </div>
            </div>
            <table class="clean-table">
                <thead>
                    <tr>
                        <th style="width: 20%;">Product</th>
                        <th style="width: 12%;">Batch</th>
                        <th style="width: 10%;">Bin</th>
                        <th style="width: 10%;">Expiry</th>
                        <th style="width: 10%; text-align: right;">System quantity</th>
                        <th style="width: 12%; text-align: right;">Physical quantity</th>
                        <th style="width: 8%; text-align: right;">Variance</th>
                        <th style="width: 12%;">Discrepancy reason</th>
                        <th style="width: 6%;">Threshold</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($stockCount->lines as $line)
                    @php
                        $hideSystem = $stockCount->blind_count && $stockCount->status === 'draft';
                    @endphp
                    <tr>
                        <td>
                            <span style="font-weight: 600; color: #0f172a;">{{ $line->batch->product->sku }}</span>
                            <small style="display: block; color: #64748b; font-size: 11.5px;">{{ $line->batch->product->name }}</small>
                        </td>
                        <td><span style="font-family: monospace; font-weight: 700; color: #2563eb;">{{ $line->batch->batch_number }}</span></td>
                        <td><span class="pill-code">{{ $line->location->code }}</span></td>
                        <td><span style="color: #64748b; font-size: 12px;">{{ $line->batch->expires_at?->toDateString() ?? '—' }}</span></td>
                        <td style="text-align: right; font-weight: 600; color: #334155;">{{ $hideSystem ? 'Hidden' : $line->system_quantity }}</td>
                        <td style="text-align: right;">
                            @if ($canSubmit)
                                <input name="lines[{{ $line->id }}][physical_quantity]" type="number" step="0.001" min="0"
                                       value="{{ old('lines.'.$line->id.'.physical_quantity', $line->physical_quantity) }}" required
                                       aria-label="Physical quantity for {{ $line->batch->batch_number }}"
                                       style="width: 100px; text-align: right; border: 1px solid #cbd5e1; border-radius: 6px; padding: 4px 8px; font-size: 13px;">
                            @else
                                <span style="font-weight: 700; color: #0f172a;">{{ $line->physical_quantity ?? 'Not counted' }}</span>
                            @endif
                        </td>
                        <td style="text-align: right; font-weight: 700; color: {{ (float) $line->variance < 0 ? '#ef4444' : ((float) $line->variance > 0 ? '#10b981' : '#64748b') }};">
                            {{ $line->variance ?? '—' }}
                        </td>
                        <td>
                            @if ($canSubmit)
                                <input name="lines[{{ $line->id }}][discrepancy_reason]"
                                       value="{{ old('lines.'.$line->id.'.discrepancy_reason', $line->discrepancy_reason) }}"
                                       maxlength="2000" aria-label="Discrepancy reason for {{ $line->batch->batch_number }}"
                                       placeholder="Reason if variance..."
                                       style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 4px 8px; font-size: 12px;">
                            @else
                                <span style="font-size: 12px; color: #475569;">{{ $line->discrepancy_reason ?? '—' }}</span>
                            @endif
                        </td>
                        <td>
                            @if ($line->exceeds_variance_threshold)
                                <span class="badge-danger">Escalated</span>
                            @elseif ($line->physical_quantity === null)
                                <span style="color: #94a3b8;">—</span>
                            @else
                                <span class="badge-success">Normal</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            @if ($canSubmit)
                <div style="padding: 16px 20px; display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid #f1f5f9;">

                    <button
                        type="submit"
                        class="button"
                        formaction="{{ route('stock-counts.save-draft', $stockCount) }}"
                        formnovalidate
                        data-skip-offline-queue="true"
                        style="background: #e2e8f0; color: #1e293b; padding: 8px 20px; font-weight: 600;">
                        Save Draft
                    </button>

                    <button
                        type="submit"
                        class="button"
                        style="background: #2563eb; color: #ffffff; padding: 8px 20px; font-weight: 600;">
                        Submit count for approval
                    </button>

                </div>
            @endif
        </form>
    </div>

    @if ($canReview)
        <!-- Supervisor Review Card -->
        <div class="card" style="background: #ffffff; border-radius: 10px; border: 1px solid #e2e8f0; padding: 24px; margin-bottom: 20px;">
            <div style="margin-bottom: 16px;">
                <h2 style="margin: 0 0 6px 0; font-size: 16px; color: #0f172a;">Supervisor review</h2>
                <p style="margin: 0; font-size: 12.5px; color: #64748b;">Variance threshold: {{ $inventorySetting->variance_threshold_percent }}%. Counts above the threshold are flagged for escalation; all adjustments still require approval.</p>
            </div>
            <form method="POST" action="{{ route('stock-counts.review', $stockCount) }}">
                @csrf
                <div class="field" style="margin-bottom: 16px;">
                    <label for="decision" style="display: block; font-size: 12.5px; font-weight: 600; color: #334155; margin-bottom: 6px;">Decision</label>
                    <select id="decision" name="decision" required style="width: 100%; max-width: 360px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; font-size: 13.5px;">
                        <option value="approved">Approve and post adjustments</option>
                        <option value="rejected">Reject without changing stock</option>
                    </select>
                </div>
                <div class="field" style="margin-bottom: 20px;">
                    <label for="reason" style="display: block; font-size: 12.5px; font-weight: 600; color: #334155; margin-bottom: 6px;">Review note (required to reject)</label>
                    <textarea id="reason" name="reason" maxlength="2000" rows="3"
                              placeholder="Notes regarding count verification..."
                              style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; font-size: 13px;">{{ old('reason') }}</textarea>
                </div>
                <button type="submit" class="button" style="background: #2563eb; color: #ffffff; padding: 8px 20px; font-weight: 600;">
                    Record supervisor decision
                </button>
            </form>
        </div>
    @endif

    <!-- Approval History Card -->
    <div class="modern-table-card">
        <div style="padding: 14px 18px; border-bottom: 1px solid #f1f5f9;">
            <h2 style="margin: 0; font-size: 15px; color: #0f172a;">Approval history</h2>
        </div>
        <table class="clean-table">
            <thead>
                <tr>
                    <th style="width: 25%;">Date & Time</th>
                    <th style="width: 25%;">Supervisor</th>
                    <th style="width: 20%;">Decision</th>
                    <th style="width: 30%;">Reason / Comments</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($stockCount->reviews as $review)
                <tr>
                    <td><span style="color: #64748b; font-size: 12.5px;">{{ $review->reviewed_at->format('Y-m-d H:i:s') }}</span></td>
                    <td><span style="font-weight: 600; color: #0f172a;">{{ $review->supervisor->name }}</span></td>
                    <td>
                        @if ($review->decision === 'approved')
                            <span class="badge-success">Approved</span>
                        @else
                            <span class="badge-danger">Rejected</span>
                        @endif
                    </td>
                    <td><span style="font-size: 12.5px; color: #475569;">{{ $review->reason ?? '—' }}</span></td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align: center; padding: 24px; color: #64748b;">
                        No supervisor decisions recorded.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
