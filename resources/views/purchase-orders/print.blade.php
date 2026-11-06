@extends('catalog.layout')

@section('title', 'Print '.$purchaseOrder->order_number)

@section('content')
    <style>@media print { header, .no-print { display: none !important; } main { width: 100%; padding: 0; } }</style>
    <div class="page-head no-print">
        <h1>Purchase order {{ $purchaseOrder->order_number }}</h1>
        <button type="button" onclick="window.print()">Print</button>
    </div>
    <section class="card">
        <h1>Purchase Order {{ $purchaseOrder->order_number }}</h1>
        <p><strong>Supplier:</strong> {{ $purchaseOrder->supplier->name }}</p>
        <p><strong>Order date:</strong> {{ $purchaseOrder->order_date->toDateString() }} · <strong>Expected:</strong> {{ $purchaseOrder->expected_date?->toDateString() ?? '—' }}</p>
        <p><strong>Status:</strong> {{ ucwords(str_replace('_', ' ', $purchaseOrder->status)) }}</p>
        <div class="table-wrap">
            <table><thead><tr><th>SKU</th><th>Product</th><th>Quantity</th><th>Unit</th></tr></thead>
                <tbody>@foreach ($purchaseOrder->lines as $line)<tr><td>{{ $line->product->sku }}</td><td>{{ $line->product->name }}</td><td>{{ $line->ordered_quantity }}</td><td>{{ $line->product->unit }}</td></tr>@endforeach</tbody>
            </table>
        </div>
        @if ($purchaseOrder->notes)<p><strong>Notes:</strong> {{ $purchaseOrder->notes }}</p>@endif
    </section>
@endsection
