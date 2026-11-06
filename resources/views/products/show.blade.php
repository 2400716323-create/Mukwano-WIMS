@extends('catalog.layout')

@section('title', $product->name)

@section('breadcrumbs')
    <span class="breadcrumbs-separator">/</span>
    <a href="{{ route('products.index') }}">Products</a>
    <span class="breadcrumbs-separator">/</span>
    <span style="color: #0f172a; font-weight: 600;">{{ $product->name }}</span>
@endsection

@section('content')
    <!-- Inline Breadcrumbs Navigation -->
    <nav style="display: flex; align-items: center; gap: 8px; font-size: 12.5px; color: #64748b; margin-bottom: 14px;" aria-label="Breadcrumb">
        <a href="{{ route('dashboard') }}" style="color: #64748b; text-decoration: none; display: flex; align-items: center; gap: 4px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
            <span>Dashboard</span>
        </a>
        <span style="color: #cbd5e1;">/</span>
        <a href="{{ route('products.index') }}" style="color: #64748b; text-decoration: none;">Products</a>
        <span style="color: #cbd5e1;">/</span>
        <span style="color: #0f172a; font-weight: 600;">{{ $product->name }}</span>
    </nav>

    <!-- Unified Page Header Card -->
    <div class="page-header-card">
        <div class="page-title-wrap">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                <span class="pill-code">{{ $product->sku }}</span>
                @if ($product->status === 'active')
                    <span class="badge-success">Active</span>
                @else
                    <span class="badge-muted">Inactive</span>
                @endif
            </div>
            <h1>{{ $product->name }}</h1>
            <p>{{ $product->category->name }} · SAP Material: {{ $product->sap_material_number ?: 'Not assigned' }}</p>
        </div>
        <div class="page-actions-wrap">
            <a class="button secondary" href="{{ route('batches.index', ['product_id' => $product->id]) }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                <span>Batches</span>
            </a>
            <a class="button secondary" href="{{ route('products.label', $product) }}" target="_blank">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                <span>Print QR</span>
            </a>
            <a class="button secondary" href="{{ route('products.edit', $product) }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                <span>Edit</span>
            </a>
            @if ($product->status === 'active')
                <form method="POST" action="{{ route('products.deactivate', $product) }}" style="margin: 0;" onsubmit="return confirm('Deactivate this product?')">
                    @csrf
                    @method('PATCH')
                    <button class="button danger" type="submit">Deactivate</button>
                </form>
            @endif
            <a class="button secondary" href="{{ route('products.index') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                <span>Back to Catalog</span>
            </a>
        </div>
    </div>

    <!-- Product Details Content -->
    <div style="display: grid; grid-template-columns: 280px 1fr; gap: 16px; align-items: start;">
        <!-- Left Image Card -->
        <div class="card" style="text-align: center; padding: 24px;">
            <img class="preview" src="{{ $imageUrl ?: asset('images/product-placeholder.svg') }}" alt="{{ $product->name }}" style="width: 100%; max-height: 220px; object-fit: contain; border-radius: 8px; background: #f8fafc; border: 1px solid #e2e8f0; padding: 12px; margin-bottom: 12px;">
            <h3 style="font-size: 14px; font-weight: 700; color: #0f172a; margin: 0 0 4px;">{{ $product->name }}</h3>
            <span class="pill-code" style="font-size: 11px;">{{ $product->sku }}</span>
        </div>

        <!-- Right Info Card -->
        <div class="card" style="padding: 24px;">
            <h2 style="margin-top: 0; margin-bottom: 16px; font-size: 16px; color: #0f172a; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px;">Specifications & Thresholds</h2>
            <div class="detail-grid">
                <div>
                    <span style="font-size: 11.5px; color: #64748b; font-weight: 600; text-transform: uppercase;">SKU Code</span>
                    <p style="margin: 3px 0 0; font-weight: 700; color: #0f172a; font-family: monospace;">{{ $product->sku }}</p>
                </div>
                <div>
                    <span style="font-size: 11.5px; color: #64748b; font-weight: 600; text-transform: uppercase;">SAP Material Number</span>
                    <p style="margin: 3px 0 0; font-weight: 600; color: #334155;">{{ $product->sap_material_number ?: '—' }}</p>
                </div>
                <div>
                    <span style="font-size: 11.5px; color: #64748b; font-weight: 600; text-transform: uppercase;">Category</span>
                    <p style="margin: 3px 0 0;"><a href="{{ route('categories.show', $product->category) }}" style="font-weight: 600; color: #2563eb; text-decoration: none;">{{ $product->category->name }}</a></p>
                </div>
                <div>
                    <span style="font-size: 11.5px; color: #64748b; font-weight: 600; text-transform: uppercase;">Unit of Measure</span>
                    <p style="margin: 3px 0 0; font-weight: 600; color: #334155;">{{ $product->unit }}</p>
                </div>
                <div>
                    <span style="font-size: 11.5px; color: #64748b; font-weight: 600; text-transform: uppercase;">Minimum Safety Stock</span>
                    <p style="margin: 3px 0 0; font-weight: 700; color: #dc2626;">{{ number_format($product->min_level) }} {{ $product->unit }}</p>
                </div>
                <div>
                    <span style="font-size: 11.5px; color: #64748b; font-weight: 600; text-transform: uppercase;">Reorder Trigger Level</span>
                    <p style="margin: 3px 0 0; font-weight: 700; color: #ea580c;">{{ number_format($product->reorder_level) }} {{ $product->unit }}</p>
                </div>
                <div>
                    <span style="font-size: 11.5px; color: #64748b; font-weight: 600; text-transform: uppercase;">Maximum Warehouse Capacity</span>
                    <p style="margin: 3px 0 0; font-weight: 700; color: #16a34a;">{{ number_format($product->max_level) }} {{ $product->unit }}</p>
                </div>
                <div>
                    <span style="font-size: 11.5px; color: #64748b; font-weight: 600; text-transform: uppercase;">Created On</span>
                    <p style="margin: 3px 0 0; color: #64748b; font-size: 12.5px;">{{ $product->created_at->format('d M Y, H:i') }}</p>
                </div>
                <div class="full" style="border-bottom: 0;">
                    <span style="font-size: 11.5px; color: #64748b; font-weight: 600; text-transform: uppercase;">Product Description</span>
                    <p style="margin: 4px 0 0; color: #475569; font-size: 13px; line-height: 1.5;">{{ $product->description ?: 'No detailed description provided.' }}</p>
                </div>
            </div>
        </div>
    </div>
@endsection
