
@extends('catalog.layout')

@section('title', 'Create Put-away Task')

@section('breadcrumbs')
    <span class="breadcrumbs-separator">/</span>
    <a href="{{ route('put-away.index') }}">Put-away Tasks</a>
    <span class="breadcrumbs-separator">/</span>
    <span style="color:#0f172a;font-weight:600;">Create Task</span>
@endsection

@section('content')

    {{-- Page header --}}
    <div class="page-header-card">
        <div class="page-title-wrap">
            <h1>Create Put-away Task</h1>
            <p>Assign a stock relocation job to an active storekeeper.</p>
        </div>

        <div class="page-actions-wrap">
            <a class="button secondary" href="{{ route('put-away.index') }}">
                Back to Put-away
            </a>
        </div>
    </div>

    {{-- Validation errors --}}
    @if ($errors->any())
        <div class="card" style="padding:14px 18px;margin-bottom:16px;border-left:4px solid #dc2626;color:#991b1b;">
            <strong>Please correct the following:</strong>
            <ul style="margin:6px 0 0;padding-left:20px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card" style="max-width:850px;padding:22px;">

        {{-- Stock item information --}}
        <h2 style="font-size:15px;color:#0f172a;margin:0 0 15px;">
            Stock Item Details
        </h2>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid #e2e8f0;">

            <div>
                <small style="color:#64748b;">Product</small>
                <div style="font-weight:700;">
                    {{ $stockBalance->batch?->product?->name ?? 'Product unavailable' }}
                </div>
                <small>{{ $stockBalance->batch?->product?->sku ?? 'N/A' }}</small>
            </div>

            <div>
                <small style="color:#64748b;">Batch Number</small>
                <div style="font-weight:700;">
                    {{ $stockBalance->batch?->batch_number ?? 'No batch' }}
                </div>
            </div>

            {{-- Source location --}}
            <div>
                <small style="color:#64748b;">Current Source Location</small>
                <div style="font-weight:700;">
                    {{ $stockBalance->location?->name ?? 'Location unavailable' }}
                </div>

                <small style="color:#64748b;">
                    {{ $stockBalance->location?->code ?? 'No location code' }}
                </small>

                <span style="display:inline-block;margin-top:5px;padding:3px 8px;border-radius:5px;background:#eff6ff;color:#1d4ed8;font-size:11px;font-weight:600;">
                    {{ ucfirst($stockBalance->location?->type ?? 'Unknown') }}
                </span>
            </div>

            <div>
                <small style="color:#64748b;">Available Quantity</small>
                <div style="font-weight:700;color:#15803d;">
                    {{ number_format((float)$stockBalance->quantity, 3) }}
                    {{ $stockBalance->batch?->product?->unit ?? 'units' }}
                </div>
            </div>

        </div>

        {{-- Task assignment form --}}
        <h2 style="font-size:15px;color:#0f172a;margin:0 0 15px;">
            Task Assignment Details
        </h2>

        <form method="POST" action="{{ route('put-away.tasks.store', $stockBalance) }}">
            @csrf

            {{-- Assign storekeeper --}}
            <div class="field">
                <label for="assigned_to">Assign to Storekeeper *</label>

                <select id="assigned_to" name="assigned_to" required style="width:100%;">
                    <option value="">Choose storekeeper</option>

                    @foreach($storekeepers as $storekeeper)
                        <option value="{{ $storekeeper->id }}" @selected(old('assigned_to') == $storekeeper->id)>
                            {{ $storekeeper->name }} — {{ $storekeeper->email }}
                        </option>
                    @endforeach
                </select>

                @if($storekeepers->isEmpty())
                    <small style="color:#b91c1c;display:block;margin-top:5px;">
                        No active storekeepers are available. Create or activate a storekeeper account first.
                    </small>
                @endif
            </div>

            {{-- Destination storage bin --}}
            <div class="field">
                <label for="destination_location_id">
                    Destination Storage Bin *
                </label>

                <small style="display:block;color:#64748b;margin-bottom:6px;">
                    Select an active operational storage bin where the stock will be placed.
                </small>

                <select id="destination_location_id" name="destination_location_id" required style="width:100%;">
                    <option value="">Choose destination storage bin</option>

                    @foreach($destinationBins as $bin)
                        <option value="{{ $bin->id }}" @selected(old('destination_location_id') == $bin->id)>
                            {{ $bin->code ?? $bin->name }} — {{ $bin->name }}
                            ({{ $bin->parent?->name ?? 'Rack' }} / {{ $bin->parent?->parent?->name ?? 'Warehouse' }})
                        </option>
                    @endforeach
                </select>

                @if($destinationBins->isEmpty())
                    <small style="color:#b91c1c;display:block;margin-top:5px;">
                        No operational storage bins are available. Check your warehouse, rack and bin setup.
                    </small>
                @endif
            </div>

            {{-- Quantity --}}
            <div class="field">
                <label for="quantity">Quantity to Move *</label>

                <input
                    id="quantity"
                    name="quantity"
                    type="number"
                    step="0.001"
                    min="0.001"
                    max="{{ $stockBalance->quantity }}"
                    value="{{ old('quantity', $stockBalance->quantity) }}"
                    required
                    style="width:100%;"
                >

                <small style="color:#64748b;display:block;margin-top:4px;">
                    Maximum available:
                    {{ number_format((float)$stockBalance->quantity, 3) }}
                    {{ $stockBalance->batch?->product?->unit ?? 'units' }}
                </small>
            </div>

            {{-- Instructions --}}
            <div class="field">
                <label for="notes">Instructions / Notes</label>

                <textarea
                    id="notes"
                    name="notes"
                    rows="3"
                    maxlength="2000"
                    placeholder="Enter any special instructions for the storekeeper..."
                    style="width:100%;"
                >{{ old('notes') }}</textarea>
            </div>

            {{-- Actions --}}
            <div style="display:flex;gap:10px;align-items:center;margin-top:18px;flex-wrap:wrap;">
                <button
                    class="button"
                    type="submit"
                    @disabled($storekeepers->isEmpty() || $destinationBins->isEmpty())
                >
                    Create and Assign Task
                </button>

                <a class="button secondary" href="{{ route('put-away.index') }}">
                    Cancel
                </a>
            </div>

        </form>
    </div>
@endsection
