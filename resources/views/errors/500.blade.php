@extends('catalog.layout')

@section('title', '500 Server Error')

@section('content')
    <div style="min-height: 50vh; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 40px 20px;">
        <div style="width: 64px; height: 64px; border-radius: 50%; background: var(--danger-bg); color: var(--danger); display: flex; align-items: center; justify-content: center; margin-bottom: 20px;">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="7.86 2 16.14 2 22 7.86 22 16.14 16.14 22 7.86 22 2 16.14 2 7.86 7.86 2"></polygon><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
        </div>
        <h1 style="font-size: 28px; margin-bottom: 8px;">System Error Encountered</h1>
        <p class="muted" style="max-width: 480px; margin-bottom: 24px; font-size: 15px;">
            An unexpected error occurred while processing your request. The incident details have been recorded in the audit logs.
        </p>
        <div style="display: flex; gap: 12px; justify-content: center;">
            <a href="{{ route('dashboard') }}" class="button">Return to Dashboard</a>
            <button type="button" class="button secondary" onclick="window.location.reload();">Retry Request</button>
        </div>
    </div>
@endsection

