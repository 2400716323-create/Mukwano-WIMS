@extends('catalog.layout')

@section('title', $category->exists ? 'Edit category' : 'New category')

@section('breadcrumbs')
    <a href="{{ route('categories.index') }}">Categories</a>
    <span class="sep">/</span>
    @if ($category->exists)
        <a href="{{ route('categories.show', $category) }}">{{ $category->name }}</a>
        <span class="sep">/</span>
        <span class="current">Edit</span>
    @else
        <span class="current">New Category</span>
    @endif
@endsection

@section('content')
    <!-- Unified Page Header -->
    <div class="page-header-card">
        <div class="page-title-wrap">
            <nav class="breadcrumb-trail" aria-label="Breadcrumb">
                <a href="{{ route('dashboard') }}">Dashboard</a>
                <span class="separator">/</span>
                <a href="{{ route('categories.index') }}">Categories</a>
                <span class="separator">/</span>
                <span class="current">{{ $category->exists ? 'Edit '.$category->name : 'New Category' }}</span>
            </nav>
            <h1>{{ $category->exists ? 'Edit Category' : 'New Category' }}</h1>
            <p>{{ $category->exists ? 'Update category naming and catalog scope.' : 'Define a new product classification group for inventory reporting.' }}</p>
        </div>
        <div class="page-actions-wrap">
            <a class="button secondary" href="{{ route('categories.index') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                <span>Back to Categories</span>
            </a>
        </div>
    </div>

    <!-- Centered Form Card -->
    <div class="card" style="max-width: 640px; margin: 0 auto; background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 24px;">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px; border-bottom: 1px solid #f1f5f9; padding-bottom: 14px;">
            <div style="width: 36px; height: 36px; border-radius: 8px; background: rgba(37, 99, 235, 0.1); color: #2563eb; display: flex; align-items: center; justify-content: center;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
            </div>
            <div>
                <h2 style="font-size: 15px; font-weight: 700; color: #0f172a; margin: 0;">Category Particulars</h2>
                <span style="font-size: 12px; color: #64748b;">Assigned to multiple SKUs across the warehouse</span>
            </div>
        </div>

        <form method="POST" action="{{ $category->exists ? route('categories.update', $category) : route('categories.store') }}">
            @csrf
            @if ($category->exists)
                @method('PUT')
            @endif

            <div class="field" style="margin-bottom: 18px;">
                <label for="name" style="font-size: 12.5px; font-weight: 600; color: #334155; margin-bottom: 6px; display: block;">
                    Category Name <span style="color: #ef4444;">*</span>
                </label>
                <input id="name" name="name" required maxlength="255" value="{{ old('name', $category->name) }}" placeholder="e.g. Edible Oils, Soaps & Detergents"
                       style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; font-size: 13.5px;">
                @error('name') <small class="errors" style="display:block; margin-top:4px; color: #dc2626; font-size: 12px;">{{ $message }}</small> @enderror
            </div>

            <div class="field" style="margin-bottom: 24px;">
                <label for="description" style="font-size: 12.5px; font-weight: 600; color: #334155; margin-bottom: 6px; display: block;">
                    Description (Optional)
                </label>
                <textarea id="description" name="description" maxlength="2000" rows="3" placeholder="Describe the types of goods or production lines grouped under this category..."
                          style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; font-size: 13px;">{{ old('description', $category->description) }}</textarea>
                @error('description') <small class="errors" style="display:block; margin-top:4px; color: #dc2626; font-size: 12px;">{{ $message }}</small> @enderror
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end; padding-top: 18px; border-top: 1px solid #f1f5f9;">
                <a class="button secondary" href="{{ route('categories.index') }}" style="padding: 8px 16px;">Cancel</a>
                <button type="submit" class="button" style="background: #2563eb; color: #ffffff; padding: 8px 22px; font-weight: 600;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    <span>{{ $category->exists ? 'Save Changes' : 'Create Category' }}</span>
                </button>
            </div>
        </form>
    </div>
@endsection
