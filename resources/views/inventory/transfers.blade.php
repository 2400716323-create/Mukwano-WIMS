@extends('catalog.layout')

@section('title', 'Stock transfers')

@section('breadcrumbs')
    <a href="{{ route('inventory.index') }}">Stock & Batches</a>
    <span class="sep">/</span>
    <span class="current">Stock Transfers</span>
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
                <span class="current">Stock Transfers</span>
            </nav>
            <h1>Stock transfers</h1>
            <p>Move available batch quantities between active bins. Source debit and destination credit are committed together in an atomic transaction.</p>
        </div>
        <div class="page-actions-wrap">
            <a class="button secondary" href="{{ route('inventory.ledger', ['movement_type' => 'transfer']) }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                <span>Transfer history</span>
            </a>
            <a class="button secondary" href="{{ route('inventory.index') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                <span>Stock overview</span>
            </a>
        </div>
    </div>

    <!-- Active Available Balances for Transfer -->
    <div class="modern-table-card">
        <div style="padding: 14px 18px; border-bottom: 1px solid #f1f5f9;">
            <h2 style="margin: 0; font-size: 15px; color: #0f172a;">Available Batches for Relocation</h2>
        </div>
        <table class="clean-table">
            <thead>
                <tr>
                    <th style="width: 20%;">Product</th>
                    <th style="width: 14%;">Batch</th>
                    <th style="width: 14%;">Source bin</th>
                    <th style="width: 12%;">Available</th>
                    <th style="width: 40%;">Transfer Execution</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($balances as $balance)
                @if ($balance->location->isOperational() && $locations->where('id', '!=', $balance->location_id)->isNotEmpty())
                    <tr>
                        <td>
                            <a href="{{ route('products.show', $balance->batch->product) }}" style="font-weight: 600; color: #0f172a; text-decoration: none; font-size: 13px;">
                                {{ $balance->batch->product->sku }}
                            </a>
                            <small style="display: block; color: #64748b; font-size: 11px;">{{ $balance->batch->product->name }}</small>
                        </td>
                        <td><span style="font-family: monospace; font-weight: 700; color: #2563eb;">{{ $balance->batch->batch_number }}</span></td>
                        <td>
                            <span class="pill-code">{{ $balance->location->code }}</span>
                            <small style="display: block; color: #64748b; font-size: 11px;">{{ $balance->location->name }}</small>
                        </td>
                        <td>
                            <span style="font-weight: 700; color: #16a34a; font-size: 13.5px;">{{ $balance->quantity }}</span>
                            <span style="font-size: 11.5px; color: #64748b;">{{ $balance->batch->product->unit }}</span>
                        </td>
                        <td>
                            <form method="POST" action="{{ route('stock-transfers.store', $balance) }}" style="display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin: 0;">
                                @csrf
                                <input type="hidden" name="idempotency_key" value="{{ \Illuminate\Support\Str::uuid() }}">
                                <select name="destination_location_id" required aria-label="Destination bin" style="font-size: 12px; padding: 5px 8px; border-radius: 6px; border: 1px solid #cbd5e1; max-width: 150px;">
                                    <option value="">Choose destination</option>
                                    @foreach ($locations->where('id', '!=', $balance->location_id) as $location)
                                        <option value="{{ $location->id }}">{{ $location->code }} — {{ $location->name }}</option>
                                    @endforeach
                                </select>
                                <input name="quantity" type="number" step="0.001" min="0.001" max="{{ $balance->quantity }}" required value="{{ $balance->quantity }}" aria-label="Transfer quantity"
                                       style="width: 80px; font-size: 12px; padding: 5px 8px; border-radius: 6px; border: 1px solid #cbd5e1; text-align: right;">
                                <input name="reason" type="text" maxlength="1000" required placeholder="Reason" aria-label="Transfer reason"
                                       style="width: 120px; font-size: 12px; padding: 5px 8px; border-radius: 6px; border: 1px solid #cbd5e1;">
                                <input name="transferred_at" type="datetime-local" required value="{{ now()->format('Y-m-d\TH:i') }}" aria-label="Transfer date and time"
                                       style="font-size: 12px; padding: 5px 8px; border-radius: 6px; border: 1px solid #cbd5e1;">
                                <button type="submit" class="button" style="background: #2563eb; color: #ffffff; padding: 5px 12px; font-size: 12px; font-weight: 600;">Transfer</button>
                            </form>
                        </td>
                    </tr>
                @endif
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; padding: 32px 20px; color: #64748b;">No stock balances are available to transfer.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
