@extends('catalog.layout')

@section('title', 'Stock movement ledger')

@section('breadcrumbs')
    <a href="{{ route('inventory.index') }}">Stock & Batches</a>
    <span class="sep">/</span>
    <span class="current">Movement Ledger</span>
@endsection

@section('content')
    <!-- Unified Page Header -->
    <div class="page-header-card">
        <div class="page-title-wrap">
            <nav class="breadcrumb-trail" aria-label="Breadcrumb">
                <a href="{{ route('dashboard') }}">Dashboard</a>
                <span class="separator">/</span>
                <a href="{{ route('inventory.index') }}">Stock Control</a>
                <span class="separator">/</span>
                <span class="current">Movement Ledger</span>
            </nav>
            <h1>Stock movement ledger</h1>
            <p>Complete, filterable history of receipts, transfers, dispatches, adjustments, and put-away movements.</p>
        </div>
        <div class="page-actions-wrap">
            <a class="button secondary" href="{{ route('inventory.index') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                <span>Stock Overview</span>
            </a>
            <a class="button" href="{{ route('stock-transfers.create') }}" style="background: #2563eb; color: #ffffff;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="17 1 21 5 17 9"></polyline><path d="M3 11V9a4 4 0 0 1 4-4h14"></path><polyline points="7 23 3 19 7 15"></polyline><path d="M21 13v2a4 4 0 0 1-4 4H3"></path></svg>
                <span>Transfer Stock</span>
            </a>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card" style="background: #ffffff; border-radius: 10px; border: 1px solid #e2e8f0; padding: 20px; margin-bottom: 20px;">
        <form method="GET" action="{{ route('inventory.ledger') }}">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; align-items: flex-end;">
                <div class="field">
                    <label for="product_id" style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 4px;">Product</label>
                    <select id="product_id" name="product_id" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 10px; font-size: 13px;">
                        <option value="">All products</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" @selected(($filters['product_id'] ?? '') == $product->id)>{{ $product->sku }} — {{ $product->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="batch_id" style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 4px;">Batch</label>
                    <select id="batch_id" name="batch_id" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 10px; font-size: 13px;">
                        <option value="">All batches</option>
                        @foreach ($batches as $batch)
                            <option value="{{ $batch->id }}" @selected(($filters['batch_id'] ?? '') == $batch->id)>{{ $batch->product->sku }} — {{ $batch->batch_number }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="location_id" style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 4px;">Bin</label>
                    <select id="location_id" name="location_id" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 10px; font-size: 13px;">
                        <option value="">All bins</option>
                        @foreach ($locations as $location)
                            <option value="{{ $location->id }}" @selected(($filters['location_id'] ?? '') == $location->id)>{{ $location->code }} — {{ $location->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="movement_type" style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 4px;">Movement type</label>
                    <select id="movement_type" name="movement_type" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 10px; font-size: 13px;">
                        <option value="">All movement types</option>
                        @foreach (['opening_balance', 'receipt', 'put_away', 'transfer', 'dispatch', 'adjustment'] as $movementType)
                            <option value="{{ $movementType }}" @selected(($filters['movement_type'] ?? '') === $movementType)>{{ ucwords(str_replace('_', ' ', $movementType)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="from" style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 4px;">From date</label>
                    <input id="from" name="from" type="date" value="{{ $filters['from'] ?? '' }}" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 10px; font-size: 13px;">
                </div>
                <div class="field">
                    <label for="to" style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 4px;">To date</label>
                    <input id="to" name="to" type="date" value="{{ $filters['to'] ?? '' }}" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 10px; font-size: 13px;">
                </div>
                <div style="display: flex; gap: 8px;">
                    <button type="submit" class="button" style="background: #2563eb; color: #ffffff; padding: 6px 16px; font-size: 13px;">Filter ledger</button>
                    <a class="button secondary" href="{{ route('inventory.ledger') }}" style="padding: 6px 12px; font-size: 13px;">Clear</a>
                </div>
            </div>
        </form>
    </div>

    <!-- Movements Table Card -->
    <div class="modern-table-card">
        <table class="clean-table">
            <thead>
                <tr>
                    <th style="width: 13%;">Date & Time</th>
                    <th style="width: 10%;">Type</th>
                    <th style="width: 18%;">Product / SKU</th>
                    <th style="width: 11%;">Batch</th>
                    <th style="width: 8%;">Source</th>
                    <th style="width: 8%;">Destination</th>
                    <th style="width: 7%; text-align: right;">Quantity</th>
                    <th style="width: 11%;">Reason / Ref</th>
                    <th style="width: 7%;">User</th>
                    <th style="width: 7%;">Approver</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($movements as $movement)
                <tr>
                    <td><span style="color: #64748b; font-size: 12px;">{{ $movement->moved_at->format('Y-m-d H:i:s') }}</span></td>
                    <td><span class="pill-code">{{ ucwords(str_replace('_', ' ', $movement->movement_type)) }}</span></td>
                    <td>
                        <a href="{{ route('products.show', $movement->batch->product) }}" style="font-weight: 600; color: #0f172a; text-decoration: none; font-size: 12.5px;">
                            {{ $movement->batch->product->sku }}
                        </a>
                        <small style="display: block; color: #64748b; font-size: 11px;">{{ $movement->batch->product->name }}</small>
                    </td>
                    <td><span style="font-family: monospace; font-weight: 700; color: #2563eb;">{{ $movement->batch->batch_number }}</span></td>
                    <td>
                        @if ($movement->fromLocation)
                            <span class="pill-code">{{ $movement->fromLocation->code }}</span>
                        @else
                            <span style="color: #94a3b8;">—</span>
                        @endif
                    </td>
                    <td>
                        @if ($movement->toLocation)
                            <span class="pill-code">{{ $movement->toLocation->code }}</span>
                        @else
                            <span style="color: #94a3b8;">—</span>
                        @endif
                    </td>
                    <td style="text-align: right; font-weight: 700;">{{ $movement->quantity }}</td>
                    <td><span style="font-size: 12px; color: #475569;">{{ $movement->reason ?? $movement->reference_type ?? '—' }}</span></td>
                    <td><span style="font-size: 12px; color: #64748b;">{{ $movement->performer?->name ?? 'System' }}</span></td>
                    <td><span style="font-size: 12px; color: #64748b;">{{ $movement->approver?->name ?? '—' }}</span></td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" style="text-align: center; padding: 32px 20px; color: #64748b;">No movements match these filters.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
        @if ($movements->hasPages())
            <div style="padding: 12px 18px; border-top: 1px solid #f1f5f9;">
                {{ $movements->links() }}
            </div>
        @endif
    </div>
@endsection
