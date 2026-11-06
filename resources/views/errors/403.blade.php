@extends('catalog.layout')

@section('title', '403 Forbidden')

@section('content')
    <div style="min-height: 50vh; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 40px 20px;">
        <div style="width: 64px; height: 64px; border-radius: 50%; background: var(--danger-bg); color: var(--danger); display: flex; align-items: center; justify-content: center; margin-bottom: 20px;">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
        </div>
        <h1 style="font-size: 28px; margin-bottom: 8px;">Access Restricted</h1>
        <p class="muted" style="max-width: 480px; margin-bottom: 24px; font-size: 15px;">
            {{ $exception->getMessage() ?: 'Your role does not permit access to this resource or action. Contact a system administrator to request permissions.' }}
        </p>
        <div style="display: flex; gap: 12px; justify-content: center;">
            <a href="{{ route('dashboard') }}" class="button">Return to Dashboard</a>
            @auth
                <a href="{{ route('profile.edit') }}" class="button secondary">View Account Profile</a>
            @endauth
        </div>
    </div>
@endsection

