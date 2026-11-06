@extends('catalog.layout')

@section('title', $category->name)

@section('breadcrumbs')
    <span class="breadcrumbs-separator">/</span>
    <a href="{{ route('categories.index') }}">Categories</a>
    <span class="breadcrumbs-separator">/</span>
    <span style="color: #0f172a; font-weight: 600;">{{ $category->name }}</span>
@endsection

@section('content')
    <!-- Inline Breadcrumbs Navigation -->
    <nav style="display: flex; align-items: center; gap: 8px; font-size: 12.5px; color: #64748b; margin-bottom: 14px;" aria-label="Breadcrumb">
        <a href="{{ route('dashboard') }}" style="color: #64748b; text-decoration: none; display: flex; align-items: center; gap: 4px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
            <span>Dashboard</span>
        </a>
        <span style="color: #cbd5e1;">/</span>
        <a href="{{ route('categories.index') }}" style="color: #64748b; text-decoration: none;">Categories</a>
        <span style="color: #cbd5e1;">/</span>
        <span style="color: #0f172a; font-weight: 600;">{{ $category->name }}</span>
    </nav>

    <div class="page-header-card">
        <div class="page-title-wrap">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                @if ($category->status === 'active')
                    <span class="badge-success">Active Category</span>
                @else
                    <span class="badge-muted">Inactive</span>
                @endif
            </div>
            <h1>{{ $category->name }}</h1>
            <p>{{ $category->description ?: 'No category description provided.' }}</p>
        </div>
        <div class="page-actions-wrap">
            <a class="button secondary" href="{{ route('categories.edit', $category) }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                <span>Edit Category</span>
            </a>
            <a class="button secondary" href="{{ route('categories.index') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                <span>Back to Categories</span>
            </a>
        </div>
    </div>

    <!-- Products in this category -->
    <div class="modern-table-card">
        <div style="padding: 16px 20px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between;">
            <div>
                <h2 style="margin: 0; font-size: 15px; color: #0f172a;">Assigned Products</h2>
                <small class="muted">{{ $products->total() }} total {{ Str::plural('product', $products->total()) }} mapped to this category</small>
            </div>
            <a class="button secondary" href="{{ route('products.create') }}" style="padding: 6px 12px; font-size: 12px;">+ Add Product</a>
        </div>

        <table class="clean-table">
            <thead>
                <tr>
                    <th style="width: 25%;">SKU</th>
                    <th style="width: 45%;">Product Name</th>
                    <th style="width: 15%;">Unit</th>
                    <th style="width: 15%;">Status</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($products as $product)
                <tr>
                    <td><span class="pill-code">{{ $product->sku }}</span></td>
                    <td><a href="{{ route('products.show', $product) }}" style="font-weight: 700; color: #0f172a; text-decoration: none;">{{ $product->name }}</a></td>
                    <td>{{ $product->unit }}</td>
                    <td>
                        @if ($product->status === 'active')
                            <span class="badge-success">Active</span>
                        @else
                            <span class="badge-muted">Inactive</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align: center; padding: 32px 20px; color: #64748b;">
                        No products currently assigned to this category.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 14px;">
        {{ $products->links() }}
    </div>
@endsection
