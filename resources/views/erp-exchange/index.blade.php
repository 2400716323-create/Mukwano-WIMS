@extends('catalog.layout')

@section('title', 'ERP exchange')

@section('breadcrumbs')
    <span class="current">ERP Exchange</span>
@endsection

@section('content')
    <style>
        .file-dropzone {
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            background: #f8fafc;
            padding: 28px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .file-dropzone:hover, .file-dropzone.dragover {
            border-color: #2563eb;
            background: #eff6ff;
        }

        .file-dropzone-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: rgba(37, 99, 235, 0.1);
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .pill-tag {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 11.5px;
            font-weight: 600;
            background: #f1f5f9;
            color: #334155;
            border: 1px solid #e2e8f0;
        }

        .field-req-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 12px;
            margin-top: 14px;
        }

        .field-req-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px 14px;
        }

        .field-req-item strong {
            display: block;
            font-size: 12.5px;
            color: #0f172a;
            margin-bottom: 2px;
        }

        .field-req-item span {
            font-size: 11.5px;
            color: #64748b;
        }
    </style>

    <!-- Unified Page Header -->
    <div class="page-header-card">
        <div class="page-title-wrap">
            <nav class="breadcrumb-trail" aria-label="Breadcrumb">
                <a href="{{ route('dashboard') }}">Dashboard</a>
                <span class="separator">/</span>
                <span class="current">ERP Exchange</span>
            </nav>
            <h1>ERP exchange</h1>
            <p>Offline SAP file exchange. Imports synchronize product-master catalogs; warehouse stock balances and movements are strictly preserved.</p>
        </div>
        <div class="page-actions-wrap">
            <a class="button secondary" href="{{ route('erp-exchange.reconciliation.csv') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                <span>Stock Reconciliation CSV</span>
            </a>
            <a class="button secondary" href="{{ route('products.index') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                <span>Product Catalog</span>
            </a>
        </div>
    </div>

    <!-- 4 Top Capability Metric Cards -->
    <div class="stat-grid-4">
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(37, 99, 235, 0.12); color: #2563eb;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Catalog Import</span>
                <span class="stat-num">CSV / SAP</span>
                <span class="stat-sub">Batch master sync</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(16, 185, 129, 0.12); color: #10b981;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Validation Engine</span>
                <span class="stat-num">Staged</span>
                <span class="stat-sub">Pre-flight preview</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(245, 158, 11, 0.12); color: #f59e0b;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Duplicate Protection</span>
                <span class="stat-num">Automated</span>
                <span class="stat-sub">Collisions flagged</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(124, 58, 237, 0.12); color: #7c3aed;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Ledger Integrity</span>
                <span class="stat-num">Isolated</span>
                <span class="stat-sub">Stock data immutable</span>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: minmax(0, 1.25fr) minmax(0, 0.75fr); gap: 20px; align-items: start;">
        <!-- Left: Import Form Card -->
        <div class="card" style="background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
            <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; margin-bottom: 14px; padding-bottom: 14px; border-bottom: 1px solid #f1f5f9;">
                <div>
                    <h2 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0 0 4px 0;">Product-master CSV import</h2>
                    <p style="font-size: 12.5px; color: #64748b; margin: 0;">Upload a UTF-8 CSV (maximum 5 MB and 1,000 data rows), map its columns, review validation and duplicates, then confirm valid rows.</p>
                </div>
                <span class="pill-code" style="font-size: 11px;">Max 5 MB</span>
            </div>

            <form method="post" action="{{ route('erp-exchange.products.preview') }}" enctype="multipart/form-data">
                @csrf
                <div class="field" style="margin-bottom: 18px;">
                    <label for="file" style="display: block; font-size: 12.5px; font-weight: 600; color: #334155; margin-bottom: 8px;">Select CSV file <span style="color: #ef4444;">*</span></label>
                    
                    <div class="file-dropzone" id="dropzone" onclick="document.getElementById('file').click()">
                        <div class="file-dropzone-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="12" y2="12"/><line x1="15" y1="15" x2="12" y2="12"/></svg>
                        </div>
                        <div>
                            <p style="margin: 0; font-size: 13.5px; font-weight: 600; color: #0f172a;" id="file-label">Click to browse or drag and drop your CSV file here</p>
                            <p style="margin: 4px 0 0; font-size: 12px; color: #64748b;" id="file-sub">Supported formats: .csv (UTF-8 encoded) · Up to 1,000 rows</p>
                        </div>
                        <input id="file" type="file" name="file" accept=".csv,text/csv,text/plain" required style="display: none;">
                    </div>
                    @error('file') <small class="errors" style="color: #ef4444; font-size: 12px; margin-top: 6px; display: block;">{{ $message }}</small> @enderror
                </div>

                <div style="display: flex; align-items: center; justify-content: space-between; padding-top: 14px; border-top: 1px solid #f1f5f9;">
                    <div style="display: flex; gap: 8px;">
                        <span class="pill-tag">UTF-8 Encoded</span>
                        <span class="pill-tag">Auto-header detection</span>
                    </div>
                    <button type="submit" class="button" style="background: #2563eb; color: #ffffff; padding: 9px 22px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        <span>Upload and map columns</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Right: Field Reference & Guidelines Card -->
        <div style="display: flex; flex-direction: column; gap: 16px;">
            <div class="card" style="background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 22px; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                <h2 style="font-size: 15px; font-weight: 700; color: #0f172a; margin: 0 0 6px 0;">Product fields</h2>
                <p style="font-size: 12.5px; color: #64748b; margin: 0 0 14px 0;">New products require a SAP material number, name, category ID, and unit. SKU defaults to the SAP material number. Unmapped master fields keep existing values; stock fields are not importable.</p>
                
                <div class="field-req-grid">
                    <div class="field-req-item">
                        <strong>SAP Material # <span style="color:#ef4444;">*</span></strong>
                        <span>Unique primary identifier</span>
                    </div>
                    <div class="field-req-item">
                        <strong>Product Name <span style="color:#ef4444;">*</span></strong>
                        <span>Official catalog designation</span>
                    </div>
                    <div class="field-req-item">
                        <strong>Category ID <span style="color:#ef4444;">*</span></strong>
                        <span>Valid active category link</span>
                    </div>
                    <div class="field-req-item">
                        <strong>Unit of Measure <span style="color:#ef4444;">*</span></strong>
                        <span>e.g. PCS, KGS, CRT, LTR</span>
                    </div>
                </div>

                <div style="margin-top: 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px; font-size: 12px; color: #475569;">
                    <strong style="color: #0f172a; display: block; margin-bottom: 6px;">Available active category IDs:</strong>
                    <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                        @forelse ($categories as $cat)
                            <span class="pill-code" style="font-size: 11px;">{{ $cat->name }}: #{{ $cat->id }}</span>
                        @empty
                            <span style="color: #94a3b8;">None available</span>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Ledger Safety Notice -->
            <div class="card" style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 12px; padding: 16px;">
                <div style="display: flex; gap: 10px; align-items: flex-start;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" style="flex-shrink:0; margin-top: 2px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                    <div>
                        <strong style="font-size: 12.5px; color: #1e40af; display: block; margin-bottom: 2px;">Warehouse Balance Protection</strong>
                        <p style="font-size: 11.5px; color: #1e3a8a; margin: 0; line-height: 1.4;">WIMS strictly prohibits modifying physical inventory levels through file imports. All stock movements must originate from verified Goods Receipts, Dispatches, or Approved Cycle Counts.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const dropzone = document.getElementById('dropzone');
        const fileInput = document.getElementById('file');
        const fileLabel = document.getElementById('file-label');
        const fileSub = document.getElementById('file-sub');

        if (fileInput && dropzone) {
            fileInput.addEventListener('change', () => {
                if (fileInput.files && fileInput.files.length > 0) {
                    const file = fileInput.files[0];
                    const sizeMb = (file.size / (1024 * 1024)).toFixed(2);
                    fileLabel.textContent = `Selected: ${file.name}`;
                    fileSub.textContent = `File size: ${sizeMb} MB · Ready to upload`;
                    dropzone.style.borderColor = '#10b981';
                    dropzone.style.background = '#f0fdf4';
                }
            });

            ['dragenter', 'dragover'].forEach(eventName => {
                dropzone.addEventListener(eventName, (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzone.classList.add('dragover');
                }, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropzone.addEventListener(eventName, (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzone.classList.remove('dragover');
                }, false);
            });

            dropzone.addEventListener('drop', (e) => {
                const dt = e.dataTransfer;
                if (dt && dt.files && dt.files.length > 0) {
                    fileInput.files = dt.files;
                    const event = new Event('change');
                    fileInput.dispatchEvent(event);
                }
            });
        }
    </script>
@endpush
