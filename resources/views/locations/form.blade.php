@extends('catalog.layout')

@section('title', $location->exists ? 'Edit location' : 'Add location')

@section('breadcrumbs')
    <a href="{{ route('locations.index') }}">Warehouse Locations</a>
    @if ($parent)
        <span class="sep">/</span>
        <a href="{{ route('locations.show', $parent) }}">{{ $parent->name }}</a>
    @endif
    <span class="sep">/</span>
    <span class="current">{{ $location->exists ? 'Edit '.$location->name : 'New '.ucfirst($location->type) }}</span>
@endsection

@section('content')
    <!-- Unified Page Header -->
    <div class="page-header-card">
        <div class="page-title-wrap">
            <nav class="breadcrumb-trail" aria-label="Breadcrumb">
                <a href="{{ route('dashboard') }}">Dashboard</a>
                <span class="separator">/</span>
                <a href="{{ route('locations.index') }}">Locations</a>
                @if ($parent)
                    <span class="separator">/</span>
                    <a href="{{ route('locations.show', $parent) }}">{{ $parent->name }}</a>
                @endif
                <span class="separator">/</span>
                <span class="current">{{ $location->exists ? 'Edit '.$location->name : 'New '.ucfirst($location->type) }}</span>
            </nav>
            <h1>{{ $location->exists ? 'Edit '.$location->type : 'Add '.$location->type }}</h1>
            <p>Define warehouse hierarchy, aisles, racks, and storage bins with unique barcode identifiers.</p>
        </div>
        <div class="page-actions-wrap">
            <a class="button secondary" href="{{ $location->exists ? route('locations.show', $location) : route('locations.index') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                <span>Cancel</span>
            </a>
        </div>
    </div>

    <!-- Location Form Card -->
    <div class="card" style="max-width: 680px; margin: 0 auto; background: #ffffff; border-radius: 10px; border: 1px solid #e2e8f0; padding: 24px;">
        <form method="POST" action="{{ $location->exists ? route('locations.update', $location) : route('locations.store') }}">
            @csrf
            @if ($location->exists)
                @method('PUT')
            @else
                <input type="hidden" name="type" value="{{ $location->type }}">
                @if ($parent)
                    <input type="hidden" name="parent_id" value="{{ $parent->id }}">
                @endif
            @endif

            @if ($parent)
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 16px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                    <div style="width: 32px; height: 32px; border-radius: 6px; background: rgba(37, 99, 235, 0.1); color: #2563eb; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                    </div>
                    <div>
                        <span style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: #64748b; letter-spacing: 0.04em;">Parent Location Node</span>
                        <div style="font-weight: 700; color: #0f172a; font-size: 13.5px;">{{ $parent->name }} <span class="pill-code" style="margin-left: 6px;">{{ ucfirst($parent->type) }}</span></div>
                    </div>
                </div>
            @endif

            <div class="field" style="margin-bottom: 18px;">
                <label for="name" style="display: block; font-size: 12.5px; font-weight: 600; color: #334155; margin-bottom: 6px;">
                    {{ ucfirst($location->type) }} name <span style="color: #ef4444;">*</span>
                </label>
                <input id="name" name="name" required maxlength="255" value="{{ old('name', $location->name) }}"
                       style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; font-size: 13.5px;"
                       placeholder="e.g. Zone A, Rack 03, Shelf 2">
                @error('name') <small class="errors" style="color: #ef4444; font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</small> @enderror
            </div>

            @if ($location->type === 'bin')
                <div class="field" style="margin-bottom: 22px;">
                    <label for="code" style="display: block; font-size: 12.5px; font-weight: 600; color: #334155; margin-bottom: 6px;">
                        Unique bin code / barcode <span style="color: #ef4444;">*</span>
                    </label>
                    <input id="code" name="code" required maxlength="80" pattern="[A-Za-z0-9][A-Za-z0-9-]*"
                           value="{{ old('code', $location->code) }}" placeholder="e.g. A03-B02"
                           style="width: 100%; font-family: monospace; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; font-size: 13.5px;">
                    <small style="display: block; color: #64748b; font-size: 11.5px; margin-top: 4px;">Used for QR scanner labels and automated bin routing.</small>
                    @error('code') <small class="errors" style="color: #ef4444; font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</small> @enderror
                </div>
            @endif

            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 24px; padding-top: 18px; border-top: 1px solid #f1f5f9;">
                <a class="button secondary" href="{{ $location->exists ? route('locations.show', $location) : route('locations.index') }}" style="padding: 8px 16px;">Cancel</a>
                <button type="submit" class="button" style="background: #2563eb; color: #ffffff; padding: 8px 20px; font-weight: 600;">
                    {{ $location->exists ? 'Save Location Changes' : 'Create Location' }}
                </button>
            </div>
        </form>
    </div>
@endsection
