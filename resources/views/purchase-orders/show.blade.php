@extends('catalog.layout')

@section('title', $purchaseOrder->order_number)

@section('breadcrumbs')
    <span class="breadcrumbs-separator">/</span>
    <a href="{{ route('purchase-orders.index') }}">Purchase Orders</a>
    <span class="breadcrumbs-separator">/</span>
    <span style="color: #0f172a; font-weight: 600;">{{ $purchaseOrder->order_number }}</span>
@endsection

@section('content')
    <!-- Inline Breadcrumbs Navigation -->
    <nav style="display: flex; align-items: center; gap: 8px; font-size: 12.5px; color: #64748b; margin-bottom: 14px;" aria-label="Breadcrumb">
        <a href="{{ route('dashboard') }}" style="color: #64748b; text-decoration: none; display: flex; align-items: center; gap: 4px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
            <span>Dashboard</span>
        </a>
        <span style="color: #cbd5e1;">/</span>
        <a href="{{ route('purchase-orders.index') }}" style="color: #64748b; text-decoration: none;">Purchase Orders</a>
        <span style="color: #cbd5e1;">/</span>
        <span style="color: #0f172a; font-weight: 600;">{{ $purchaseOrder->order_number }}</span>
    </nav>

    <div class="page-header-card">
        <div class="page-title-wrap">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                <span class="pill-code">{{ $purchaseOrder->order_number }}</span>
                @php
                    $badgeClass = match($purchaseOrder->status) {
                        'approved' => 'badge-info',
                        'received' => 'badge-success',
                        'partially_received' => 'badge-warning',
                        'cancelled' => 'badge-danger',
                        default => 'badge-purple',
                    };
                @endphp
                <span class="{{ $badgeClass }}">{{ ucwords(str_replace('_', ' ', $purchaseOrder->status)) }}</span>
            </div>
            <h1>{{ $purchaseOrder->supplier->name }}</h1>
            <p>Order Date: {{ $purchaseOrder->order_date->format('d M Y') }} · Expected: {{ $purchaseOrder->expected_date?->format('d M Y') ?? 'Not specified' }}</p>
        </div>
        <div class="page-actions-wrap">
            <a class="button secondary" href="{{ route('purchase-orders.print', $purchaseOrder) }}" target="_blank">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                <span>Print PO</span>
            </a>
            @if ($purchaseOrder->status === 'draft')
                <a class="button secondary" href="{{ route('purchase-orders.edit', $purchaseOrder) }}">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                    <span>Edit Draft</span>
                </a>
                <form method="POST" action="{{ route('purchase-orders.approve', $purchaseOrder) }}" style="margin: 0;">
                    @csrf
                    <button type="submit" class="button">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        <span>Approve Order</span>
                    </button>
                </form>
            @endif
            @if (in_array($purchaseOrder->status, ['approved', 'partially_received'], true))
                <a class="button" href="{{ route('goods-receipts.create', ['purchase_order_id' => $purchaseOrder->id]) }}" style="background: #10b981;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                    <span>Receive Goods (GRN)</span>
                </a>
            @endif
            @if (in_array($purchaseOrder->status, ['draft', 'approved', 'partially_received'], true))
                <form method="POST" action="{{ route('purchase-orders.cancel', $purchaseOrder) }}" style="margin: 0;" onsubmit="return confirm('Cancel this purchase order?')">
                    @csrf
                    @method('PATCH')
                    <button class="button danger" type="submit">Cancel</button>
                </form>
            @endif
            <a class="button secondary" href="{{ route('purchase-orders.index') }}">Back</a>
        </div>
    </div>

    <!-- PO Details & Info Grid -->
    <div class="card" style="margin-bottom: 16px; padding: 20px;">
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px;">
            <div>
                <span style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 600;">Supplier</span>
                <p style="margin: 4px 0 0; font-weight: 700; color: #0f172a;"><a href="{{ route('suppliers.show', $purchaseOrder->supplier) }}" style="color: inherit; text-decoration: none;">{{ $purchaseOrder->supplier->name }}</a></p>
            </div>
            <div>
                <span style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 600;">Created By</span>
                <p style="margin: 4px 0 0; font-weight: 600; color: #334155;">{{ $purchaseOrder->creator?->name ?? 'System' }}</p>
            </div>
            <div>
                <span style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 600;">Authorized By</span>
                <p style="margin: 4px 0 0; font-weight: 600; color: #334155;">{{ $purchaseOrder->approver?->name ?? 'Awaiting Approval' }}</p>
            </div>
            <div>
                <span style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 600;">Expected Delivery</span>
                <p style="margin: 4px 0 0; font-weight: 600; color: #2563eb;">{{ $purchaseOrder->expected_date?->format('d M Y') ?? 'Flexible' }}</p>
            </div>
        </div>
        @if ($purchaseOrder->notes)
            <div style="margin-top: 14px; padding-top: 12px; border-top: 1px solid #f1f5f9;">
                <span style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 600;">Order Notes</span>
                <p style="margin: 3px 0 0; font-size: 13px; color: #475569;">{{ $purchaseOrder->notes }}</p>
            </div>
        @endif
    </div>

    <!-- PO Line Items Table Card -->
    <div class="modern-table-card">
        <div style="padding: 14px 18px; border-bottom: 1px solid #f1f5f9;">
            <h2 style="margin: 0; font-size: 15px; color: #0f172a;">Order Line Items</h2>
        </div>
        <table class="clean-table">
            <thead>
                <tr>
                    <th style="width: 18%;">SKU Code</th>
                    <th style="width: 34%;">Product Name</th>
                    <th style="width: 12%; text-align: right;">Ordered</th>
                    <th style="width: 12%; text-align: right;">Received</th>
                    <th style="width: 12%; text-align: right;">Outstanding</th>
                    <th style="width: 12%;">Unit</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($purchaseOrder->lines as $line)
                <tr>
                    <td><span class="pill-code">{{ $line->product->sku }}</span></td>
                    <td>
                        <a href="{{ route('products.show', $line->product) }}" style="font-weight: 600; color: #0f172a; text-decoration: none;">
                            {{ $line->product->name }}
                        </a>
                    </td>
                    <td style="text-align: right; font-weight: 700;">{{ number_format($line->ordered_quantity) }}</td>
                    <td style="text-align: right; font-weight: 700; color: #16a34a;">{{ number_format($line->received_quantity) }}</td>
                    <td style="text-align: right; font-weight: 700; color: {{ $line->outstandingQuantity() > 0 ? '#ea580c' : '#64748b' }};">
                        {{ $purchaseOrder->status === 'cancelled' ? '—' : number_format($line->outstandingQuantity()) }}
                    </td>
                    <td><span style="color: #64748b; font-size: 12px;">{{ $line->product->unit }}</span></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    @if ($purchaseOrder->goodsReceipts->isNotEmpty())
        <div class="modern-table-card" style="margin-top: 16px;">
            <div style="padding: 14px 18px; border-bottom: 1px solid #f1f5f9;">
                <h2 style="margin: 0; font-size: 15px; color: #0f172a;">Linked Goods Receipts (GRNs)</h2>
            </div>
            <table class="clean-table">
                <thead>
                    <tr>
                        <th>Receipt No</th>
                        <th>Received Date</th>
                        <th>Received By</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($purchaseOrder->goodsReceipts as $receipt)
                    <tr>
                        <td>
                            <a href="{{ route('goods-receipts.show', $receipt) }}" style="font-weight: 700; color: #2563eb; text-decoration: none; font-family: monospace;">
                                {{ $receipt->receipt_number }}
                            </a>
                        </td>
                        <td>{{ $receipt->received_at->format('d M Y, H:i') }}</td>
                        <td>{{ $receipt->receiver?->name ?? 'Storekeeper' }}</td>
                        <td>
                            <a href="{{ route('goods-receipts.show', $receipt) }}" class="button secondary" style="padding: 3px 8px; font-size: 12px;">View GRN</a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
