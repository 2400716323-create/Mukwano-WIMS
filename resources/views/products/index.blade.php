
@extends('catalog.layout')

@section('title', 'Products')

@section('breadcrumbs')
    <span class="breadcrumbs-separator">/</span>
    <span style="color:#0f172a;font-weight:700;">Products</span>
@endsection

@section('content')

<style>
    .products-page { color:#1e293b; }
    .products-page * { box-sizing:border-box; }

    .products-header {
        display:flex; justify-content:space-between; align-items:center;
        gap:18px; flex-wrap:wrap; padding:25px;
        background:linear-gradient(120deg,#102b46,#174d69);
        border-radius:16px; color:white; margin-bottom:22px;
        box-shadow:0 8px 22px rgba(15,45,70,.12);
    }
    .products-header h1 { margin:0 0 7px; font-size:25px; font-weight:800; }
    .products-header p { margin:0; color:#d5e6f1; font-size:13px; }
    .products-header .button { text-decoration:none; }
    .products-actions { display:flex; gap:9px; flex-wrap:wrap; }
    .products-actions .button { display:inline-flex; align-items:center; gap:7px; }

    .products-metrics {
        display:grid; grid-template-columns:repeat(4,minmax(0,1fr));
        gap:15px; margin-bottom:22px;
    }
    .products-metric {
        background:white; border:1px solid #e8edf3; border-radius:13px;
        padding:18px; display:flex; align-items:center; gap:13px;
        box-shadow:0 3px 12px rgba(15,23,42,.035);
    }
    .products-metric-icon {
        width:45px;height:45px;border-radius:12px;
        display:flex;align-items:center;justify-content:center;flex-shrink:0;
    }
    .products-metric-label { color:#64748b;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px; }
    .products-metric-value { display:block;font-size:25px;font-weight:800;color:#0f172a;margin-top:3px; }
    .products-metric-note { display:block;font-size:11px;color:#94a3b8;margin-top:2px; }

    .products-panel {
        background:white;border:1px solid #e8edf3;border-radius:13px;
        padding:18px;margin-bottom:18px;
        box-shadow:0 3px 12px rgba(15,23,42,.025);
    }
    .products-panel-heading {
        display:flex;justify-content:space-between;align-items:center;
        gap:12px;flex-wrap:wrap;margin-bottom:15px;
    }
    .products-panel-heading h3 { margin:0;font-size:14px;font-weight:800;color:#0f172a; }
    .products-panel-heading p { margin:4px 0 0;color:#94a3b8;font-size:11.5px; }

    .products-filter {
        display:grid;grid-template-columns:minmax(220px,2fr) 1fr 1fr auto auto;
        gap:10px;align-items:center;
    }
    .products-filter input,.products-filter select,.products-import input[type=file] {
        min-height:40px;border:1px solid #dce4ec;border-radius:8px;
        padding:9px 12px;background:#fff;color:#334155;width:100%;font-size:12px;
    }
    .products-filter input:focus,.products-filter select:focus {
        outline:none;border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.09);
    }
    .products-filter .button { min-height:40px; }

    .products-import summary {
        cursor:pointer;display:flex;justify-content:space-between;
        align-items:center;gap:12px;flex-wrap:wrap;font-weight:700;
        font-size:13px;color:#0f172a;list-style:none;
    }
    .products-import summary::-webkit-details-marker { display:none; }
    .products-import summary small { color:#94a3b8;font-weight:400; }
    .products-import-form {
        display:flex;align-items:center;gap:12px;flex-wrap:wrap;
        border-top:1px solid #edf1f5;margin-top:15px;padding-top:15px;
    }
    .products-import-form input { flex:1;min-width:220px; }

    .products-table-card {
        background:white;border:1px solid #e8edf3;border-radius:13px;
        overflow:hidden;box-shadow:0 3px 12px rgba(15,23,42,.025);
    }
    .products-table-top {
        display:flex;justify-content:space-between;align-items:center;
        gap:10px;flex-wrap:wrap;padding:17px 19px;
        border-bottom:1px solid #edf1f5;
    }
    .products-table-top strong { font-size:14px;color:#0f172a; }
    .products-table-top span { font-size:11.5px;color:#64748b; }

    .products-table-wrap { overflow-x:auto; }
    .products-table { width:100%;border-collapse:collapse;min-width:760px; }
    .products-table th {
        background:#f8fafc;padding:13px 15px;text-align:left;
        color:#64748b;font-size:10.5px;text-transform:uppercase;
        letter-spacing:.5px;font-weight:800;white-space:nowrap;
    }
    .products-table th a { color:inherit;text-decoration:none; }
    .products-table td {
        padding:13px 15px;border-top:1px solid #f1f5f9;
        font-size:12px;vertical-align:middle;
    }
    .products-table tbody tr:hover { background:#f8fbff; }
    .product-details { display:flex;align-items:center;gap:12px;min-width:230px; }
    .product-image {
        width:43px;height:43px;border-radius:10px;object-fit:cover;
        border:1px solid #e2e8f0;background:#f8fafc;flex-shrink:0;
    }
    .product-name { display:block;color:#0f172a;font-size:12.5px;font-weight:800;text-decoration:none; }
    .product-name:hover { color:#2563eb; }
    .product-reference { display:flex;align-items:center;gap:7px;flex-wrap:wrap;margin-top:5px; }
    .product-sku {
        background:#f1f5f9;color:#475569;padding:3px 7px;
        border-radius:5px;font-size:10px;font-weight:700;
    }
    .product-sap { color:#94a3b8;font-size:10px; }
    .product-category {
        display:inline-block;background:#f1edff;color:#7043b5;
        padding:5px 9px;border-radius:20px;font-size:10.5px;font-weight:700;
    }
    .product-status {
        display:inline-flex;align-items:center;gap:6px;
        padding:5px 9px;border-radius:20px;font-size:10.5px;font-weight:700;
    }
    .product-status:before {
        content:"";width:6px;height:6px;border-radius:50%;background:currentColor;
    }
    .product-status.active { background:#e8f8ef;color:#15803d; }
    .product-status.inactive { background:#fff0f0;color:#dc2626; }
    .product-row-actions { display:flex;justify-content:flex-end;gap:6px; }
    .product-row-actions .button { padding:6px 10px;font-size:11px; }

    .products-empty { padding:45px 20px;text-align:center;color:#94a3b8; }
    .products-empty strong { display:block;color:#334155;margin:9px 0 5px;font-size:13px; }
    .products-empty p { margin:0;font-size:11.5px; }

    .products-pagination { margin-top:17px; }

    @media(max-width:950px) {
        .products-metrics { grid-template-columns:repeat(2,minmax(0,1fr)); }
        .products-filter { grid-template-columns:1fr 1fr; }
        .products-filter input { grid-column:1/-1; }
    }
    @media(max-width:600px) {
        .products-header { padding:19px; }
        .products-header h1 { font-size:21px; }
        .products-actions { width:100%; }
        .products-actions .button { flex:1;justify-content:center; }
        .products-metrics { gap:9px; }
        .products-metric { padding:12px;gap:9px; }
        .products-metric-icon { width:36px;height:36px; }
        .products-metric-value { font-size:20px; }
        .products-filter { grid-template-columns:1fr; }
        .products-filter input { grid-column:auto; }
    }
</style>

<div class="products-page">

    <nav style="display:flex;align-items:center;gap:8px;font-size:12px;color:#94a3b8;margin-bottom:15px;" aria-label="Breadcrumb">
        <a href="{{ route('dashboard') }}" style="color:#64748b;text-decoration:none;">Dashboard</a>
        <span>/</span>
        <strong style="color:#0f172a;">Products Catalog</strong>
    </nav>

    <header class="products-header">
        <div>
            <h1>Products Catalog</h1>
            <p>Manage product records, specifications, categories and inventory classifications.</p>
        </div>

        <div class="products-actions">
            <a class="button secondary" href="{{ route('batches.index') }}">View Batches</a>
            <a class="button secondary" href="{{ route('products.export') }}">Export CSV</a>
            <a class="button" href="{{ route('products.create') }}">+ Add Product</a>
        </div>
    </header>

    <section class="products-metrics" aria-label="Product statistics">

        <div class="products-metric">
            <div class="products-metric-icon" style="background:#e8f8ef;color:#15945b;">
                <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
            <div>
                <span class="products-metric-label">Total Products</span>
                <strong class="products-metric-value">{{ number_format($metrics['total'] ?? $products->total()) }}</strong>
                <span class="products-metric-note">Registered product records</span>
            </div>
        </div>

        <div class="products-metric">
            <div class="products-metric-icon" style="background:#eaf2ff;color:#2563eb;">
                <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
            </div>
            <div>
                <span class="products-metric-label">Active SKUs</span>
                <strong class="products-metric-value">{{ number_format($metrics['active'] ?? $products->total()) }}</strong>
                <span class="products-metric-note">Active product records</span>
            </div>
        </div>

        <div class="products-metric">
            <div class="products-metric-icon" style="background:#f2edff;color:#7c3aed;">
                <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="8" y1="6" x2="21" y2="6"/>
                    <line x1="8" y1="12" x2="21" y2="12"/>
                    <line x1="8" y1="18" x2="21" y2="18"/>
                    <line x1="3" y1="6" x2="3.01" y2="6"/>
                    <line x1="3" y1="12" x2="3.01" y2="12"/>
                    <line x1="3" y1="18" x2="3.01" y2="18"/>
                </svg>
            </div>
            <div>
                <span class="products-metric-label">Categories</span>
                <strong class="products-metric-value">{{ number_format($metrics['categories'] ?? $categories->count()) }}</strong>
                <span class="products-metric-note">Product classifications</span>
            </div>
        </div>

        <div class="products-metric">
            <div class="products-metric-icon" style="background:#fff5e5;color:#d97706;">
                <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
            </div>
            <div>
                <span class="products-metric-label">Inactive SKUs</span>
                <strong class="products-metric-value">{{ number_format($metrics['inactive'] ?? 0) }}</strong>
                <span class="products-metric-note">Inactive product records</span>
            </div>
        </div>

    </section>

    <section class="products-panel">
        <div class="products-panel-heading">
            <div>
                <h3>Search and Filter Products</h3>
                <p>Find products using their name, SKU, SAP number, category or status.</p>
            </div>
        </div>

        <form class="products-filter" method="GET" action="{{ route('products.index') }}">

            <input
                type="search"
                name="search"
                value="{{ $filters['search'] ?? '' }}"
                placeholder="Search product name, SKU or SAP number..."
                aria-label="Search products"
            >

            <select name="category_id" aria-label="Filter by category">
                <option value="">All Categories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected(($filters['category_id'] ?? '') == $category->id)>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>

            <select name="status" aria-label="Filter by status">
                <option value="">All Statuses</option>
                <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
            </select>

            <button type="submit" class="button">Apply Filters</button>

            @if (request()->hasAny(['search', 'category_id', 'status']))
                <a href="{{ route('products.index') }}" class="button secondary">Clear</a>
            @endif

        </form>
    </section>

    <details class="products-panel products-import">
        <summary>
            <span>↑ Import Product Master CSV</span>
            <small>Upload and preview product records</small>
        </summary>

        <form class="products-import-form" method="POST" action="{{ route('products.import') }}" enctype="multipart/form-data">
            @csrf
            <input type="file" name="file" accept=".csv,text/csv" required>
            <button class="button secondary" type="submit">Preview Import</button>
            <span style="font-size:11px;color:#94a3b8;">
                Select a CSV file. Product information will be validated before confirmation.
            </span>
        </form>
    </details>

    @php
        $sortLink = fn (string $column): string => route('products.index', array_merge(request()->query(), [
            'sort' => $column,
            'direction' => $sort === $column && $direction === 'asc' ? 'desc' : 'asc',
        ]));
    @endphp

    <section class="products-table-card">

        <div class="products-table-top">
            <div>
                <strong>Products Register</strong>
                <span style="margin-left:8px;">Page {{ $products->currentPage() }} of {{ $products->lastPage() }}</span>
            </div>

            <span>
                Showing {{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }}
                of {{ $products->total() }} products
            </span>
        </div>

        <div class="products-table-wrap">
            <table class="products-table">
                <thead>
                    <tr>
                        <th style="width:43%;"><a href="{{ $sortLink('name') }}">Product Details ↕</a></th>
                        <th><a href="{{ $sortLink('category') }}">Category ↕</a></th>
                        <th><a href="{{ $sortLink('unit') }}">Unit ↕</a></th>
                        <th><a href="{{ $sortLink('status') }}">Status ↕</a></th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($products as $product)
                        <tr>
                            <td>
                                <div class="product-details">
                                    <a href="{{ route('products.show', $product) }}">
                                        <img
                                            class="product-image"
                                            src="{{ $product->image_path ? route('products.image', ['filename' => basename($product->image_path)]) : asset('images/product-placeholder.svg') }}"
                                            alt="{{ $product->name }}"
                                        >
                                    </a>

                                    <div style="min-width:0;">
                                        <a class="product-name" href="{{ route('products.show', $product) }}">
                                            {{ $product->name }}
                                        </a>

                                        <div class="product-reference">
                                            <span class="product-sku">{{ $product->sku }}</span>

                                            @if ($product->sap_material_number)
                                                <span class="product-sap">SAP: {{ $product->sap_material_number }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <span class="product-category">
                                    {{ $product->category?->name ?? 'Uncategorized' }}
                                </span>
                            </td>

                            <td style="color:#475569;font-weight:600;">
                                {{ $product->unit }}
                            </td>

                            <td>
                                @if ($product->status === 'active')
                                    <span class="product-status active">Active</span>
                                @else
                                    <span class="product-status inactive">Inactive</span>
                                @endif
                            </td>

                            <td>
                                <div class="product-row-actions">
                                    <a class="button secondary" href="{{ route('products.show', $product) }}">View</a>
                                    <a class="button secondary" href="{{ route('products.edit', $product) }}">Edit</a>
                                </div>
                            </td>
                        </tr>

                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="products-empty">
                                    <svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                    <strong>No products found</strong>
                                    <p>Try changing your search or filters to find products.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </section>

    <div class="products-pagination">
        {{ $products->links() }}
    </div>

</div>
@endsection
