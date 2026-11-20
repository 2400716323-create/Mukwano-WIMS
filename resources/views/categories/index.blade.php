@extends('catalog.layout')

@section('title', 'Categories')

@section('content')
<style>
    .cat-page{display:grid;gap:20px}
    .cat-header{display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap}
    .cat-header h1{margin:0;color:#0f172a;font-size:25px}
    .cat-header p{margin:6px 0 0;color:#64748b;font-size:13px}
    .cat-btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;padding:9px 14px;border-radius:8px;border:1px solid #dbe2ea;background:#fff;color:#334155;text-decoration:none;font-size:13px;font-weight:600;cursor:pointer}
    .cat-btn.primary{background:#2563eb;border-color:#2563eb;color:#fff}
    .cat-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}
    .cat-stat{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:17px}
    .cat-stat span{display:block;color:#64748b;font-size:12px}
    .cat-stat strong{display:block;margin-top:7px;color:#0f172a;font-size:25px}
    .cat-panel{background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden}
    .cat-filter{padding:16px;display:flex;gap:10px;flex-wrap:wrap}
    .cat-filter input{flex:1;min-width:220px;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;font-size:13px}
    .cat-table-wrap{overflow-x:auto}
    .cat-table{width:100%;border-collapse:collapse;min-width:700px}
    .cat-table th{padding:13px 16px;text-align:left;background:#f8fafc;color:#64748b;font-size:11px;text-transform:uppercase;letter-spacing:.04em}
    .cat-table td{padding:15px 16px;border-top:1px solid #edf1f5;color:#475569;font-size:13px}
    .cat-name{color:#0f172a;font-weight:700;text-decoration:none}
    .cat-name:hover{color:#2563eb}
    .cat-initial{display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;margin-right:9px;border-radius:9px;background:#eff6ff;color:#2563eb;font-weight:700}
    .cat-badge{display:inline-block;padding:5px 9px;border-radius:20px;font-size:11px;font-weight:700}
    .cat-active{background:#dcfce7;color:#166534}
    .cat-inactive{background:#f1f5f9;color:#475569}
    .cat-count{font-weight:700;color:#334155}
    .cat-empty{text-align:center;padding:38px 16px!important;color:#64748b!important}
    .cat-pagination{padding:15px}
    @media(max-width:900px){.cat-stats{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media(max-width:520px){.cat-stats{grid-template-columns:1fr 1fr}.cat-stat{padding:13px}.cat-stat strong{font-size:21px}}
</style>

<div class="cat-page">
    <div class="cat-header">
        <div>
            <h1>Categories</h1>
            <p>View and organise product classification groups.</p>
        </div>
        <a href="{{ route('categories.create') }}" class="cat-btn primary">
            <span>＋</span> Add Category
        </a>
    </div>

    <div class="cat-stats">
        <div class="cat-stat">
            <span>Total Categories</span>
            <strong>{{ number_format($metrics['total'] ?? $categories->total()) }}</strong>
        </div>
        <div class="cat-stat">
            <span>Active Categories</span>
            <strong>{{ number_format($metrics['active'] ?? 0) }}</strong>
        </div>
        <div class="cat-stat">
            <span>Assigned Products</span>
            <strong>{{ number_format($metrics['assigned_products'] ?? 0) }}</strong>
        </div>
        <div class="cat-stat">
            <span>Inactive Categories</span>
            <strong>{{ number_format($metrics['inactive'] ?? 0) }}</strong>
        </div>
    </div>

    <div class="cat-panel">
        <form method="GET" action="{{ route('categories.index') }}" class="cat-filter">
            <input
                type="search"
                name="search"
                value="{{ request('search') }}"
                placeholder="Search by category name or description..."
                aria-label="Search categories"
            >
            <button type="submit" class="cat-btn primary">Search</button>
            @if(request()->filled('search'))
                <a href="{{ route('categories.index') }}" class="cat-btn">Clear</a>
            @endif
        </form>

        <div class="cat-table-wrap">
            <table class="cat-table">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Assigned Products</th>
                        <th>Status</th>
                        <th>View</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                        <tr>
                            <td>
                                <a href="{{ route('categories.show', $category) }}" class="cat-name">
                                    <span class="cat-initial">{{ strtoupper(substr($category->name, 0, 1)) }}</span>
                                    {{ $category->name }}
                                </a>
                            </td>
                            <td>{{ $category->description ?: 'No description provided' }}</td>
                            <td>
                                <span class="cat-count">
                                    {{ number_format($category->products_count) }}
                                    {{ Str::plural('product', $category->products_count) }}
                                </span>
                            </td>
                            <td>
                                @if($category->status === 'active')
                                    <span class="cat-badge cat-active">Active</span>
                                @else
                                    <span class="cat-badge cat-inactive">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('categories.show', $category) }}" class="cat-btn">View details</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="cat-empty">
                                <strong>No categories found.</strong>
                                <div>Try another search term.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="cat-pagination">
            {{ $categories->links() }}
        </div>
    </div>
</div>
@endsection