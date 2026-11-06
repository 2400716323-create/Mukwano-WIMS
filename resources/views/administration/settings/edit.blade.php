@extends('catalog.layout')

@section('title', 'System settings')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="separator">/</span>
    <span class="current">System Settings</span>
@endsection

@section('content')
    <style>
        .settings-card {
            max-width: 820px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            padding: 28px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }

        .settings-section {
            padding-bottom: 24px;
            margin-bottom: 24px;
            border-bottom: 1px solid #f1f5f9;
        }

        .settings-section:last-of-type {
            border-bottom: 0;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 4px;
        }

        .section-icon {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            background: rgba(37, 99, 235, 0.1);
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .section-header h2 {
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
        }

        .section-desc {
            font-size: 12.5px;
            color: #64748b;
            margin: 0 0 16px 38px;
        }

        .field-label {
            display: block;
            font-size: 12.5px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 6px;
        }

        .input-text {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 9px 13px;
            font-size: 13.5px;
            color: #0f172a;
            background: #ffffff;
            transition: all 0.15s ease;
            box-sizing: border-box;
        }

        .input-text:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        .input-affix-wrap {
            position: relative;
            display: flex;
            align-items: center;
            width: 100%;
        }

        .input-affix-wrap input {
            padding-right: 50px;
        }

        .input-affix {
            position: absolute;
            right: 12px;
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
            pointer-events: none;
        }

        .logo-box {
            display: flex;
            gap: 20px;
            align-items: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 18px 20px;
        }

        @media (max-width: 640px) {
            .logo-box {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        .logo-preview-frame {
            width: 76px;
            height: 76px;
            border-radius: 10px;
            background: #ffffff;
            border: 1.5px solid #cbd5e1;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex-shrink: 0;
            padding: 6px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            position: relative;
        }

        .logo-preview-frame img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .logo-upload-trigger {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 14px;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            color: #0f172a;
            font-size: 12.5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s ease;
            box-shadow: 0 1px 2px rgba(0,0,0,0.03);
            margin: 0;
        }

        .logo-upload-trigger:hover {
            background: #f1f5f9;
            border-color: #94a3b8;
        }

        .remove-logo-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: #dc2626;
            cursor: pointer;
            padding: 6px 12px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.15s ease;
            margin: 0;
        }

        .remove-logo-btn:hover {
            background: #fee2e2;
        }

        .file-selected-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 11.5px;
            color: #0369a1;
            background: #e0f2fe;
            border: 1px solid #bae6fd;
            padding: 3px 8px;
            border-radius: 6px;
            font-weight: 500;
        }
    </style>

    <!-- Unified Page Header -->
    <div class="page-header-card">
        <div class="page-title-wrap">
            <nav class="breadcrumb-trail" aria-label="Breadcrumb">
                <a href="{{ route('dashboard') }}">Dashboard</a>
                <span class="separator">/</span>
                <span class="current">System Settings</span>
            </nav>
            <h1>System settings</h1>
            <p>Configure warehouse organizational identity, corporate logo branding, and audit escalation parameters.</p>
        </div>
        <div class="page-actions-wrap">
            <a class="button secondary" href="{{ route('admin.audit.index') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                <span>Audit Logs</span>
            </a>
            <a class="button secondary" href="{{ route('admin.users.index') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                <span>Users</span>
            </a>
        </div>
    </div>

    <div class="settings-card">
        <form method="post" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <!-- Section 1: Company Profile & Identity -->
            <div class="settings-section">
                <div class="section-header">
                    <div class="section-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="2" width="16" height="20" rx="2" ry="2"/><line x1="9" y1="22" x2="9" y2="22.01"/><line x1="15" y1="22" x2="15" y2="22.01"/><line x1="9" y1="6" x2="9" y2="6.01"/><line x1="15" y1="6" x2="15" y2="6.01"/><line x1="9" y1="10" x2="9" y2="10.01"/><line x1="15" y1="10" x2="15" y2="10.01"/><line x1="9" y1="14" x2="9" y2="14.01"/><line x1="15" y1="14" x2="15" y2="14.01"/><line x1="9" y1="18" x2="9" y2="18.01"/><line x1="15" y1="18" x2="15" y2="18.01"/></svg>
                    </div>
                    <h2>Company Identity</h2>
                </div>
                <p class="section-desc">Official company title printed on warehouse delivery notes, picking slips, reports, and navigation.</p>

                <div class="field" style="margin-left: 38px;">
                    <label for="company_name" class="field-label">Company name <span style="color: #ef4444;">*</span></label>
                    <input id="company_name" name="company_name" class="input-text" value="{{ old('company_name', $settings->company_name ?: config('app.name')) }}" required maxlength="255" placeholder="e.g. Mukwano Industries Uganda Limited">
                    @error('company_name') <small class="errors" style="color: #ef4444; font-size: 12px; margin-top: 5px; display: block;">{{ $message }}</small> @enderror
                </div>
            </div>

            <!-- Section 2: Corporate Brand Logo -->
            <div class="settings-section">
                <div class="section-header">
                    <div class="section-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    </div>
                    <h2>Corporate Logo</h2>
                </div>
                <p class="section-desc">Official brand image rendered in the sidebar header, navigation topbar, and login screen.</p>

                <div style="margin-left: 38px;">
                    <div class="logo-box">
                        <div class="logo-preview-frame">
                            @if ($settings->logo_path)
                                <img id="logo-preview-img" class="thumb" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($settings->logo_path) }}" alt="Current company logo">
                            @else
                                <img id="logo-preview-img" class="thumb" src="" alt="Logo preview" style="display: none;">
                                <div id="logo-placeholder" style="color: #94a3b8; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                </div>
                            @endif
                        </div>

                        <div style="flex: 1; min-width: 0;">
                            <label class="field-label" style="margin-bottom: 4px;">Upload company logo</label>
                            <p style="margin: 0 0 12px 0; font-size: 12px; color: #64748b;">PNG, JPEG, or WebP up to 2 MB. Transparent backgrounds recommended.</p>

                            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                                <label for="logo" class="logo-upload-trigger">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                    <span>Choose New Logo</span>
                                    <input id="logo" name="logo" type="file" accept="image/png,image/jpeg,image/webp" style="display: none;">
                                </label>

                                <span id="selected-file-badge" class="file-selected-badge" style="display: none;">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                    <span id="selected-file-name">No file chosen</span>
                                </span>

                                @if ($settings->logo_path)
                                    <label class="remove-logo-btn" title="Remove corporate logo">
                                        <input type="checkbox" name="remove_logo" id="remove_logo" value="1">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                        <span>Remove current logo</span>
                                    </label>
                                @endif
                            </div>
                        </div>
                    </div>
                    @error('logo') <small class="errors" style="color: #ef4444; font-size: 12px; margin-top: 6px; display: block;">{{ $message }}</small> @enderror
                </div>
            </div>

            <!-- Section 3: Cycle Count & Variance Parameters -->
            <div class="settings-section">
                <div class="section-header">
                    <div class="section-icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                    </div>
                    <h2>Cycle Count & Variance Parameters</h2>
                </div>
                <p class="section-desc">Reconciliation variance thresholds and allowable count error tolerance percentages.</p>

                <div style="margin-left: 38px; display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="field">
                        <label for="variance_threshold_percent" class="field-label">Variance escalation threshold</label>
                        <div class="input-affix-wrap">
                            <input id="variance_threshold_percent" name="variance_threshold_percent" type="number" min="0" max="1000" step="0.001" value="{{ old('variance_threshold_percent', $settings->variance_threshold_percent) }}" required class="input-text">
                            <span class="input-affix">%</span>
                        </div>
                    </div>
                    <div class="field">
                        <label for="accuracy_tolerance_percent" class="field-label">Accuracy tolerance margin</label>
                        <div class="input-affix-wrap">
                            <input id="accuracy_tolerance_percent" name="accuracy_tolerance_percent" type="number" min="0" max="100" step="0.001" value="{{ old('accuracy_tolerance_percent', $settings->accuracy_tolerance_percent) }}" required class="input-text">
                            <span class="input-affix">%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 4: Shelf-Life & Expiry Horizons -->
            <div class="settings-section">
                <div class="section-header">
                    <div class="section-icon" style="background: rgba(124, 58, 237, 0.1); color: #7c3aed;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <h2>Batch Expiry Alert Horizons</h2>
                </div>
                <p class="section-desc">Calendar day thresholds before lot expiration for triggering progressive alert severity levels.</p>

                <div style="margin-left: 38px; display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px;">
                    <div class="field">
                        <label for="expiry_soon_days" class="field-label">Soon alert window</label>
                        <div class="input-affix-wrap">
                            <input id="expiry_soon_days" name="expiry_soon_days" type="number" min="1" max="365" value="{{ old('expiry_soon_days', $expiryWindows['soon']) }}" required class="input-text">
                            <span class="input-affix">days</span>
                        </div>
                    </div>
                    <div class="field">
                        <label for="expiry_warning_days" class="field-label">Warning alert window</label>
                        <div class="input-affix-wrap">
                            <input id="expiry_warning_days" name="expiry_warning_days" type="number" min="2" max="365" value="{{ old('expiry_warning_days', $expiryWindows['warning']) }}" required class="input-text">
                            <span class="input-affix">days</span>
                        </div>
                    </div>
                    <div class="field">
                        <label for="expiry_notice_days" class="field-label">Notice alert window</label>
                        <div class="input-affix-wrap">
                            <input id="expiry_notice_days" name="expiry_notice_days" type="number" min="3" max="365" value="{{ old('expiry_notice_days', $expiryWindows['notice']) }}" required class="input-text">
                            <span class="input-affix">days</span>
                        </div>
                    </div>
                </div>
                <p style="margin: 10px 0 0 38px; font-size: 11.5px; color: #64748b;">Alert horizon rule: Soon &lt; Warning &lt; Notice days. Batch lots approaching these thresholds appear automatically in the Alert Centre.</p>
            </div>

            <!-- Footer Save Actions -->
            <div style="display: flex; gap: 12px; justify-content: flex-end; padding-top: 22px; border-top: 1px solid #f1f5f9; margin-top: 6px;">
                <button type="submit" class="button" style="background: #2563eb; color: #ffffff; padding: 9px 22px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; border-radius: 6px; box-shadow: 0 1px 3px rgba(37,99,235,0.25);">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    <span>Save system settings</span>
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        const logoInput = document.getElementById('logo');
        const logoPreviewImg = document.getElementById('logo-preview-img');
        const logoPlaceholder = document.getElementById('logo-placeholder');
        const removeLogoCheckbox = document.getElementById('remove_logo');
        const selectedBadge = document.getElementById('selected-file-badge');
        const selectedName = document.getElementById('selected-file-name');

        if (logoInput && logoPreviewImg) {
            logoInput.addEventListener('change', () => {
                const file = logoInput.files && logoInput.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        logoPreviewImg.src = e.target.result;
                        logoPreviewImg.style.display = 'block';
                        if (logoPlaceholder) logoPlaceholder.style.display = 'none';
                        if (removeLogoCheckbox) removeLogoCheckbox.checked = false;
                        
                        if (selectedBadge && selectedName) {
                            const sizeKb = (file.size / 1024).toFixed(1);
                            selectedName.textContent = `${file.name} (${sizeKb} KB)`;
                            selectedBadge.style.display = 'inline-flex';
                        }
                    };
                    reader.readAsDataURL(file);
                } else {
                    if (selectedBadge) selectedBadge.style.display = 'none';
                }
            });
        }

        if (removeLogoCheckbox) {
            removeLogoCheckbox.addEventListener('change', () => {
                if (removeLogoCheckbox.checked) {
                    if (logoInput) logoInput.value = '';
                    if (selectedBadge) selectedBadge.style.display = 'none';
                    logoPreviewImg.style.display = 'none';
                    if (logoPlaceholder) logoPlaceholder.style.display = 'flex';
                }
            });
        }
    </script>
@endpush
