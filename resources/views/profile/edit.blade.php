@extends('catalog.layout')

@section('title', 'My profile')

@section('breadcrumbs')
    <span class="current">My Profile</span>
@endsection

@section('content')
    <!-- Unified Page Header -->
    <div class="page-header-card">
        <div class="page-title-wrap">
            <nav class="breadcrumb-trail" aria-label="Breadcrumb">
                <a href="{{ route('dashboard') }}">Dashboard</a>
                <span class="separator">/</span>
                <span class="current">My Profile</span>
            </nav>
            <h1>My profile</h1>
            <p>Manage your account identity, profile photo, and password security credentials.</p>
        </div>
        <div class="page-actions-wrap">
            <a class="button secondary" href="{{ route('dashboard') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                <span>Dashboard</span>
            </a>
        </div>
    </div>

    <div style="max-width: 720px; margin: 0 auto; display: flex; flex-direction: column; gap: 20px;">
        <!-- Profile Identity & Details Card -->
        <div class="card" style="background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; padding-bottom: 14px; border-bottom: 1px solid #f1f5f9;">
                <div>
                    <h2 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0 0 4px 0;">Profile details</h2>
                    <p style="font-size: 12.5px; color: #64748b; margin: 0;">Update your personal avatar and verified contact information.</p>
                </div>
                <span class="pill-code" style="text-transform: capitalize; font-size: 11.5px;">{{ $user->role_label }}</span>
            </div>

            <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <!-- Profile Avatar Uploader Section -->
                <div style="display: flex; align-items: center; gap: 20px; padding: 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; margin-bottom: 22px;">
                    <div style="position: relative; width: 76px; height: 76px; flex-shrink: 0;">
                        <div id="avatar-preview-wrap" style="width: 76px; height: 76px; border-radius: 50%; overflow: hidden; background: #2563eb; color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 26px; font-weight: 700; border: 3px solid #ffffff; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                            @if ($user->avatar_url)
                                <img id="avatar-preview-img" src="{{ $user->avatar_url }}" alt="{{ $user->name }}" style="width: 100%; height: 100%; object-fit: cover;">
                            @else
                                <span id="avatar-initials">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                <img id="avatar-preview-img" src="" alt="Avatar preview" style="display: none; width: 100%; height: 100%; object-fit: cover;">
                            @endif
                        </div>
                    </div>

                    <div style="flex: 1; min-width: 0;">
                        <label for="avatar" style="display: block; font-size: 13px; font-weight: 600; color: #0f172a; margin-bottom: 4px;">Profile Photo</label>
                        <p style="font-size: 12px; color: #64748b; margin: 0 0 10px 0;">Upload a clear square portrait. JPG, PNG, or WebP up to 2 MB.</p>
                        
                        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                            <label class="button secondary" style="cursor: pointer; padding: 6px 14px; font-size: 12px; margin: 0; display: inline-flex; align-items: center; gap: 6px;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                <span>Choose Image</span>
                                <input id="avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp" style="display: none;">
                            </label>

                            @if ($user->avatar_path)
                                <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 12px; color: #dc2626; cursor: pointer; margin: 0;">
                                    <input type="checkbox" name="remove_avatar" id="remove_avatar" value="1">
                                    <span>Remove current photo</span>
                                </label>
                            @endif
                        </div>
                        @error('avatar') <small class="errors" style="color: #ef4444; font-size: 12px; margin-top: 6px; display: block;">{{ $message }}</small> @enderror
                    </div>
                </div>

                <div class="field" style="margin-bottom: 16px;">
                    <label for="name" style="display: block; font-size: 12.5px; font-weight: 600; color: #334155; margin-bottom: 6px;">Name <span style="color: #ef4444;">*</span></label>
                    <input id="name" name="name" value="{{ old('name', $user->name) }}" required maxlength="255"
                           style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; font-size: 13.5px;">
                    @error('name') <small class="errors" style="color: #ef4444; font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</small> @enderror
                </div>

                <div class="field" style="margin-bottom: 20px;">
                    <label for="email" style="display: block; font-size: 12.5px; font-weight: 600; color: #334155; margin-bottom: 6px;">Email <span style="color: #ef4444;">*</span></label>
                    <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required maxlength="255"
                           style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; font-size: 13.5px;">
                    @error('email') <small class="errors" style="color: #ef4444; font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</small> @enderror
                </div>

                <div style="display: flex; justify-content: flex-end;">
                    <button type="submit" class="button" style="background: #2563eb; color: #ffffff; padding: 8px 20px; font-weight: 600;">Save profile</button>
                </div>
            </form>
        </div>

        <!-- Change Password Card -->
        <div class="card" style="background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
            <div style="margin-bottom: 18px; padding-bottom: 14px; border-bottom: 1px solid #f1f5f9;">
                <h2 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0 0 4px 0;">Change password</h2>
                <p style="font-size: 12.5px; color: #64748b; margin: 0;">Ensure your account uses a strong, unique password of at least 12 characters.</p>
            </div>

            <form method="post" action="{{ route('profile.password') }}">
                @csrf
                @method('PUT')
                <div class="field" style="margin-bottom: 16px;">
                    <label for="current_password" style="display: block; font-size: 12.5px; font-weight: 600; color: #334155; margin-bottom: 6px;">Current password <span style="color: #ef4444;">*</span></label>
                    <div class="password-field-wrap" style="position: relative; display: flex; align-items: center;">
                        <input id="current_password" name="current_password" type="password" autocomplete="current-password" required
                               style="padding-right: 42px; width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; font-size: 13.5px;">
                        <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('current_password', this)" aria-label="Show password" title="Show password" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: transparent; border: 0; padding: 6px; cursor: pointer; color: #64748b; display: inline-flex; align-items: center; justify-content: center; border-radius: 4px;">
                            <svg class="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <svg class="eye-off-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                <line x1="1" y1="23" x2="23" y2="23"></line>
                            </svg>
                        </button>
                    </div>
                    @error('current_password') <small class="errors" style="color: #ef4444; font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</small> @enderror
                </div>

                <div class="field" style="margin-bottom: 16px;">
                    <label for="password" style="display: block; font-size: 12.5px; font-weight: 600; color: #334155; margin-bottom: 6px;">New password (at least 12 characters) <span style="color: #ef4444;">*</span></label>
                    <div class="password-field-wrap" style="position: relative; display: flex; align-items: center;">
                        <input id="password" name="password" type="password" autocomplete="new-password" minlength="12" required
                               style="padding-right: 42px; width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; font-size: 13.5px;">
                        <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('password', this)" aria-label="Show password" title="Show password" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: transparent; border: 0; padding: 6px; cursor: pointer; color: #64748b; display: inline-flex; align-items: center; justify-content: center; border-radius: 4px;">
                            <svg class="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <svg class="eye-off-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                <line x1="1" y1="23" x2="23" y2="23"></line>
                            </svg>
                        </button>
                    </div>
                    @error('password') <small class="errors" style="color: #ef4444; font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</small> @enderror
                </div>

                <div class="field" style="margin-bottom: 20px;">
                    <label for="password_confirmation" style="display: block; font-size: 12.5px; font-weight: 600; color: #334155; margin-bottom: 6px;">Confirm new password <span style="color: #ef4444;">*</span></label>
                    <div class="password-field-wrap" style="position: relative; display: flex; align-items: center;">
                        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required
                               style="padding-right: 42px; width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; font-size: 13.5px;">
                        <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('password_confirmation', this)" aria-label="Show password" title="Show password" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: transparent; border: 0; padding: 6px; cursor: pointer; color: #64748b; display: inline-flex; align-items: center; justify-content: center; border-radius: 4px;">
                            <svg class="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <svg class="eye-off-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                <line x1="1" y1="23" x2="23" y2="23"></line>
                            </svg>
                        </button>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end;">
                    <button type="submit" class="button" style="background: #2563eb; color: #ffffff; padding: 8px 20px; font-weight: 600;">Change password</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const avatarInput = document.getElementById('avatar');
        const avatarPreviewImg = document.getElementById('avatar-preview-img');
        const avatarInitials = document.getElementById('avatar-initials');
        const removeAvatarCheckbox = document.getElementById('remove_avatar');

        if (avatarInput && avatarPreviewImg) {
            avatarInput.addEventListener('change', () => {
                const file = avatarInput.files && avatarInput.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        avatarPreviewImg.src = e.target.result;
                        avatarPreviewImg.style.display = 'block';
                        if (avatarInitials) avatarInitials.style.display = 'none';
                        if (removeAvatarCheckbox) removeAvatarCheckbox.checked = false;
                    };
                    reader.readAsDataURL(file);
                }
            });
        }

        if (removeAvatarCheckbox) {
            removeAvatarCheckbox.addEventListener('change', () => {
                if (removeAvatarCheckbox.checked) {
                    if (avatarInput) avatarInput.value = '';
                    avatarPreviewImg.style.display = 'none';
                    if (avatarInitials) avatarInitials.style.display = 'block';
                }
            });
        }
    </script>
@endpush
