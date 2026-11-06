@extends('catalog.layout')

@section('title', 'Review goods receipt')

@section('content')
    <div class="page-head">
        <div><h1>Review receipt before posting</h1><p class="muted">Stock and purchase-order quantities remain unchanged until you confirm.</p></div>
        <a href="{{ route('goods-receipts.create', ['purchase_order_id' => $purchaseOrder?->id]) }}">Back to receipt</a>
    </div>
    <section class="card">
        <p><strong>Supplier:</strong> {{ $supplier->name }} · <strong>PO:</strong> {{ $purchaseOrder?->order_number ?? 'Direct receipt' }}</p>
        @if ($receiptData['notes'] ?? null)<p><strong>Notes:</strong> {{ $receiptData['notes'] }}</p>@endif
        <div class="table-wrap">
            <table><thead><tr><th>Product</th><th>Quantity</th><th>Batch</th><th>Manufactured</th><th>Expires</th><th>Destination</th><th>PO outstanding</th></tr></thead>
                <tbody>
                @foreach ($lines as $line)
                    <tr>
                        <td>{{ $line['product']->sku }} — {{ $line['product']->name }}</td>
                        <td>{{ $line['quantity'] }} {{ $line['product']->unit }}</td>
                        <td>{{ $line['batch_number'] }}</td>
                        <td>{{ $line['manufactured_at'] ?? '—' }}</td>
                        <td>{{ $line['expires_at'] ?? '—' }}</td>
                        <td>{{ $line['location']->code }} — {{ $line['location']->name }}</td>
                        <td>{{ $line['outstanding'] ?? 'Direct' }}</td>
                    </tr>
                    @if ($line['exceeds_outstanding'])
                        <tr><td class="errors" colspan="7">Warning: this quantity exceeds the current PO outstanding amount. The discrepancy was explicitly acknowledged.</td></tr>
                    @elseif ($line['partial'])
                        <tr><td colspan="7">Partial receipt: the remaining PO quantity will stay outstanding.</td></tr>
                    @endif
                @endforeach
                </tbody>
            </table>
        </div>
        <form method="POST" action="{{ route('goods-receipts.confirm', $token) }}" style="margin-top:18px">
            @csrf
            <button type="submit">Confirm and post stock</button>
        </form>
    </section>
@endsection
