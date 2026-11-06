@extends('catalog.layout')

@section('title', '419 Page Expired')

@section('content')
    <div style="min-height: 50vh; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 40px 20px;">
        <div style="width: 64px; height: 64px; border-radius: 50%; background: var(--warning-bg); color: var(--warning); display: flex; align-items: center; justify-content: center; margin-bottom: 20px;">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
        </div>
        <h1 style="font-size: 28px; margin-bottom: 8px;">Session Expired</h1>
        <p class="muted" style="max-width: 480px; margin-bottom: 24px; font-size: 15px;">
            Your security token or active session has expired due to inactivity. Please refresh the page and try submitting again.
        </p>
        <div style="display: flex; gap: 12px; justify-content: center;">
            <button type="button" class="button" onclick="window.location.reload();">Refresh Page</button>
            <a href="{{ route('login') }}" class="button secondary">Sign In Again</a>
        </div>
    </div>
@endsection

