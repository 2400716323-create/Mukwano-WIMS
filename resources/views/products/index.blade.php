@extends('catalog.layout')

@section('title', 'Products')

@section('breadcrumbs')
    <span class="breadcrumbs-separator">/</span>
    <span style="color: #0f172a; font-weight: 600;">Products</span>
@endsection

@section('content')
    <!-- Inline Breadcrumbs Bar -->
    <nav style="display: flex; align-items: center; gap: 8px; font-size: 12.5px; color: #64748b; margin-bottom: 14px;" aria-label="Breadcrumb">
        <a href="{{ route('dashboard') }}" style="color: #64748b; text-decoration: none; display: flex; align-items: center; gap: 4px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
            <span>Dashboard</span>
        </a>
        <span style="color: #cbd5e1;">/</span>
        <span style="color: #0f172a; font-weight: 600;">Products Catalog</span>
    </nav>

    <!-- 1. Page Header Card -->
    <div class="page-header-card">
        <div class="page-title-wrap">
            <h1>Products Catalog</h1>
            <p>Active items, master specifications, safety thresholds, and inventory classification.</p>
        </div>
        <div class="page-actions-wrap">
            <a class="button secondary" href="{{ route('batches.index') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                <span>Batches</span>
            </a>
            <a class="button secondary" href="{{ route('products.export') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                <span>Export CSV</span>
            </a>
            <a class="button" href="{{ route('products.create') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                <span>Add Product</span>
            </a>
        </div>
    </div>

    <!-- 2. Four KPI Metric Cards -->
    <section class="stat-grid-4" aria-label="Product Metrics">
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(16, 185, 129, 0.12); color: #10b981;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Total Products</span>
                <strong class="stat-num">{{ number_format($metrics['total'] ?? $products->total()) }}</strong>
                <span class="stat-sub">Registered SKUs</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(37, 99, 235, 0.12); color: #2563eb;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Active SKUs</span>
                <strong class="stat-num">{{ number_format($metrics['active'] ?? $products->total()) }}</strong>
                <span class="stat-sub">Ready for operations</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(124, 58, 237, 0.12); color: #7c3aed;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Categories</span>
                <strong class="stat-num">{{ number_format($metrics['categories'] ?? $categories->count()) }}</strong>
                <span class="stat-sub">Active groups</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(245, 158, 11, 0.12); color: #f59e0b;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Inactive SKUs</span>
                <strong class="stat-num">{{ number_format($metrics['inactive'] ?? 0) }}</strong>
                <span class="stat-sub">Archived or paused</span>
            </div>
        </div>
    </section>

    <!-- 3. Search & Filter Bar -->
    <form class="filter-card" method="GET" action="{{ route('products.index') }}">
        <div style="position: relative; flex: 1; min-width: 260px;">
            <svg style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none;" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search by SKU, SAP number, or product name..." aria-label="Search products" style="width: 100%; padding-left: 36px;">
        </div>

        <select name="category_id" aria-label="Filter by category">
            <option value="">All Categories</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected(($filters['category_id'] ?? '') == $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>

        <select name="status" aria-label="Filter by status">
            <option value="">All Statuses</option>
            <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
            <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
        </select>

        <button type="submit" class="button">Filter</button>
        @if (request()->hasAny(['search', 'category_id', 'status']))
            <a class="button secondary" href="{{ route('products.index') }}">Clear</a>
        @endif
    </form>

    <!-- 4. CSV Import Panel (Collapsible) -->
    <details class="card" style="margin-bottom: 16px; border-radius: 12px; padding: 14px 18px;">
        <summary style="font-weight: 600; cursor: pointer; display: flex; justify-content: space-between; align-items: center; user-select: none;">
            <span style="display: flex; align-items: center; gap: 8px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                <span>Import Product-Master CSV</span>
            </span>
            <small class="muted">Upload bulk SAP material masters</small>
        </summary>
        <form style="display: flex; align-items: center; gap: 12px; margin-top: 14px; flex-wrap: wrap;" method="POST" action="{{ route('products.import') }}" enctype="multipart/form-data">
            @csrf
            <input id="csv-file" type="file" name="file" accept=".csv,text/csv" required style="padding: 6px 10px;">
            <button class="button secondary" type="submit">Preview Import</button>
            <span class="muted" style="font-size: 11.5px;">Uploads are mapped and validated before confirmation. SAP material number is required.</span>
        </form>
    </details>

    <!-- 5. Streamlined, Highly Organized Products Table -->
    @php
        $sortLink = fn (string $column): string => route('products.index', array_merge(request()->query(), [
            'sort' => $column,
            'direction' => $sort === $column && $direction === 'asc' ? 'desc' : 'asc',
        ]));
    @endphp

    <div class="modern-table-card">
        <div style="padding: 14px 18px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-weight: 700; color: #0f172a; font-size: 14px;">Products Register</span>
                <span class="pill-code" style="font-size: 11px;">Page {{ $products->currentPage() }} of {{ $products->lastPage() }}</span>
            </div>
            <span style="font-size: 12px; color: #64748b;">Showing {{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }} of {{ $products->total() }} items</span>
        </div>
        <table class="clean-table">
            <thead>
                <tr>
                    <th style="width: 48%;"><a href="{{ $sortLink('name') }}">Product Details ↕</a></th>
                    <th style="width: 20%;"><a href="{{ $sortLink('category') }}">Category ↕</a></th>
                    <th style="width: 12%;"><a href="{{ $sortLink('unit') }}">Unit ↕</a></th>
                    <th style="width: 10%;"><a href="{{ $sortLink('status') }}">Status ↕</a></th>
                    <th style="width: 10%; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <a href="{{ route('products.show', $product) }}" style="flex-shrink: 0;">
                                    <img class="thumb" style="width: 42px; height: 42px; border-radius: 8px; object-fit: cover; border: 1px solid #e2e8f0; background: #f8fafc;" src="{{ $product->image_path ? route('products.image', ['filename' => basename($product->image_path)]) : asset('images/product-placeholder.svg') }}" alt="{{ $product->name }}">
                                </a>
                                <div style="display: flex; flex-direction: column; min-width: 0;">
                                    <a href="{{ route('products.show', $product) }}" style="font-weight: 700; color: #0f172a; text-decoration: none; font-size: 13.5px; line-height: 1.3;" title="{{ $product->name }}">
                                        {{ $product->name }}
                                    </a>
                                    <div style="display: flex; align-items: center; gap: 6px; margin-top: 3px; flex-wrap: wrap;">
                                        <span class="pill-code">{{ $product->sku }}</span>
                                        @if ($product->sap_material_number)
                                            <span style="font-size: 11px; color: #64748b; font-family: monospace;">SAP: {{ $product->sap_material_number }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge badge-purple" style="font-size: 11.5px; padding: 3px 9px;">
                                {{ $product->category->name }}
                            </span>
                        </td>
                        <td>
                            <span style="color: #475569; font-weight: 500; font-size: 12.5px;">{{ $product->unit }}</span>
                        </td>
                        <td>
                            @if ($product->status === 'active')
                                <span class="badge badge-success">Active</span>
                            @else
                                <span class="badge badge-danger">Inactive</span>
                            @endif
                        </td>
                        <td style="text-align: right;">
                            <div style="display: inline-flex; align-items: center; gap: 5px; justify-content: flex-end;">
                                <a class="button secondary" style="padding: 4px 9px; font-size: 12px;" href="{{ route('products.show', $product) }}">View</a>
                                <a class="button secondary" style="padding: 4px 9px; font-size: 12px;" href="{{ route('products.edit', $product) }}">Edit</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 36px 20px; color: #64748b;">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" style="margin-bottom: 8px;"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                            <p style="margin: 0; font-weight: 600; color: #334155;">No products found matching the criteria</p>
                            <small style="color: #94a3b8;">Try clearing the search query or create a new product.</small>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- 6. Pagination -->
    <div style="margin-top: 14px;">
        {{ $products->links() }}
    </div>
@endsection
