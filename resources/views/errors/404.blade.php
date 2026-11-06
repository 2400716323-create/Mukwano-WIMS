@extends('catalog.layout')

@section('title', '404 Not Found')

@section('content')
    <div style="min-height: 50vh; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 40px 20px;">
        <div style="width: 64px; height: 64px; border-radius: 50%; background: var(--surface-muted); color: var(--navy-700); display: flex; align-items: center; justify-content: center; margin-bottom: 20px;">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
        </div>
        <h1 style="font-size: 28px; margin-bottom: 8px;">Page Not Found</h1>
        <p class="muted" style="max-width: 480px; margin-bottom: 24px; font-size: 15px;">
            The page, batch, product, or location record you requested cannot be found or may have been moved.
        </p>
        <div style="display: flex; gap: 12px; justify-content: center;">
            <a href="{{ route('dashboard') }}" class="button">Return to Dashboard</a>
            <a href="{{ route('products.index') }}" class="button secondary">Browse Products</a>
        </div>
    </div>
@endsection

