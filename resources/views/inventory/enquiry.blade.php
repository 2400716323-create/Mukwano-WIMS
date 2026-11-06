@extends('catalog.layout')

@section('title', 'Stock enquiry')

@section('breadcrumbs')
    <a href="{{ route('inventory.index') }}">Stock & Batches</a>
    <span class="sep">/</span>
    <span class="current">Stock Enquiry</span>
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
                <span class="current">Stock Enquiry</span>
            </nav>
            <h1>Stock enquiry</h1>
            <p>Search current product, batch and bin balances alongside their complete movement history.</p>
        </div>
        <div class="page-actions-wrap">
            <a class="button secondary" href="{{ route('inventory.index') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                <span>Stock overview</span>
            </a>
            <a class="button" href="{{ route('stock-transfers.create') }}" style="background: #2563eb; color: #ffffff;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="17 1 21 5 17 9"></polyline><path d="M3 11V9a4 4 0 0 1 4-4h14"></path><polyline points="7 23 3 19 7 15"></polyline><path d="M21 13v2a4 4 0 0 1-4 4H3"></path></svg>
                <span>Transfer Stock</span>
            </a>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card" style="background: #ffffff; border-radius: 10px; border: 1px solid #e2e8f0; padding: 20px; margin-bottom: 20px;">
        <form method="GET" action="{{ route('inventory.enquiry') }}">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; align-items: flex-end;">
                <div class="field">
                    <label for="search" style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 4px;">Search product, batch or location</label>
                    <input id="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Keywords..."
                           style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 10px; font-size: 13px;">
                </div>
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
                <div style="display: flex; gap: 8px;">
                    <button type="submit" class="button" style="background: #2563eb; color: #ffffff; padding: 6px 16px; font-size: 13px;">Enquire</button>
                    @if ($hasFilter)
                        <a class="button secondary" href="{{ route('inventory.enquiry') }}" style="padding: 6px 12px; font-size: 13px;">Clear</a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    @if (! $hasFilter)
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 24px; text-align: center; color: #64748b;">
            <p style="margin: 0; font-size: 13.5px; font-weight: 500;">Choose a search term, product, batch, or bin to view balances and movement history.</p>
        </div>
    @else
        <!-- Current Balances Table -->
        <div class="modern-table-card" style="margin-bottom: 24px;">
            <div style="padding: 14px 18px; border-bottom: 1px solid #f1f5f9;">
                <h2 style="margin: 0; font-size: 15px; color: #0f172a;">Current balances</h2>
            </div>
            <table class="clean-table">
                <thead>
                    <tr>
                        <th style="width: 30%;">SKU / Product</th>
                        <th style="width: 18%;">Batch</th>
                        <th style="width: 14%;">Expiry status</th>
                        <th style="width: 20%;">Bin</th>
                        <th style="width: 10%; text-align: right;">Quantity</th>
                        <th style="width: 8%;">Unit</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($balances as $balance)
                    <tr>
                        <td>
                            <a href="{{ route('products.show', $balance->batch->product) }}" style="font-weight: 700; color: #0f172a; text-decoration: none;">
                                {{ $balance->batch->product->sku }} — {{ $balance->batch->product->name }}
                            </a>
                        </td>
                        <td><span style="font-family: monospace; font-weight: 700; color: #2563eb;">{{ $balance->batch->batch_number }}</span></td>
                        <td><span class="pill-code">{{ $balance->batch->expiry_status }}</span></td>
                        <td><span class="pill-code">{{ $balance->location->code }}</span> <small style="color: #64748b;">{{ $balance->location->name }}</small></td>
                        <td style="text-align: right; font-weight: 700; color: #16a34a;">{{ $balance->quantity }}</td>
                        <td><span style="color: #64748b; font-size: 12px;">{{ $balance->batch->product->unit }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 24px; color: #64748b;">No current balances match this enquiry.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
            @if ($balances->hasPages())
                <div style="padding: 12px 18px; border-top: 1px solid #f1f5f9;">
                    {{ $balances->links() }}
                </div>
            @endif
        </div>

        <!-- Movement History Table -->
        <div class="modern-table-card">
            <div style="padding: 14px 18px; border-bottom: 1px solid #f1f5f9;">
                <h2 style="margin: 0; font-size: 15px; color: #0f172a;">Movement history</h2>
            </div>
            <table class="clean-table">
                <thead>
                    <tr>
                        <th style="width: 14%;">Date</th>
                        <th style="width: 12%;">Movement</th>
                        <th style="width: 22%;">Product / batch</th>
                        <th style="width: 12%;">From</th>
                        <th style="width: 12%;">To</th>
                        <th style="width: 8%; text-align: right;">Quantity</th>
                        <th style="width: 10%;">Reason</th>
                        <th style="width: 10%;">User</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($movements as $movement)
                    <tr>
                        <td><span style="color: #64748b; font-size: 12px;">{{ $movement->moved_at->format('Y-m-d H:i:s') }}</span></td>
                        <td><span class="pill-code">{{ ucwords(str_replace('_', ' ', $movement->movement_type)) }}</span></td>
                        <td>
                            <div style="font-weight: 600; color: #0f172a; font-size: 12.5px;">{{ $movement->batch->product->sku }}</div>
                            <span style="font-family: monospace; font-size: 11.5px; color: #2563eb;">{{ $movement->batch->batch_number }}</span>
                        </td>
                        <td><span class="pill-code">{{ $movement->fromLocation?->code ?? '—' }}</span></td>
                        <td><span class="pill-code">{{ $movement->toLocation?->code ?? '—' }}</span></td>
                        <td style="text-align: right; font-weight: 700;">{{ $movement->quantity }}</td>
                        <td><span style="font-size: 12px; color: #475569;">{{ $movement->reason ?? $movement->reference_type ?? '—' }}</span></td>
                        <td><span style="font-size: 12px; color: #64748b;">{{ $movement->performer?->name ?? 'System' }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 24px; color: #64748b;">No movement history matches this enquiry.</td>
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
    @endif
@endsection
