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
            <p>Update your personal account details, contact email, and password security credentials.</p>
        </div>
        <div class="page-actions-wrap">
            <a class="button secondary" href="{{ route('dashboard') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                <span>Dashboard</span>
            </a>
        </div>
    </div>

    <div style="max-width: 680px; margin: 0 auto; display: flex; flex-direction: column; gap: 20px;">
        <!-- Profile Details Card -->
        <div class="card" style="background: #ffffff; border-radius: 10px; border: 1px solid #e2e8f0; padding: 24px;">
            <h2 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0 0 16px 0;">Profile details</h2>
            <form method="post" action="{{ route('profile.update') }}">
                @csrf
                @method('PUT')
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
        <div class="card" style="background: #ffffff; border-radius: 10px; border: 1px solid #e2e8f0; padding: 24px;">
            <h2 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0 0 16px 0;">Change password</h2>
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
