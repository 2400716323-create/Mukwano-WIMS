@extends('catalog.layout')

@section('title', 'Print '.$goodsReceipt->receipt_number)

@section('content')
    <style>@media print { header, .no-print { display: none !important; } main { width: 100%; padding: 0; } }</style>
    <div class="page-head no-print"><h1>Goods Receipt {{ $goodsReceipt->receipt_number }}</h1><button type="button" onclick="window.print()">Print</button></div>
    <section class="card">
        <h1>Goods Receipt {{ $goodsReceipt->receipt_number }}</h1>
        <p><strong>Supplier:</strong> {{ $goodsReceipt->supplier?->name ?? 'Direct receipt' }} · <strong>PO:</strong> {{ $goodsReceipt->purchaseOrder?->order_number ?? '—' }}</p>
        <p><strong>Received by:</strong> {{ $goodsReceipt->receiver->name }} · <strong>Received at:</strong> {{ $goodsReceipt->received_at->format('Y-m-d H:i:s') }}</p>
        <div class="table-wrap">
            <table><thead><tr><th>SKU</th><th>Product</th><th>Quantity</th><th>Batch</th><th>Manufactured</th><th>Expiry</th><th>Bin</th></tr></thead>
                <tbody>@foreach ($goodsReceipt->lines as $line)<tr>
                    <td>{{ $line->product->sku }}</td><td>{{ $line->product->name }}</td><td>{{ $line->quantity }} {{ $line->product->unit }}</td>
                    <td>{{ $line->batch->batch_number }}</td><td>{{ $line->batch->manufactured_at?->toDateString() ?? '—' }}</td>
                    <td>{{ $line->batch->expires_at?->toDateString() ?? '—' }}</td><td>{{ $line->location->code }} — {{ $line->location->name }}</td>
                </tr>@endforeach</tbody>
            </table>
        </div>
        @if ($goodsReceipt->notes)<p><strong>Notes:</strong> {{ $goodsReceipt->notes }}</p>@endif
    </section>
@endsection
