
@extends('catalog.layout')

@section('title', $product->exists ? 'Edit product' : 'New product')

@section('breadcrumbs')
    <a href="{{ route('products.index') }}">Products</a>
    <span class="sep">/</span>
    @if ($product->exists)
        <a href="{{ route('products.show', $product) }}">{{ $product->name }}</a>
        <span class="sep">/</span>
        <span class="current">Edit</span>
    @else
        <span class="current">New Product</span>
    @endif
@endsection

@section('content')
    <div class="page-header-card">
        <div class="page-title-wrap">
            <nav class="breadcrumb-trail" aria-label="Breadcrumb">
                <a href="{{ route('dashboard') }}">Dashboard</a>
                <span class="separator">/</span>
                <a href="{{ route('products.index') }}">Products</a>
                <span class="separator">/</span>
                <span class="current">{{ $product->exists ? 'Edit '.$product->name : 'New Product' }}</span>
            </nav>

            <h1>{{ $product->exists ? 'Edit Product' : 'New Product' }}</h1>
            <p>{{ $product->exists ? 'Update product specifications, inventory thresholds, and catalog imagery.' : 'Register a new SKU with safety stock thresholds, categories, and units.' }}</p>
        </div>

        <div class="page-actions-wrap">
            <a class="button secondary" href="{{ $product->exists ? route('products.show', $product) : route('products.index') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
                <span>Cancel</span>
            </a>
        </div>
    </div>

    <form
        method="POST"
        enctype="multipart/form-data"
        action="{{ $product->exists ? route('products.update', $product) : route('products.store') }}"
        style="max-width: 900px; margin: 0 auto; display: flex; flex-direction: column; gap: 20px;"
    >
        @csrf

        @if ($product->exists)
            @method('PUT')
        @endif

        {{-- Section 1: Identification and Basic Information --}}
        <div class="card" style="background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 24px;">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 18px; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px;">
                <div style="width: 28px; height: 28px; border-radius: 6px; background: rgba(37, 99, 235, 0.1); color: #2563eb; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px;">1</div>
                <div>
                    <h2 style="font-size: 15px; font-weight: 700; color: #0f172a; margin: 0;">Identification & Title</h2>
                    <p style="font-size: 12px; color: #64748b; margin: 0;">Unique item identifiers and human-readable names</p>
                </div>
            </div>

            <div class="form-grid">
                <div class="field">
                    <label for="sku" style="font-size: 12.5px; font-weight: 600; color: #334155;">
                        SKU Code <span style="color: #ef4444;">*</span>
                    </label>
                    <input
                        id="sku"
                        name="sku"
                        type="text"
                        required
                        maxlength="100"
                        value="{{ old('sku', $product->sku) }}"
                        placeholder="e.g. MUK-OIL-5L"
                        style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; font-family: monospace; font-size: 13.5px;"
                    >
                    @error('sku')
                        <small class="errors" style="display:block; margin-top:4px; color:#dc2626; font-size:12px;">{{ $message }}</small>
                    @enderror
                </div>

                <div class="field">
                    <label for="sap_material_number" style="font-size: 12.5px; font-weight: 600; color: #334155;">
                        SAP Material Number
                    </label>
                    <input
                        id="sap_material_number"
                        name="sap_material_number"
                        type="text"
                        maxlength="100"
                        value="{{ old('sap_material_number', $product->sap_material_number) }}"
                        placeholder="e.g. 10004523"
                        style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; font-family: monospace; font-size: 13.5px;"
                    >
                    @error('sap_material_number')
                        <small class="errors" style="display:block; margin-top:4px; color:#dc2626; font-size:12px;">{{ $message }}</small>
                    @enderror
                </div>

                <div class="field full">
                    <label for="name" style="font-size: 12.5px; font-weight: 600; color: #334155;">
                        Product Full Name <span style="color: #ef4444;">*</span>
                    </label>
                    <input
                        id="name"
                        name="name"
                        type="text"
                        required
                        maxlength="255"
                        value="{{ old('name', $product->name) }}"
                        placeholder="e.g. Mukwano Fortified Cooking Oil 5L Jerrycan"
                        style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; font-size: 13.5px;"
                    >
                    @error('name')
                        <small class="errors" style="display:block; margin-top:4px; color:#dc2626; font-size:12px;">{{ $message }}</small>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Section 2: Classification and Measurement --}}
        <div class="card" style="background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 24px;">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 18px; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px;">
                <div style="width: 28px; height: 28px; border-radius: 6px; background: rgba(124, 58, 237, 0.1); color: #7c3aed; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px;">2</div>
                <div>
                    <h2 style="font-size: 15px; font-weight: 700; color: #0f172a; margin: 0;">Classification & Status</h2>
                    <p style="font-size: 12px; color: #64748b; margin: 0;">Department taxonomy and physical units of measure</p>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
                <div class="field">
                    <label for="category_id" style="font-size: 12.5px; font-weight: 600; color: #334155;">
                        Category <span style="color: #ef4444;">*</span>
                    </label>
                    <select id="category_id" name="category_id" required style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; font-size: 13.5px;">
                        <option value="">Select Category...</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <small class="errors" style="display:block; margin-top:4px; color:#dc2626; font-size:12px;">{{ $message }}</small>
                    @enderror
                </div>

                <div class="field">
                    <label for="unit" style="font-size: 12.5px; font-weight: 600; color: #334155;">
                        Unit of Measure <span style="color: #ef4444;">*</span>
                    </label>
                    <input
                        id="unit"
                        name="unit"
                        type="text"
                        required
                        maxlength="32"
                        value="{{ old('unit', $product->unit) }}"
                        placeholder="e.g. pcs, cartons, kg, litres"
                        style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; font-size: 13.5px;"
                    >
                    @error('unit')
                        <small class="errors" style="display:block; margin-top:4px; color:#dc2626; font-size:12px;">{{ $message }}</small>
                    @enderror
                </div>

                <div class="field">
                    <label for="status" style="font-size: 12.5px; font-weight: 600; color: #334155;">
                        Catalog Status <span style="color: #ef4444;">*</span>
                    </label>
                    <select id="status" name="status" required style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; font-size: 13.5px;">
                        <option value="active" @selected(old('status', $product->status ?: 'active') === 'active')>Active</option>
                        <option value="inactive" @selected(old('status', $product->status) === 'inactive')>Inactive</option>
                    </select>
                    @error('status')
                        <small class="errors" style="display:block; margin-top:4px; color:#dc2626; font-size:12px;">{{ $message }}</small>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Section 3: Inventory Thresholds --}}
        <div class="card" style="background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 24px;">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 18px; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px;">
                <div style="width: 28px; height: 28px; border-radius: 6px; background: rgba(16, 185, 129, 0.1); color: #10b981; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px;">3</div>
                <div>
                    <h2 style="font-size: 15px; font-weight: 700; color: #0f172a; margin: 0;">Inventory Thresholds & Automation</h2>
                    <p style="font-size: 12px; color: #64748b; margin: 0;">Safety buffers to trigger automated warehouse alert notifications</p>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px;">
                <div class="field">
                    <label for="min_level" style="font-size: 12px; font-weight: 600; color: #334155;">
                        Minimum Safety Level <span style="color: #ef4444;">*</span>
                    </label>
                    <input
                        id="min_level"
                        name="min_level"
                        type="number"
                        step="0.001"
                        min="0"
                        required
                        value="{{ old('min_level', $product->min_level ?? '0') }}"
                        style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; font-size: 13.5px; font-weight: 600;"
                    >
                    <small style="display:block; color:#64748b; font-size:11px; margin-top:4px;">Generates critical alert if stock drops below.</small>
                    @error('min_level')
                        <small class="errors" style="display:block; margin-top:4px; color:#dc2626; font-size:12px;">{{ $message }}</small>
                    @enderror
                </div>

                <div class="field">
                    <label for="reorder_level" style="font-size: 12px; font-weight: 600; color: #334155;">
                        Reorder Trigger Level <span style="color: #ef4444;">*</span>
                    </label>
                    <input
                        id="reorder_level"
                        name="reorder_level"
                        type="number"
                        step="0.001"
                        min="0"
                        required
                        value="{{ old('reorder_level', $product->reorder_level ?? '0') }}"
                        style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; font-size: 13.5px; font-weight: 600;"
                    >
                    <small style="display:block; color:#64748b; font-size:11px; margin-top:4px;">Target point to initiate replenishment PO.</small>
                    @error('reorder_level')
                        <small class="errors" style="display:block; margin-top:4px; color:#dc2626; font-size:12px;">{{ $message }}</small>
                    @enderror
                </div>

                <div class="field">
                    <label for="max_level" style="font-size: 12px; font-weight: 600; color: #334155;">
                        Maximum Capacity Level <span style="color: #ef4444;">*</span>
                    </label>
                    <input
                        id="max_level"
                        name="max_level"
                        type="number"
                        step="0.001"
                        min="0"
                        required
                        value="{{ old('max_level', $product->max_level ?? '0') }}"
                        style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; font-size: 13.5px; font-weight: 600;"
                    >
                    <small style="display:block; color:#64748b; font-size:11px; margin-top:4px;">Upper storage ceiling per warehouse policy.</small>
                    @error('max_level')
                        <small class="errors" style="display:block; margin-top:4px; color:#dc2626; font-size:12px;">{{ $message }}</small>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Section 4: Specifications and Image --}}
        <div class="card" style="background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 24px;">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 18px; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px;">
                <div style="width: 28px; height: 28px; border-radius: 6px; background: rgba(245, 158, 11, 0.1); color: #f59e0b; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px;">4</div>
                <div>
                    <h2 style="font-size: 15px; font-weight: 700; color: #0f172a; margin: 0;">Specifications & Imagery</h2>
                    <p style="font-size: 12px; color: #64748b; margin: 0;">Visual asset and storage handling details</p>
                </div>
            </div>

            <div class="field" style="margin-bottom: 18px;">
                <label for="description" style="font-size: 12.5px; font-weight: 600; color: #334155;">Detailed Description</label>
                <textarea
                    id="description"
                    name="description"
                    maxlength="5000"
                    rows="3"
                    placeholder="Enter packaging details, handling specifications, or supplier notes..."
                    style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; font-size: 13px;"
                >{{ old('description', $product->description) }}</textarea>
                @error('description')
                    <small class="errors" style="display:block; margin-top:4px; color:#dc2626; font-size:12px;">{{ $message }}</small>
                @enderror
            </div>

            <div class="field">
                <label for="image" style="font-size: 12.5px; font-weight: 600; color: #334155; margin-bottom: 8px;">
                    Product Image Asset
                </label>

                <div style="display: flex; gap: 18px; align-items: center; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 10px; padding: 18px;">
                    <img
                        class="preview"
                        id="image-preview"
                        src="{{ $product->image_path ? route('products.image', ['filename' => basename($product->image_path)]) : asset('images/product-placeholder.svg') }}"
                        alt="Product image preview"
                        style="background:#ffffff; border-radius:8px; width:100px; height:100px; object-fit:contain; border:1px solid #e2e8f0; flex-shrink:0;"
                    >

                    <div style="flex: 1;">
                        <input
                            id="image"
                            name="image"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            style="font-size:12.5px; border:1px solid #cbd5e1; border-radius:6px; padding:6px 10px; background:#ffffff;"
                        >

                        <p style="margin:6px 0 0; font-size:11.5px; color:#64748b;">
                            Supported: JPG, PNG, or WebP. Maximum file size: 5 MB.
                        </p>

                        @error('image')
                            <small class="errors" style="display:block; margin-top:4px; color:#dc2626; font-size:12px;">{{ $message }}</small>
                        @enderror

                        @if ($product->image_path)
                            <label style="margin-top:10px; display:inline-flex; align-items:center; gap:6px; font-size:12.5px; color:#dc2626; cursor:pointer;">
                                <input id="remove-image" type="checkbox" name="remove_image" value="1" @checked(old('remove_image'))>
                                <span>Remove existing image</span>
                            </label>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Form Actions --}}
        <div style="background:#ffffff; border-radius:10px; border:1px solid #e2e8f0; padding:16px 24px; display:flex; align-items:center; justify-content:flex-end; gap:12px;">
            <a
                class="button secondary"
                href="{{ $product->exists ? route('products.show', $product) : route('products.index') }}"
                style="padding:9px 20px;"
            >
                Cancel
            </a>

            <button
                type="submit"
                class="button"
                style="background:#2563eb; color:#ffffff; padding:9px 24px; font-weight:600;"
            >
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11z"/>
                    <polyline points="17 21 17 13 7 13 7 21"/>
                    <polyline points="7 3 7 8 15 8"/>
                </svg>
                <span>{{ $product->exists ? 'Save Product Changes' : 'Create Product' }}</span>
            </button>
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

        if (imageInput && imagePreview) {
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
        }

        if (removeImage && imageInput && imagePreview) {
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

        window.addEventListener('beforeunload', () => {
            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
            }
        });
    </script>
@endpush
