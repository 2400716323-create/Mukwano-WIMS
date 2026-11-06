@extends('catalog.layout')

@section('title', 'Create count list')

@section('breadcrumbs')
    <a href="{{ route('stock-counts.index') }}">Cycle Counts</a>
    <span class="sep">/</span>
    <span class="current">New Count List</span>
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
                <span class="current">New Count List</span>
            </nav>
            <h1>Create count list</h1>
            <p>Choose a bin and optionally narrow the audit to one product or batch for physical counting.</p>
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
        <form method="POST" action="{{ route('stock-counts.store') }}">
            @csrf
            <div class="form-grid" style="display: grid; grid-template-columns: 1fr; gap: 16px;">
                <div class="field">
                    <label for="location_id" style="display: block; font-size: 12.5px; font-weight: 600; color: #334155; margin-bottom: 6px;">Bin Location <span style="color: #ef4444;">*</span></label>
                    <select id="location_id" name="location_id" required style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; font-size: 13.5px;">
                        <option value="">Choose bin</option>
                        @foreach ($locations as $location)
                            <option value="{{ $location->id }}" @selected(old('location_id') == $location->id)>{{ $location->code }} — {{ $location->name }}</option>
                        @endforeach
                    </select>
                    @error('location_id') <small class="errors" style="color: #ef4444; font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</small> @enderror
                </div>
                
                <div class="field">
                    <label for="product_id" style="display: block; font-size: 12.5px; font-weight: 600; color: #334155; margin-bottom: 6px;">Product (optional)</label>
                    <select id="product_id" name="product_id" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; font-size: 13.5px;">
                        <option value="">All products in bin</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" @selected(old('product_id') == $product->id)>{{ $product->sku }} — {{ $product->name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="field">
                    <label for="batch_id" style="display: block; font-size: 12.5px; font-weight: 600; color: #334155; margin-bottom: 6px;">Batch (optional)</label>
                    <select id="batch_id" name="batch_id" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; font-size: 13.5px;">
                        <option value="">All batches for selected product</option>
                        @foreach ($batches as $batch)
                            <option value="{{ $batch->id }}" @selected(old('batch_id') == $batch->id)>{{ $batch->product->sku }} — {{ $batch->batch_number }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="field" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px; margin-top: 6px;">
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: #1e293b; cursor: pointer; margin: 0;">
                        <input type="checkbox" name="blind_count" value="1" @checked(old('blind_count')) style="width: 16px; height: 16px; cursor: pointer;">
                        <span>Blind count (hide expected system quantities from warehouse floor counters)</span>
                    </label>
                </div>
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 24px; padding-top: 18px; border-top: 1px solid #f1f5f9;">
                <a class="button secondary" href="{{ route('stock-counts.index') }}" style="padding: 8px 16px;">Cancel</a>
                <button type="submit" class="button" style="background: #2563eb; color: #ffffff; padding: 8px 20px; font-weight: 600;">
                    Create count list
                </button>
            </div>
        </form>
    </div>
@endsection
