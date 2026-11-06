@extends('catalog.layout')

@section('title', $location->name)

@section('breadcrumbs')
    <span class="breadcrumbs-separator">/</span>
    <a href="{{ route('locations.index') }}">Locations</a>
    <span class="breadcrumbs-separator">/</span>
    <span style="color: #0f172a; font-weight: 600;">{{ $location->name }}</span>
@endsection

@section('content')
    <!-- Inline Breadcrumbs Navigation -->
    <nav style="display: flex; align-items: center; gap: 8px; font-size: 12.5px; color: #64748b; margin-bottom: 14px;" aria-label="Breadcrumb">
        <a href="{{ route('dashboard') }}" style="color: #64748b; text-decoration: none; display: flex; align-items: center; gap: 4px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
            <span>Dashboard</span>
        </a>
        <span style="color: #cbd5e1;">/</span>
        <a href="{{ route('locations.index') }}" style="color: #64748b; text-decoration: none;">Locations</a>
        <span style="color: #cbd5e1;">/</span>
        <span style="color: #0f172a; font-weight: 600;">{{ $location->name }}</span>
    </nav>

    <!-- Page Header Card -->
    <div class="page-header-card">
        <div class="page-title-wrap">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                <span class="pill-code" style="text-transform: uppercase;">{{ $location->type }}</span>
                @if ($location->code)
                    <span class="pill-code">{{ $location->code }}</span>
                @endif
                <span class="badge-{{ $location->isOperational() ? 'success' : 'danger' }}">
                    {{ $location->isOperational() ? 'Operational' : 'Disabled' }}
                </span>
            </div>
            <h1>{{ $location->name }}</h1>
            <p style="color: #64748b; font-size: 13px;">
                {{ $location->parent?->parent?->parent?->name ? $location->parent->parent->parent->name.' › ' : '' }}
                {{ $location->parent?->parent?->name ? $location->parent->parent->name.' › ' : '' }}
                {{ $location->parent?->name ? $location->parent->name.' › ' : '' }}
                {{ $location->name }}
            </p>
        </div>
        <div class="page-actions-wrap">
            <a class="button secondary" href="{{ route('locations.edit', $location) }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                <span>Edit</span>
            </a>
            <a class="button secondary" href="{{ route('locations.label', $location) }}" target="_blank">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                <span>Print QR Label</span>
            </a>
            @if ($location->type === 'bin' && $location->isOperational())
                <a class="button" href="{{ route('goods-receipts.create', ['location_id' => $location->id]) }}" style="background: #10b981;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                    <span>Receive Into Bin</span>
                </a>
            @endif
            <a class="button secondary" href="{{ route('locations.index') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                <span>All Locations</span>
            </a>
        </div>
    </div>

    @if ($children = $location->children)
        @if ($children->isNotEmpty())
            <!-- Child Locations Table Card -->
            <div class="modern-table-card" style="margin-bottom: 20px;">
                <div style="padding: 14px 18px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between;">
                    <h2 style="margin: 0; font-size: 15px; color: #0f172a;">Sub-Locations / Child Nodes ({{ $children->count() }})</h2>
                    @if ($nextType = $location->childType())
                        <a href="{{ route('locations.create', ['type' => $nextType, 'parent_id' => $location->id]) }}" class="button secondary" style="padding: 4px 10px; font-size: 12px;">+ Add {{ ucfirst($nextType) }}</a>
                    @endif
                </div>
                <table class="clean-table">
                    <thead>
                        <tr>
                            <th style="width: 35%;">Name</th>
                            <th style="width: 15%;">Type</th>
                            <th style="width: 20%;">Code</th>
                            <th style="width: 15%;">Status</th>
                            <th style="width: 15%; text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($children as $child)
                        <tr>
                            <td><a href="{{ route('locations.show', $child) }}" style="font-weight: 700; color: #0f172a; text-decoration: none;">{{ $child->name }}</a></td>
                            <td><span class="pill-code" style="text-transform: uppercase;">{{ $child->type }}</span></td>
                            <td><span class="pill-code">{{ $child->code ?: '—' }}</span></td>
                            <td>
                                <span class="badge-{{ $child->isOperational() ? 'success' : 'danger' }}">
                                    {{ $child->isOperational() ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; align-items: center; gap: 5px; justify-content: flex-end;">
                                    <a class="button secondary" style="padding: 3px 8px; font-size: 11.5px;" href="{{ route('locations.edit', $child) }}">Edit</a>
                                    <a class="button secondary" style="padding: 3px 8px; font-size: 11.5px;" href="{{ route('locations.label', $child) }}" target="_blank">QR</a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endif

    @if ($location->type === 'bin')
        <!-- Products and batches in this bin -->
        <div class="modern-table-card">
            <div style="padding: 14px 18px; border-bottom: 1px solid #f1f5f9;">
                <h2 style="margin: 0; font-size: 15px; color: #0f172a;">Products & Batches in this Bin</h2>
            </div>
            @if ($location->inventory->isNotEmpty())
                <table class="clean-table">
                    <thead>
                        <tr>
                            <th style="width: 30%;">Product</th>
                            <th style="width: 15%;">SKU</th>
                            <th style="width: 15%;">Batch</th>
                            <th style="width: 12%; text-align: right;">Quantity</th>
                            <th style="width: 8%;">Unit</th>
                            <th style="width: 20%; text-align: right;">Move Batch</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($location->inventory as $inventory)
                        <tr>
                            <td>
                                <a href="{{ route('products.show', $inventory->product) }}" style="font-weight: 700; color: #0f172a; text-decoration: none;">
                                    {{ $inventory->product->name }}
                                </a>
                            </td>
                            <td><span class="pill-code">{{ $inventory->product->sku }}</span></td>
                            <td><span style="font-family: monospace; font-weight: 700; color: #2563eb;">{{ $inventory->batch_number }}</span></td>
                            <td style="text-align: right; font-weight: 700; color: #16a34a;">{{ $inventory->quantity }}</td>
                            <td><span style="color: #64748b; font-size: 12px;">{{ $inventory->product->unit }}</span></td>
                            <td style="text-align: right;">
                                @if ($destinationBins->isNotEmpty())
                                    <form method="POST" action="{{ route('bin-inventory.move', $inventory) }}" style="display: inline-flex; align-items: center; gap: 5px; justify-content: flex-end; margin: 0;">
                                        @csrf
                                        <select name="destination_location_id" required aria-label="Destination bin" style="padding: 3px 6px; font-size: 11.5px; max-width: 130px;">
                                            <option value="">Choose bin…</option>
                                            @foreach ($destinationBins as $destinationBin)
                                                <option value="{{ $destinationBin->id }}">{{ $destinationBin->code }}</option>
                                            @endforeach
                                        </select>
                                        <button class="button secondary" style="padding: 3px 8px; font-size: 11.5px;" type="submit">Move</button>
                                    </form>
                                @else
                                    <span style="color: #94a3b8;">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @else
                <div style="padding: 32px 20px; text-align: center; color: #64748b;">
                    <p style="margin: 0; font-weight: 600; color: #334155;">No products or batches assigned to this bin.</p>
                    <small style="color: #94a3b8;">{{ $location->isOperational() ? 'This bin is vacant and ready for put-away.' : 'This bin is inactive.' }}</small>
                </div>
            @endif
        </div>
    @endif
@endsection
