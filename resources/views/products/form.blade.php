@extends('catalog.layout')

@section('title', $product->exists ? 'Edit product' : 'New product')

@section('breadcrumbs')
    <span class="breadcrumbs-separator">/</span>
    <a href="{{ route('products.index') }}">Products</a>
    <span class="breadcrumbs-separator">/</span>
    @if ($product->exists)
        <a href="{{ route('products.show', $product) }}">{{ $product->name }}</a>
        <span class="breadcrumbs-separator">/</span>
        <span style="color: #0f172a; font-weight: 600;">Edit</span>
    @else
        <span style="color: #0f172a; font-weight: 600;">New Product</span>
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
        <a href="{{ route('products.index') }}" style="color: #64748b; text-decoration: none;">Products</a>
        <span style="color: #cbd5e1;">/</span>
        @if ($product->exists)
            <a href="{{ route('products.show', $product) }}" style="color: #64748b; text-decoration: none;">{{ $product->name }}</a>
            <span style="color: #cbd5e1;">/</span>
            <span style="color: #0f172a; font-weight: 600;">Edit</span>
        @else
            <span style="color: #0f172a; font-weight: 600;">New Product</span>
        @endif
    </nav>

    <div class="page-header-card">
        <div class="page-title-wrap">
            <h1>{{ $product->exists ? 'Edit Product' : 'New Product' }}</h1>
            <p>{{ $product->exists ? 'Update product specifications, thresholds, and imagery.' : 'Register a new SKU with safety stock thresholds and units.' }}</p>
        </div>
        <div class="page-actions-wrap">
            <a class="button secondary" href="{{ $product->exists ? route('products.show', $product) : route('products.index') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                <span>Cancel</span>
            </a>
        </div>
    </div>

    <form class="card" method="POST" enctype="multipart/form-data" action="{{ $product->exists ? route('products.update', $product) : route('products.store') }}" style="max-width: 960px;">
        @csrf
        @if ($product->exists)
            @method('PUT')
        @endif
        <div class="form-grid">
            <div class="field">
                <label for="sku">SKU Code</label>
                <input id="sku" name="sku" required maxlength="100" value="{{ old('sku', $product->sku) }}" placeholder="e.g. MUK-OIL-5L">
                @error('sku') <small class="errors" style="display:block; margin-top:4px; padding:4px 8px; border-radius:4px;">{{ $message }}</small> @enderror
            </div>
            <div class="field">
                <label for="sap_material_number">SAP Material Number</label>
                <input id="sap_material_number" name="sap_material_number" maxlength="100" value="{{ old('sap_material_number', $product->sap_material_number) }}" placeholder="e.g. 10004523">
                @error('sap_material_number') <small class="errors" style="display:block; margin-top:4px; padding:4px 8px; border-radius:4px;">{{ $message }}</small> @enderror
            </div>
            <div class="field full">
                <label for="name">Product Name</label>
                <input id="name" name="name" required maxlength="255" value="{{ old('name', $product->name) }}" placeholder="e.g. Mukwano Cooking Oil 5L">
                @error('name') <small class="errors" style="display:block; margin-top:4px; padding:4px 8px; border-radius:4px;">{{ $message }}</small> @enderror
            </div>
            <div class="field">
                <label for="category_id">Category</label>
                <select id="category_id" name="category_id" required>
                    <option value="">Select Category...</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
                @error('category_id') <small class="errors" style="display:block; margin-top:4px; padding:4px 8px; border-radius:4px;">{{ $message }}</small> @enderror
            </div>
            <div class="field">
                <label for="unit">Unit of Measure</label>
                <input id="unit" name="unit" required maxlength="32" value="{{ old('unit', $product->unit) }}" placeholder="e.g. pcs, cartons, kg, litres">
                @error('unit') <small class="errors" style="display:block; margin-top:4px; padding:4px 8px; border-radius:4px;">{{ $message }}</small> @enderror
            </div>
            <div class="field">
                <label for="status">Catalog Status</label>
                <select id="status" name="status" required>
                    <option value="active" @selected(old('status', $product->status ?: 'active') === 'active')>Active</option>
                    <option value="inactive" @selected(old('status', $product->status) === 'inactive')>Inactive</option>
                </select>
            </div>
            <div class="field">
                <label for="min_level">Minimum Safety Stock Level</label>
                <input id="min_level" name="min_level" type="number" step="0.001" min="0" required value="{{ old('min_level', $product->min_level ?? '0') }}">
                @error('min_level') <small class="errors" style="display:block; margin-top:4px; padding:4px 8px; border-radius:4px;">{{ $message }}</small> @enderror
            </div>
            <div class="field">
                <label for="reorder_level">Reorder Trigger Level</label>
                <input id="reorder_level" name="reorder_level" type="number" step="0.001" min="0" required value="{{ old('reorder_level', $product->reorder_level ?? '0') }}">
                @error('reorder_level') <small class="errors" style="display:block; margin-top:4px; padding:4px 8px; border-radius:4px;">{{ $message }}</small> @enderror
            </div>
            <div class="field">
                <label for="max_level">Maximum Capacity Level</label>
                <input id="max_level" name="max_level" type="number" step="0.001" min="0" required value="{{ old('max_level', $product->max_level ?? '0') }}">
                @error('max_level') <small class="errors" style="display:block; margin-top:4px; padding:4px 8px; border-radius:4px;">{{ $message }}</small> @enderror
            </div>
            <div class="field full">
                <label for="description">Detailed Description</label>
                <textarea id="description" name="description" maxlength="5000" rows="3" placeholder="Enter packaging details, handling specifications, or supplier notes...">{{ old('description', $product->description) }}</textarea>
                @error('description') <small class="errors" style="display:block; margin-top:4px; padding:4px 8px; border-radius:4px;">{{ $message }}</small> @enderror
            </div>
            <div class="field full">
                <label for="image">Product Image</label>
                <div class="inline-image" style="background: #f8fafc; padding: 14px; border-radius: 8px; border: 1px dashed #cbd5e1;">
                    <img class="preview" id="image-preview" src="{{ $product->image_path ? route('products.image', ['filename' => basename($product->image_path)]) : asset('images/product-placeholder.svg') }}" alt="Product image preview" style="background:#fff; border-radius: 8px; width: 120px; height: 120px;">
                    <div>
                        <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp">
                        <p style="margin: 6px 0 0;"><small class="muted">JPG, PNG, or WebP; maximum 5 MB. Images are stored securely on local storage.</small></p>
                        @error('image') <small class="errors" style="display:block; margin-top:4px; padding:4px 8px; border-radius:4px;">{{ $message }}</small> @enderror
                        @if ($product->image_path)
                            <label style="margin-top: 8px; display: flex; align-items: center; gap: 6px; font-weight: normal; cursor: pointer;">
                                <input id="remove-image" type="checkbox" name="remove_image" value="1" @checked(old('remove_image'))>
                                <span>Remove current image</span>
                            </label>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div style="margin-top: 18px; display: flex; align-items: center; gap: 10px;">
            <button type="submit" class="button">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                <span>{{ $product->exists ? 'Save Changes' : 'Create Product' }}</span>
            </button>
            <a class="button secondary" href="{{ $product->exists ? route('products.show', $product) : route('products.index') }}">Cancel</a>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        const imageInput = document.getElementById('image');
        const imagePreview = document.getElementById('image-preview');
        const removeImage = document.getElementById('remove-image');
        const placeholder = @json(asset('images/product-placeholder.svg'));
        let previewUrl = null;

        imageInput.addEventListener('change', () => {
            const file = imageInput.files && imageInput.files[0];
            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
                previewUrl = null;
            }
            if (!file) {
                imagePreview.src = placeholder;
                return;
            }
            previewUrl = URL.createObjectURL(file);
            imagePreview.src = previewUrl;
            if (removeImage) {
                removeImage.checked = false;
            }
        });

        if (removeImage) {
            removeImage.addEventListener('change', () => {
                if (removeImage.checked) {
                    imageInput.value = '';
                    if (previewUrl) {
                        URL.revokeObjectURL(previewUrl);
                        previewUrl = null;
                    }
                    imagePreview.src = placeholder;
                }
            });
        }
    </script>
@endpush
