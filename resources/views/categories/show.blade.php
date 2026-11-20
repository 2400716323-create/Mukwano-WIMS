@extends('catalog.layout')

@section('title', $category->name)

@section('breadcrumbs')
    <a href="{{ route('categories.index') }}">Categories</a>
    <span class="breadcrumbs-separator">/</span>
    <span>{{ $category->name }}</span>
@endsection

@section('content')
<style>
    .cat-show-page{display:grid;gap:18px}
    .cat-show-header{display:flex;align-items:flex-start;justify-content:space-between;gap:15px;flex-wrap:wrap;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:22px}
    .cat-show-header h1{margin:10px 0 6px;color:#0f172a;font-size:25px}
    .cat-show-header p{margin:0;color:#64748b;font-size:13px;line-height:1.6}
    .cat-show-actions{display:flex;gap:9px;flex-wrap:wrap}
    .cat-show-btn{display:inline-flex;align-items:center;justify-content:center;padding:9px 13px;border:1px solid #dbe2ea;border-radius:8px;background:#fff;color:#334155;text-decoration:none;font-size:13px;font-weight:600}
    .cat-show-btn.primary{background:#2563eb;border-color:#2563eb;color:#fff}
    .cat-show-badge{display:inline-block;padding:5px 10px;border-radius:20px;font-size:11px;font-weight:700}
    .cat-show-active{background:#dcfce7;color:#166534}
    .cat-show-inactive{background:#f1f5f9;color:#475569}
    .cat-show-panel{background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden}
    .cat-show-panel-head{padding:17px 20px;border-bottom:1px solid #edf1f5}
    .cat-show-panel-head h2{margin:0;color:#0f172a;font-size:16px}
    .cat-show-panel-head p{margin:5px 0 0;color:#64748b;font-size:12px}
    .cat-show-table-wrap{overflow-x:auto}
    .cat-show-table{width:100%;border-collapse:collapse;min-width:600px}
    .cat-show-table th{padding:13px 16px;background:#f8fafc;text-align:left;color:#64748b;font-size:11px;text-transform:uppercase}
    .cat-show-table td{padding:14px 16px;border-top:1px solid #edf1f5;color:#475569;font-size:13px}
    .cat-show-table a{color:#1d4ed8;text-decoration:none;font-weight:600}
    .cat-show-empty{text-align:center;padding:35px!important;color:#64748b!important}
    .cat-show-pagination{padding:15px}
</style>

<div class="cat-show-page">
    <div class="cat-show-header">
        <div>
            @if($category->status === 'active')
                <span class="cat-show-badge cat-show-active">Active Category</span>
            @else
                <span class="cat-show-badge cat-show-inactive">Inactive Category</span>
            @endif

            <h1>{{ $category->name }}</h1>
            <p>{{ $category->description ?: 'No description has been provided for this category.' }}</p>
        </div>

        <div class="cat-show-actions">
            <a href="{{ route('categories.edit', $category) }}" class="cat-show-btn primary">Edit Category</a>
            <a href="{{ route('categories.index') }}" class="cat-show-btn">Back to Categories</a>
        </div>
    </div>

    <div class="cat-show-panel">
        <div class="cat-show-panel-head">
            <h2>Assigned Products</h2>
            <p>
                {{ number_format($products->total()) }}
                {{ Str::plural('product', $products->total()) }}
                currently listed under this category.
            </p>
        </div>

        <div class="cat-show-table-wrap">
            <table class="cat-show-table">
                <thead>
                    <tr>
                        <th>Product SKU</th>
                        <th>Product Name</th>
                        <th>Unit</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr>
                            <td>{{ $product->sku ?: '—' }}</td>
                            <td>
                                <a href="{{ route('products.show', $product) }}">
                                    {{ $product->name }}
                                </a>
                            </td>
                            <td>{{ $product->unit ?: '—' }}</td>
                            <td>
                                @if($product->status === 'active')
                                    <span class="cat-show-badge cat-show-active">Active</span>
                                @else
                                    <span class="cat-show-badge cat-show-inactive">Inactive</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="cat-show-empty">
                                No products are currently assigned to this category.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="cat-show-pagination">
            {{ $products->links() }}
        </div>
    </div>
</div>
@endsection