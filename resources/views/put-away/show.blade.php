
@extends('catalog.layout')

@section('title', 'Confirm Put-away — Mukwano WIMS')

@section('breadcrumbs')
    <span class="breadcrumbs-separator">/</span>
    <a href="{{ route('put-away.index') }}">Put-away Tasks</a>
    <span class="breadcrumbs-separator">/</span>
    <span style="color: #0f172a; font-weight: 600;">Assign Destination Bin</span>
@endsection

@section('content')
    {{-- Breadcrumb navigation --}}
    <nav style="display:flex;align-items:center;gap:8px;font-size:12.5px;color:#64748b;margin-bottom:14px;" aria-label="Breadcrumb">
        <a href="{{ route('dashboard') }}" style="color:#64748b;text-decoration:none;display:flex;align-items:center;gap:4px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
            <span>Dashboard</span>
        </a>
        <span style="color:#cbd5e1;">/</span>
        <a href="{{ route('put-away.index') }}" style="color:#64748b;text-decoration:none;">Put-away Tasks</a>
        <span style="color:#cbd5e1;">/</span>
        <span style="color:#0f172a;font-weight:600;">Assign Destination Bin</span>
    </nav>

    {{-- Page header --}}
    <div class="page-header-card">
        <div class="page-title-wrap">
            <h1>Assign Destination Bin</h1>
            <p>Move stock from its current source location, such as Receiving or Staging, into an operational storage bin.</p>
        </div>

        <div class="page-actions-wrap">
            <a class="button secondary" href="{{ route('put-away.index') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                <span>Back to Tasks</span>
            </a>
        </div>
    </div>

    {{-- Validation errors --}}
    @if ($errors->any())
        <div class="card" style="border-left:4px solid #ef4444;background:#fef2f2;padding:14px 18px;margin-bottom:16px;">
            <h3 style="color:#991b1b;font-size:13.5px;margin:0 0 6px;">Please review the following errors:</h3>
            <ul style="margin:0;padding-left:20px;color:#b91c1c;font-size:12.5px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div style="display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1.2fr);gap:16px;align-items:start;max-width:1040px;">

        {{-- Stock item summary --}}
        <div class="card" style="padding:22px;">
            <h2 style="font-size:14px;font-weight:700;color:#0f172a;margin:0 0 14px;border-bottom:1px solid #f1f5f9;padding-bottom:8px;">
                Stock Item Details
            </h2>

            <div class="detail-grid" style="grid-template-columns:1fr;">

                <div>
                    <span style="font-size:11px;text-transform:uppercase;color:#64748b;font-weight:600;">Product</span>
                    <p style="margin:3px 0 0;font-weight:700;color:#0f172a;font-size:14px;">
                        {{ $stockBalance->batch?->product?->name ?? 'N/A' }}
                    </p>
                    <span class="pill-code" style="margin-top:3px;">
                        {{ $stockBalance->batch?->product?->sku ?? 'N/A' }}
                    </span>
                </div>

                <div>
                    <span style="font-size:11px;text-transform:uppercase;color:#64748b;font-weight:600;">Batch Number</span>
                    <p style="margin:3px 0 0;font-family:monospace;font-weight:700;color:#2563eb;">
                        {{ $stockBalance->batch?->batch_number ?? 'No Batch' }}
                    </p>

                    @if($stockBalance->batch?->expires_at)
                        <small style="color:#64748b;font-size:11.5px;display:block;margin-top:2px;">
                            Expires: {{ $stockBalance->batch->expires_at->format('d M Y') }}
                            ({{ $stockBalance->batch->expiry_status }})
                        </small>
                    @endif
                </div>

                {{-- Current source location --}}
                <div>
                    <span style="font-size:11px;text-transform:uppercase;color:#64748b;font-weight:600;">
                        Current Source Location
                    </span>

                    <p style="margin:3px 0 0;font-weight:600;color:#334155;">
                        {{ $stockBalance->location?->name ?? 'Current Location' }}
                        @if($stockBalance->location?->code)
                            ({{ $stockBalance->location->code }})
                        @endif
                    </p>

                    <span style="display:inline-block;margin-top:5px;padding:3px 8px;border-radius:5px;background:#eff6ff;color:#1d4ed8;font-size:11px;font-weight:600;">
                        {{ ucfirst($stockBalance->location?->type ?? 'Unknown') }}
                    </span>

                    @if($stockBalance->location?->parent)
                        <small style="display:block;margin-top:5px;color:#64748b;font-size:11.5px;">
                            Parent location: {{ $stockBalance->location->parent->name }}
                        </small>
                    @endif
                </div>

                {{-- Available quantity --}}
                <div>
                    <span style="font-size:11px;text-transform:uppercase;color:#64748b;font-weight:600;">
                        Available Quantity
                    </span>

                    <p style="margin:3px 0 0;font-weight:800;font-size:18px;color:#16a34a;">
                        {{ rtrim(rtrim(number_format((float)$stockBalance->quantity, 3), '0'), '.') }}
                        <span style="font-size:12px;font-weight:normal;color:#64748b;">
                            {{ $stockBalance->batch?->product?->unit ?? 'units' }}
                        </span>
                    </p>
                </div>

            </div>
        </div>

        {{-- Put-away assignment form --}}
        <div class="card" style="padding:22px;">
            <h2 style="font-size:14px;font-weight:700;color:#0f172a;margin:0 0 14px;border-bottom:1px solid #f1f5f9;padding-bottom:8px;">
                Relocation Instructions
            </h2>

            <form method="POST" action="{{ route('stock-balances.put-away', $stockBalance) }}">
                @csrf

                <input type="hidden" name="idempotency_key" value="{{ (string)\Illuminate\Support\Str::uuid() }}">

                {{-- Destination bin --}}
                <div class="field">
                    <label for="destination_location_id">
                        Destination Storage Bin <span style="color:#ef4444;">*</span>
                    </label>

                    <p style="margin:0 0 6px;font-size:12px;color:#64748b;">
                        Select an active operational storage bin inside the warehouse rack structure.
                    </p>

                    <select id="destination_location_id" name="destination_location_id" required style="width:100%;">
                        <option value="" disabled {{ old('destination_location_id') ? '' : 'selected' }}>
                            -- Select an active storage bin --
                        </option>

                        @foreach($destinationBins as $bin)
                            <option value="{{ $bin->id }}" {{ old('destination_location_id') == $bin->id ? 'selected' : '' }}>
                                {{ $bin->code ?? $bin->name }} — {{ $bin->name }}

                                @if($bin->parent)
                                    ({{ $bin->parent->name }} / {{ $bin->parent->parent?->name ?? 'Warehouse' }})
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Quantity to move --}}
                <div class="field">
                    <label for="quantity">
                        Quantity to Move <span style="color:#ef4444;">*</span>
                    </label>

                    <input
                        type="number"
                        id="quantity"
                        name="quantity"
                        step="0.001"
                        min="0.001"
                        max="{{ $stockBalance->quantity }}"
                        value="{{ old('quantity', rtrim(rtrim(number_format((float)$stockBalance->quantity, 3), '0'), '.')) }}"
                        required
                        style="width:100%;font-size:14px;"
                    >

                    <small style="color:#64748b;font-size:11.5px;margin-top:3px;display:block;">
                        Maximum moveable:
                        {{ rtrim(rtrim(number_format((float)$stockBalance->quantity, 3), '0'), '.') }}
                        {{ $stockBalance->batch?->product?->unit ?? 'units' }}
                    </small>
                </div>

                {{-- Form actions --}}
                <div style="margin-top:18px;display:flex;align-items:center;gap:10px;">
                    <button type="submit" class="button" style="background:#2563eb;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        <span>Confirm Put-away</span>
                    </button>

                    <a class="button secondary" href="{{ route('put-away.index') }}">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
