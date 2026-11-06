@extends('catalog.layout')

@section('title', 'Forgot Password')

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
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                    </div>
                @endif
                <h1 style="font-size: 22px; font-weight: 700; margin: 0 0 6px;">Reset Password</h1>
                <p class="muted" style="margin: 0; font-size: 13px;">
                    Enter your registered email address and we will send you a secure password reset link.
                </p>
            </div>

            <form method="POST" action="{{ route('password.email') }}">
                @csrf

                <div class="field">
                    <label for="email">Work Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="name@mukwano.com">
                </div>

                <div style="margin-top: 24px; display: flex; flex-direction: column; gap: 12px;">
                    <button type="submit" class="button" style="width: 100%; padding: 11px;">
                        Email Password Reset Link
                    </button>
                    <a href="{{ route('login') }}" class="button secondary" style="width: 100%; text-align: center; padding: 11px;">
                        Back to Sign In
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection

