@extends('catalog.layout')

@section('title', 'Categories')

@section('breadcrumbs')
    <span class="breadcrumbs-separator">/</span>
    <span style="color: #0f172a; font-weight: 600;">Categories</span>
@endsection

@section('content')
    <!-- Inline Breadcrumbs Navigation -->
    <nav style="display: flex; align-items: center; gap: 8px; font-size: 12.5px; color: #64748b; margin-bottom: 14px;" aria-label="Breadcrumb">
        <a href="{{ route('dashboard') }}" style="color: #64748b; text-decoration: none; display: flex; align-items: center; gap: 4px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
            <span>Dashboard</span>
        </a>
        <span style="color: #cbd5e1;">/</span>
        <span style="color: #0f172a; font-weight: 600;">Categories</span>
    </nav>

    <!-- Unified Page Header -->
    <div class="page-header-card">
        <div class="page-title-wrap">
            <h1>Categories</h1>
            <p>Organize warehouse catalog items into logical departments and track inventory classification.</p>
        </div>
        <div class="page-actions-wrap">
            <a class="button" href="{{ route('categories.create') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                <span>Add Category</span>
            </a>
        </div>
    </div>

    <!-- 4 Top KPI Cards -->
    <div class="stat-grid-4">
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(16, 185, 129, 0.12); color: #10b981;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h7"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Total Categories</span>
                <span class="stat-num">{{ number_format($metrics['total'] ?? $categories->total()) }}</span>
                <span class="stat-sub">Defined catalog groups</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(37, 99, 235, 0.12); color: #2563eb;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Active Categories</span>
                <span class="stat-num">{{ number_format($metrics['active'] ?? 0) }}</span>
                <span class="stat-sub">Available for stock assignment</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(245, 158, 11, 0.12); color: #f59e0b;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Assigned Products</span>
                <span class="stat-num">{{ number_format($metrics['assigned_products'] ?? 0) }}</span>
                <span class="stat-sub">Catalog items mapped</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(100, 116, 139, 0.12); color: #64748b;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Inactive Categories</span>
                <span class="stat-num">{{ number_format($metrics['inactive'] ?? 0) }}</span>
                <span class="stat-sub">Archived or phased out</span>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="filter-card">
        <form method="GET" action="{{ route('categories.index') }}" style="display: flex; align-items: center; gap: 10px; width: 100%; flex-wrap: wrap; margin: 0;">
            <div style="position: relative; flex: 1; min-width: 260px;">
                <svg style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none;" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Search category name or description..." aria-label="Search categories" style="width: 100%;">
            </div>
            <button type="submit" class="button">Filter</button>
            @if (request()->filled('search'))
                <a class="button secondary" href="{{ route('categories.index') }}">Reset</a>
            @endif
        </form>
    </div>

    <!-- Modern Table Card -->
    <div class="modern-table-card">
        <table class="clean-table">
            <thead>
                <tr>
                    <th style="width: 28%;">Category Name</th>
                    <th style="width: 32%;">Description</th>
                    <th style="width: 14%;">Products Count</th>
                    <th style="width: 12%;">Status</th>
                    <th style="width: 14%; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($categories as $category)
                <tr>
                    <td>
                        <a href="{{ route('categories.show', $category) }}" style="font-weight: 700; color: #0f172a; text-decoration: none; display: flex; align-items: center; gap: 8px;">
                            <span style="display: inline-flex; width: 28px; height: 28px; border-radius: 6px; background: #f1f5f9; color: #2563eb; align-items: center; justify-content: center; font-size: 12px; font-weight: 700;">
                                {{ strtoupper(substr($category->name, 0, 1)) }}
                            </span>
                            <span>{{ $category->name }}</span>
                        </a>
                    </td>
                    <td>
                        <span style="color: #64748b; font-size: 12.5px;">{{ $category->description ?: '—' }}</span>
                    </td>
                    <td>
                        <span class="pill-code" style="font-weight: 700; font-size: 12px;">{{ $category->products_count }} {{ Str::plural('SKU', $category->products_count) }}</span>
                    </td>
                    <td>
                        @if ($category->status === 'active')
                            <span class="badge-success">Active</span>
                        @else
                            <span class="badge-muted">Inactive</span>
                        @endif
                    </td>
                    <td style="text-align: right;">
                        <div style="display: inline-flex; align-items: center; gap: 6px; justify-content: flex-end;">
                            <a href="{{ route('categories.edit', $category) }}" class="button secondary" style="padding: 4px 10px; font-size: 12px;">Edit</a>
                            @if ($category->status === 'active')
                                <form class="inline-form" method="POST" action="{{ route('categories.deactivate', $category) }}" style="margin: 0;">
                                    @csrf
                                    @method('PATCH')
                                    @if ($category->products_count > 0)
                                        <select name="reassign_to" required aria-label="Reassign products from {{ $category->name }}" style="padding: 3px 6px; font-size: 11px; max-width: 130px; border-radius: 6px;">
                                            <option value="">Move to…</option>
                                            @foreach ($availableCategories as $availableCategory)
                                                @if ($availableCategory->id !== $category->id)
                                                    <option value="{{ $availableCategory->id }}">{{ $availableCategory->name }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    @endif
                                    <button class="button secondary" type="submit" style="padding: 4px 8px; font-size: 12px; color: #d97706;">Deactivate</button>
                                </form>
                            @else
                                <form class="inline-form" method="POST" action="{{ route('categories.activate', $category) }}" style="margin: 0;">
                                    @csrf
                                    @method('PATCH')
                                    <button class="button secondary" type="submit" style="padding: 4px 8px; font-size: 12px; color: #16a34a;">Activate</button>
                                </form>
                            @endif
                            <form class="inline-form" method="POST" action="{{ route('categories.destroy', $category) }}" onsubmit="return confirm('Delete this category permanently?')" style="margin: 0;">
                                @csrf
                                @method('DELETE')
                                @if ($category->products_count > 0)
                                    <select name="reassign_to" required aria-label="Reassign products before deleting {{ $category->name }}" style="padding: 3px 6px; font-size: 11px; max-width: 130px; border-radius: 6px;">
                                        <option value="">Move to…</option>
                                        @foreach ($availableCategories as $availableCategory)
                                            @if ($availableCategory->id !== $category->id)
                                                <option value="{{ $availableCategory->id }}">{{ $availableCategory->name }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                @endif
                                <button class="button danger" type="submit" style="padding: 4px 8px; font-size: 12px;">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; padding: 36px 20px; color: #64748b;">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" style="margin-bottom: 8px;"><path d="M4 6h16M4 12h16M4 18h7"/></svg>
                        <p style="margin: 0; font-weight: 600; color: #334155;">No categories found matching your criteria</p>
                        <small style="color: #94a3b8;">Try clearing the search query or create a new category.</small>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div style="margin-top: 14px;">
        {{ $categories->links() }}
    </div>
@endsection
