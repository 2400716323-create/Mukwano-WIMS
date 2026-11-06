@extends('catalog.layout')

@section('title', 'New dispatch request')

@section('breadcrumbs')
    <a href="{{ route('dispatch-requests.index') }}">Dispatch Requests</a>
    <span class="sep">/</span>
    <span class="current">New Request</span>
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
                <span class="current">New Request</span>
            </nav>
            <h1>New dispatch request</h1>
            <p>Create a request for an active product. Stock is committed only when an authorized dispatch officer posts the picks.</p>
        </div>
        <div class="page-actions-wrap">
            <a class="button secondary" href="{{ route('dispatch-requests.index') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                <span>Back to Requests</span>
            </a>
        </div>
    </div>

    <!-- Form Card -->
    <div class="card" style="max-width: 640px; margin: 0 auto; background: #ffffff; border-radius: 10px; border: 1px solid #e2e8f0; padding: 24px;">
        <form method="POST" action="{{ route('dispatch-requests.store') }}">
            @csrf
            <div class="field" style="margin-bottom: 18px;">
                <label for="product_id" style="display: block; font-size: 12.5px; font-weight: 600; color: #334155; margin-bottom: 6px;">Product <span style="color: #ef4444;">*</span></label>
                <select id="product_id" name="product_id" required style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; font-size: 13.5px;">
                    <option value="">Choose a product</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}" @selected(old('product_id') == $product->id)>{{ $product->sku }} — {{ $product->name }}</option>
                    @endforeach
                </select>
                @error('product_id') <small class="errors" style="color: #ef4444; font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</small> @enderror
            </div>
            <div class="field" style="margin-bottom: 22px;">
                <label for="quantity" style="display: block; font-size: 12.5px; font-weight: 600; color: #334155; margin-bottom: 6px;">Requested quantity <span style="color: #ef4444;">*</span></label>
                <input id="quantity" name="quantity" type="number" step="0.001" min="0.001" value="{{ old('quantity') }}" required
                       placeholder="e.g. 50"
                       style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; font-size: 13.5px;">
                @error('quantity') <small class="errors" style="color: #ef4444; font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</small> @enderror
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end; padding-top: 18px; border-top: 1px solid #f1f5f9;">
                <a class="button secondary" href="{{ route('dispatch-requests.index') }}" style="padding: 8px 16px;">Cancel</a>
                <button type="submit" class="button" style="background: #2563eb; color: #ffffff; padding: 8px 20px; font-weight: 600;">
                    Create request and view FEFO pick list
                </button>
            </div>
        </form>
    </div>
@endsection
