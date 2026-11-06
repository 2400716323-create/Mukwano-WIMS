@extends('catalog.layout')

@section('title', 'Sign in')

@section('content')
    <div style="max-width: 440px; margin: 40px auto;">
        <div class="card" style="padding: 32px;">
            <div style="text-align: center; margin-bottom: 24px;">
                @if ($systemSettings?->logo_path)
                    <div style="margin-bottom: 16px;">
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($systemSettings->logo_path) }}" alt="{{ $systemSettings->company_name ?? 'Logo' }}" style="max-height: 52px; max-width: 200px; object-fit: contain;">
                    </div>
                @else
                    <div style="width: 48px; height: 48px; border-radius: 12px; background: var(--navy-800); color: white; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#38bdf8" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                    </div>
                @endif
                <h1 style="font-size: 22px; font-weight: 700; margin: 0 0 6px;">Sign In to {{ $systemSettings?->company_name ?: 'WIMS' }}</h1>
                <p class="muted" style="margin: 0; font-size: 13px;">Use your active Mukwano warehouse credentials.</p>
            </div>

            <form method="POST" action="{{ route('login.store') }}">
                @csrf
                <div class="field">
                    <label for="email">Email Address</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus placeholder="name@mukwano.com">
                </div>
                <div class="field">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <label for="password" style="margin-bottom: 0;">Password</label>
                        <a href="{{ route('password.request') }}" style="font-size: 12px;">Forgot password?</a>
                    </div>
                    <div class="password-field-wrap" style="position: relative; display: flex; align-items: center;">
                        <input id="password" name="password" type="password" autocomplete="current-password" required style="padding-right: 42px; width: 100%;">
                        <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('password', this)" aria-label="Show password" title="Show password" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: transparent; border: 0; padding: 6px; cursor: pointer; color: #64748b; display: inline-flex; align-items: center; justify-content: center; border-radius: 4px;">
                            <svg class="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <svg class="eye-off-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                <line x1="1" y1="1" x2="23" y2="23"></line>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="field">
                    <label for="remember" style="font-weight: normal; display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input id="remember" name="remember" type="checkbox" value="1">
                        <span>Keep me signed in</span>
                    </label>
                </div>
                <div style="margin-top: 24px;">
                    <button type="submit" class="button" style="width: 100%; padding: 11px;">Sign in</button>
                </div>
            </form>
        </div>
    </div>
@endsection
