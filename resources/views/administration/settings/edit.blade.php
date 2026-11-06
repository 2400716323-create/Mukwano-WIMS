@extends('catalog.layout')

@section('title', 'System settings')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="separator">/</span>
    <span class="current">System Settings</span>
@endsection

@section('content')
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

    <div class="card" style="max-width: 760px; margin: 0 auto; background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
        <form method="post" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            
            <div class="field" style="margin-bottom: 20px;">
                <label for="company_name" style="display: block; font-size: 12.5px; font-weight: 600; color: #334155; margin-bottom: 6px;">Company name <span style="color: #ef4444;">*</span></label>
                <input id="company_name" name="company_name" value="{{ old('company_name', $settings->company_name ?: config('app.name')) }}" required maxlength="255"
                       style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; font-size: 13.5px;">
                @error('company_name') <small class="errors" style="color: #ef4444; font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</small> @enderror
            </div>

            <!-- Corporate Logo Branding Section -->
            <div class="field" style="margin-bottom: 24px;">
                <label for="logo" style="display: block; font-size: 12.5px; font-weight: 600; color: #334155; margin-bottom: 6px;">Company logo (PNG, JPEG, or WebP; up to 2 MB)</label>
                
                <div style="display: flex; gap: 16px; align-items: center; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px;">
                    <div style="width: 64px; height: 64px; border-radius: 8px; background: #ffffff; border: 1px solid #cbd5e1; display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0; padding: 4px;">
                        @if ($settings->logo_path)
                            <img id="logo-preview-img" class="thumb" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($settings->logo_path) }}" alt="Current company logo" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                        @else
                            <img id="logo-preview-img" class="thumb" src="" alt="Logo preview" style="display: none; max-width: 100%; max-height: 100%; object-fit: contain;">
                            <div id="logo-placeholder" style="color: #94a3b8; display: flex; flex-direction: column; align-items: center;">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                            </div>
                        @endif
                    </div>

                    <div style="flex: 1; min-width: 0;">
                        <input id="logo" name="logo" type="file" accept="image/png,image/jpeg,image/webp"
                               style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 10px; font-size: 12.5px; background: #ffffff;">
                        <p style="margin: 6px 0 0; font-size: 11.5px; color: #64748b;">Displays in the sidebar header, navigation, and login screen.</p>
                        
                        @if ($settings->logo_path)
                            <div style="margin-top: 8px;">
                                <label style="font-size: 12px; color: #dc2626; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; margin: 0;">
                                    <input type="checkbox" name="remove_logo" id="remove_logo" value="1">
                                    <span>Remove current logo</span>
                                </label>
                            </div>
                        @endif
                    </div>
                </div>
                @error('logo') <small class="errors" style="color: #ef4444; font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</small> @enderror
            </div>

            <div style="border-top: 1px solid #f1f5f9; padding-top: 20px; margin-bottom: 20px;">
                <h3 style="font-size: 14px; font-weight: 700; color: #0f172a; margin: 0 0 14px 0;">Cycle Count & Expiry Parameters</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="field">
                        <label for="variance_threshold_percent" style="display: block; font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 4px;">Cycle-count variance escalation (%)</label>
                        <input id="variance_threshold_percent" name="variance_threshold_percent" type="number" min="0" max="1000" step="0.001" value="{{ old('variance_threshold_percent', $settings->variance_threshold_percent) }}" required
                               style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 7px 10px; font-size: 13px;">
                    </div>
                    <div class="field">
                        <label for="accuracy_tolerance_percent" style="display: block; font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 4px;">Cycle-count accuracy tolerance (%)</label>
                        <input id="accuracy_tolerance_percent" name="accuracy_tolerance_percent" type="number" min="0" max="100" step="0.001" value="{{ old('accuracy_tolerance_percent', $settings->accuracy_tolerance_percent) }}" required
                               style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 7px 10px; font-size: 13px;">
                    </div>
                    <div class="field">
                        <label for="expiry_soon_days" style="display: block; font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 4px;">Expiry alert window (soon, days)</label>
                        <input id="expiry_soon_days" name="expiry_soon_days" type="number" min="1" max="365" value="{{ old('expiry_soon_days', $expiryWindows['soon']) }}" required
                               style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 7px 10px; font-size: 13px;">
                    </div>
                    <div class="field">
                        <label for="expiry_warning_days" style="display: block; font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 4px;">Expiry alert window (warning, days)</label>
                        <input id="expiry_warning_days" name="expiry_warning_days" type="number" min="2" max="365" value="{{ old('expiry_warning_days', $expiryWindows['warning']) }}" required
                               style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 7px 10px; font-size: 13px;">
                    </div>
                    <div class="field" style="grid-column: span 2;">
                        <label for="expiry_notice_days" style="display: block; font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 4px;">Expiry alert window (notice, days)</label>
                        <input id="expiry_notice_days" name="expiry_notice_days" type="number" min="3" max="365" value="{{ old('expiry_notice_days', $expiryWindows['notice']) }}" required
                               style="width: 100%; max-width: calc(50% - 8px); border: 1px solid #cbd5e1; border-radius: 6px; padding: 7px 10px; font-size: 13px;">
                    </div>
                </div>
                <small style="display: block; color: #64748b; font-size: 11.5px; margin-top: 10px;">Expiry windows must be increasing. Account deactivation occurs immediately on the next request.</small>
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end; padding-top: 18px; border-top: 1px solid #f1f5f9;">
                <button type="submit" class="button" style="background: #2563eb; color: #ffffff; padding: 8px 20px; font-weight: 600;">
                    Save system settings
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
                    };
                    reader.readAsDataURL(file);
                }
            });
        }

        if (removeLogoCheckbox) {
            removeLogoCheckbox.addEventListener('change', () => {
                if (removeLogoCheckbox.checked) {
                    if (logoInput) logoInput.value = '';
                    logoPreviewImg.style.display = 'none';
                    if (logoPlaceholder) logoPlaceholder.style.display = 'flex';
                }
            });
        }
    </script>
@endpush
