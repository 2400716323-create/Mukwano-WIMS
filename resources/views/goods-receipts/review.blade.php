
@extends('catalog.layout')

@section('title', 'Review goods receipt')

@section('content')
    <div class="page-head">
        <div>
            <h1>Review receipt before posting</h1>
            <p class="muted">
                Review the information entered below. This page does not post stock
                or change purchase-order quantities.
            </p>
        </div>

        <a href="{{ route('goods-receipts.create', ['purchase_order_id' => $purchaseOrder?->id]) }}">
            Back to receipt form
        </a>
    </div>

    <section class="card">
        <h2>Receipt Information</h2>

        <p>
            <strong>Supplier:</strong>
            {{ $supplier->name }}
        </p>

        <p>
            <strong>Purchase Order:</strong>
            {{ $purchaseOrder?->order_number ?? 'Direct receipt' }}
        </p>

        @if ($receiptData['notes'] ?? null)
            <p>
                <strong>Receiving Notes:</strong>
                {{ $receiptData['notes'] }}
            </p>
        @endif

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Batch Number</th>
                        <th>Manufactured</th>
                        <th>Expires</th>
                        <th>Destination</th>
                        <th>PO Outstanding</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($lines as $line)
                        <tr>
                            <td>
                                {{ $line['product']->sku }}
                                —
                                {{ $line['product']->name }}
                            </td>

                            <td>
                                {{ $line['quantity'] }}
                                {{ $line['product']->unit }}
                            </td>

                            <td>{{ $line['batch_number'] }}</td>

                            <td>{{ $line['manufactured_at'] ?? '—' }}</td>

                            <td>{{ $line['expires_at'] ?? '—' }}</td>

                            <td>
                                {{ $line['location']->code ?: 'No code' }}
                                —
                                {{ $line['location']->name }}
                            </td>

                            <td>{{ $line['outstanding'] ?? 'Direct' }}</td>
                        </tr>

                        @if ($line['exceeds_outstanding'])
                            <tr>
                                <td class="errors" colspan="7">
                                    Warning: The entered quantity exceeds the current PO outstanding amount.
                                </td>
                            </tr>
                        @elseif ($line['partial'])
                            <tr>
                                <td colspan="7">
                                    Partial receipt: some purchase-order quantity
                                    remains outstanding.
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="margin-top: 20px; display: flex; gap: 12px; flex-wrap: wrap;">
            <a href="{{ route('goods-receipts.create', ['purchase_order_id' => $purchaseOrder?->id]) }}"
               class="btn btn-secondary">
                Edit Receipt Details
            </a>

            <a href="{{ route('goods-receipts.index') }}"
               class="btn btn-primary">
                Return to Goods Receipts
            </a>
        </div>

        <p class="muted" style="margin-top: 16px;">
            Review completed. No inventory transaction has been performed.
        </p>
    </section>
@endsection
