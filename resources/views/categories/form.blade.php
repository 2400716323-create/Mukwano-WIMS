@extends('catalog.layout')

@section('title', $category->exists ? 'Edit category' : 'New category')

@section('breadcrumbs')
    <span class="breadcrumbs-separator">/</span>
    <a href="{{ route('categories.index') }}">Categories</a>
    <span class="breadcrumbs-separator">/</span>
    @if ($category->exists)
        <a href="{{ route('categories.show', $category) }}">{{ $category->name }}</a>
        <span class="breadcrumbs-separator">/</span>
        <span style="color: #0f172a; font-weight: 600;">Edit</span>
    @else
        <span style="color: #0f172a; font-weight: 600;">New Category</span>
    @endif
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
        @if ($category->exists)
            <a href="{{ route('categories.show', $category) }}" style="color: #64748b; text-decoration: none;">{{ $category->name }}</a>
            <span style="color: #cbd5e1;">/</span>
            <span style="color: #0f172a; font-weight: 600;">Edit</span>
        @else
            <span style="color: #0f172a; font-weight: 600;">New Category</span>
        @endif
    </nav>

    <div class="page-header-card">
        <div class="page-title-wrap">
            <h1>{{ $category->exists ? 'Edit Category' : 'New Category' }}</h1>
            <p>{{ $category->exists ? 'Update category naming and catalog scope.' : 'Define a new product classification group.' }}</p>
        </div>
        <div class="page-actions-wrap">
            <a class="button secondary" href="{{ route('categories.index') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                <span>Back to Categories</span>
            </a>
        </div>
    </div>

    <form class="card" method="POST" action="{{ $category->exists ? route('categories.update', $category) : route('categories.store') }}" style="max-width: 640px;">
        @csrf
        @if ($category->exists)
            @method('PUT')
        @endif
        <div class="field">
            <label for="name">Category Name</label>
            <input id="name" name="name" required maxlength="255" value="{{ old('name', $category->name) }}" placeholder="e.g. Edible Oils, Soaps & Detergents">
            @error('name') <small class="errors" style="display:block; margin-top:4px; padding:4px 8px; border-radius:4px;">{{ $message }}</small> @enderror
        </div>
        <div class="field">
            <label for="description">Description (Optional)</label>
            <textarea id="description" name="description" maxlength="2000" rows="3" placeholder="Describe the types of goods or production lines grouped under this category...">{{ old('description', $category->description) }}</textarea>
            @error('description') <small class="errors" style="display:block; margin-top:4px; padding:4px 8px; border-radius:4px;">{{ $message }}</small> @enderror
        </div>
        <div style="margin-top: 18px; display: flex; align-items: center; gap: 10px;">
            <button type="submit" class="button">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                <span>{{ $category->exists ? 'Save Changes' : 'Create Category' }}</span>
            </button>
            <a class="button secondary" href="{{ route('categories.index') }}">Cancel</a>
        </div>
    </form>
@endsection
