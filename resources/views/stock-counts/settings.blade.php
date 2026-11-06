@extends('catalog.layout')

@section('title', 'Cycle count settings')

@section('breadcrumbs')
    <a href="{{ route('stock-counts.index') }}">Cycle Counts</a>
    <span class="sep">/</span>
    <span class="current">Count Settings</span>
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
                <span class="current">Count Settings</span>
            </nav>
            <h1>Cycle count settings</h1>
            <p>Configure discrepancy escalation and Inventory Record Accuracy tolerance thresholds.</p>
        </div>
        <div class="page-actions-wrap">
            <a class="button secondary" href="{{ route('stock-counts.index') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                <span>Cycle Counts</span>
            </a>
        </div>
    </div>

    <!-- Form Card -->
    <div class="card" style="max-width: 680px; margin: 0 auto; background: #ffffff; border-radius: 10px; border: 1px solid #e2e8f0; padding: 24px;">
        <form method="POST" action="{{ route('stock-counts.settings.update') }}">
            @csrf
            @method('PUT')
            
            <div class="field" style="margin-bottom: 22px;">
                <label for="variance_threshold_percent" style="display: block; font-size: 12.5px; font-weight: 600; color: #334155; margin-bottom: 6px;">
                    Variance escalation threshold (%) <span style="color: #ef4444;">*</span>
                </label>
                <input id="variance_threshold_percent" name="variance_threshold_percent" type="number" min="0" max="1000" step="0.001"
                       value="{{ old('variance_threshold_percent', $inventorySetting->variance_threshold_percent) }}" required
                       style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; font-size: 13.5px;">
                <small style="display: block; color: #64748b; font-size: 11.5px; margin-top: 5px;">
                    A line is flagged for escalation when absolute variance exceeds this percentage of its recorded quantity. All submitted counts still require supervisor approval.
                </small>
                @error('variance_threshold_percent') <small class="errors" style="color: #ef4444; font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</small> @enderror
            </div>

            <div class="field" style="margin-bottom: 24px;">
                <label for="accuracy_tolerance_percent" style="display: block; font-size: 12.5px; font-weight: 600; color: #334155; margin-bottom: 6px;">
                    Accuracy matching tolerance (%) <span style="color: #ef4444;">*</span>
                </label>
                <input id="accuracy_tolerance_percent" name="accuracy_tolerance_percent" type="number" min="0" max="100" step="0.001"
                       value="{{ old('accuracy_tolerance_percent', $inventorySetting->accuracy_tolerance_percent) }}" required
                       style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; font-size: 13.5px;">
                <small style="display: block; color: #64748b; font-size: 11.5px; margin-top: 5px;">
                    Accuracy = matching records / counted records × 100. A record matches when absolute variance is within this percentage of its recorded quantity.
                </small>
                @error('accuracy_tolerance_percent') <small class="errors" style="color: #ef4444; font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</small> @enderror
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end; padding-top: 18px; border-top: 1px solid #f1f5f9;">
                <a class="button secondary" href="{{ route('stock-counts.index') }}" style="padding: 8px 16px;">Cancel</a>
                <button type="submit" class="button" style="background: #2563eb; color: #ffffff; padding: 8px 20px; font-weight: 600;">
                    Save settings
                </button>
            </div>
        </form>
    </div>
@endsection
