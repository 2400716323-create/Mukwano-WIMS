@extends('catalog.layout')

@section('title', $goodsReceipt->receipt_number)

@section('breadcrumbs')
    <span class="breadcrumbs-separator">/</span>
    <a href="{{ route('goods-receipts.index') }}">Goods Receipts</a>
    <span class="breadcrumbs-separator">/</span>
    <span style="color: #0f172a; font-weight: 600;">{{ $goodsReceipt->receipt_number }}</span>
@endsection

@section('content')
    <!-- Inline Breadcrumbs Navigation -->
    <nav style="display: flex; align-items: center; gap: 8px; font-size: 12.5px; color: #64748b; margin-bottom: 14px;" aria-label="Breadcrumb">
        <a href="{{ route('dashboard') }}" style="color: #64748b; text-decoration: none; display: flex; align-items: center; gap: 4px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
            <span>Dashboard</span>
        </a>
        <span style="color: #cbd5e1;">/</span>
        <a href="{{ route('goods-receipts.index') }}" style="color: #64748b; text-decoration: none;">Goods Receipts</a>
        <span style="color: #cbd5e1;">/</span>
        <span style="color: #0f172a; font-weight: 600;">{{ $goodsReceipt->receipt_number }}</span>
    </nav>

    <div class="page-header-card">
        <div class="page-title-wrap">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                <span class="pill-code">{{ $goodsReceipt->receipt_number }}</span>
                <span class="badge-success">Confirmed & Posted</span>
            </div>
            <h1>{{ $goodsReceipt->supplier?->name ?? 'Direct Warehouse Receipt' }}</h1>
            <p>Received: {{ $goodsReceipt->received_at->format('d M Y, H:i') }} by {{ $goodsReceipt->receiver->name }}</p>
        </div>
        <div class="page-actions-wrap">
            <a class="button secondary" href="{{ route('goods-receipts.print', $goodsReceipt) }}" target="_blank">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                <span>Print GRN</span>
            </a>
            <a class="button secondary" href="{{ route('goods-receipts.index') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                <span>Back to Receipts</span>
            </a>
        </div>
    </div>

    <!-- Receipt Meta Details Card -->
    <div class="card" style="margin-bottom: 16px; padding: 20px;">
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px;">
            <div>
                <span style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 600;">Purchase Order</span>
                <p style="margin: 4px 0 0; font-weight: 700; color: #0f172a;">
                    @if ($goodsReceipt->purchaseOrder)
                        <a href="{{ route('purchase-orders.show', $goodsReceipt->purchaseOrder) }}" style="color: #2563eb; text-decoration: none;">
                            {{ $goodsReceipt->purchaseOrder->order_number }}
                        </a>
                    @else
                        Direct Receipt
                    @endif
                </p>
            </div>
            <div>
                <span style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 600;">Receiving Officer</span>
                <p style="margin: 4px 0 0; font-weight: 600; color: #334155;">{{ $goodsReceipt->receiver->name }}</p>
            </div>
            <div>
                <span style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 600;">System Timestamp</span>
                <p style="margin: 4px 0 0; font-weight: 600; color: #334155;">{{ $goodsReceipt->confirmed_at->format('d M Y, H:i:s') }}</p>
            </div>
            <div>
                <span style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 600;">Total Received Lines</span>
                <p style="margin: 4px 0 0; font-weight: 700; color: #16a34a;">{{ $goodsReceipt->lines->count() }} {{ Str::plural('line', $goodsReceipt->lines->count()) }}</p>
            </div>
        </div>
        @if ($goodsReceipt->notes)
            <div style="margin-top: 14px; padding-top: 12px; border-top: 1px solid #f1f5f9;">
                <span style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 600;">Delivery & Inspection Notes</span>
                <p style="margin: 3px 0 0; font-size: 13px; color: #475569;">{{ $goodsReceipt->notes }}</p>
            </div>
        @endif
    </div>

    <!-- Lines Table Card -->
    <div class="modern-table-card">
        <div style="padding: 14px 18px; border-bottom: 1px solid #f1f5f9;">
            <h2 style="margin: 0; font-size: 15px; color: #0f172a;">Received Stock Items & Batch Allocation</h2>
        </div>
        <table class="clean-table">
            <thead>
                <tr>
                    <th style="width: 25%;">Product Details</th>
                    <th style="width: 14%;">Batch Number</th>
                    <th style="width: 12%;">Batch Status</th>
                    <th style="width: 12%; text-align: right;">Quantity</th>
                    <th style="width: 12%;">Mfg Date</th>
                    <th style="width: 12%;">Expiry Date</th>
                    <th style="width: 13%;">Initial Bin</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($goodsReceipt->lines as $line)
                <tr>
                    <td>
                        <div style="display: flex; flex-direction: column;">
                            <a href="{{ route('products.show', $line->product) }}" style="font-weight: 600; color: #0f172a; text-decoration: none;">
                                {{ $line->product->name }}
                            </a>
                            <span class="pill-code" style="width: fit-content; margin-top: 2px;">{{ $line->product->sku }}</span>
                        </div>
                    </td>
                    <td>
                        <a href="{{ route('batches.show', $line->batch) }}" style="font-family: monospace; font-weight: 700; color: #2563eb; text-decoration: none;">
                            {{ $line->batch->batch_number }}
                        </a>
                    </td>
                    <td>
                        @php
                            $expStatus = $line->batch->expiry_status;
                            $expClass = match($expStatus) {
                                'good' => 'badge-success',
                                'expiring_soon' => 'badge-warning',
                                'expired' => 'badge-danger',
                                default => 'badge-info',
                            };
                        @endphp
                        <span class="{{ $expClass }}">{{ ucwords(str_replace('_', ' ', $expStatus)) }}</span>
                    </td>
                    <td style="text-align: right; font-weight: 700; color: #16a34a;">
                        +{{ number_format($line->quantity) }} <span style="font-size: 11px; font-weight: normal; color: #64748b;">{{ $line->product->unit }}</span>
                    </td>
                    <td><span style="color: #64748b; font-size: 12px;">{{ $line->batch->manufactured_at?->format('d M Y') ?? '—' }}</span></td>
                    <td><span style="color: #64748b; font-size: 12px;">{{ $line->batch->expires_at?->format('d M Y') ?? '—' }}</span></td>
                    <td>
                        <a href="{{ route('locations.show', $line->location) }}" style="font-weight: 600; color: #334155; text-decoration: none; font-size: 12.5px;">
                            {{ $line->location->code }}
                        </a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection
