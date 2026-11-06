@extends('catalog.layout')

@section('title', 'Receive goods')

@section('content')
    @php
        $existingLines = old('lines', [['product_id' => '', 'quantity' => '', 'batch_number' => '', 'manufactured_at' => '', 'expires_at' => '', 'location_id' => '']]);
        $purchaseOrderId = old('purchase_order_id', $selectedPurchaseOrder?->id);
    @endphp
    <div class="page-head">
        <div><h1>Receive goods</h1><p class="muted">Review all quantities and batch details before posting stock.</p></div>
        <a href="{{ route('goods-receipts.index') }}">Receipt history</a>
    </div>
    <form method="POST" action="{{ route('goods-receipts.review') }}"
        data-offline-operation="Goods receipt review"
        data-offline-sync-url="{{ route('offline.sync.goods-receipt') }}">
        @csrf
        <input type="hidden" name="client_id" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
        <section class="card">
            <div class="form-grid">
                <div class="field">
                    <label for="purchase_order_id">Purchase order</label>
                    <select id="purchase_order_id" name="purchase_order_id">
                        <option value="">Direct receipt (no purchase order)</option>
                        @foreach ($purchaseOrders as $purchaseOrder)
                            <option value="{{ $purchaseOrder->id }}" @selected($purchaseOrderId == $purchaseOrder->id)>{{ $purchaseOrder->order_number }} — {{ $purchaseOrder->supplier->name }} ({{ ucwords(str_replace('_', ' ', $purchaseOrder->status)) }})</option>
                        @endforeach
                    </select>
                    @error('purchase_order_id') <small class="errors">{{ $message }}</small> @enderror
                </div>
                <div class="field">
                    <label for="supplier_id">Supplier (required for direct receipt)</label>
                    <select id="supplier_id" name="supplier_id" @disabled($purchaseOrderId) @required(!$purchaseOrderId)>
                        <option value="">Choose supplier</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>{{ $supplier->name }} ({{ $supplier->code }})</option>
                        @endforeach
                    </select>
                    @error('supplier_id') <small class="errors">{{ $message }}</small> @enderror
                </div>
                <div class="field full">
                    <label for="notes">Receiving notes</label>
                    <textarea id="notes" name="notes">{{ old('notes') }}</textarea>
                </div>
            </div>
            @if ($selectedPurchaseOrder)
                <h2>Outstanding purchase order lines</h2>
                <div class="table-wrap">
                    <table><thead><tr><th>Product</th><th>Ordered</th><th>Received</th><th>Outstanding</th></tr></thead>
                        <tbody>@foreach ($selectedPurchaseOrder->lines as $orderLine)<tr><td>{{ $orderLine->product->sku }} — {{ $orderLine->product->name }}</td><td>{{ $orderLine->ordered_quantity }}</td><td>{{ $orderLine->received_quantity }}</td><td>{{ $orderLine->outstandingQuantity() }}</td></tr>@endforeach</tbody>
                    </table>
                </div>
                <p class="field">
                    <label>
                        <input type="checkbox" name="accept_discrepancies" value="1" @checked(old('accept_discrepancies'))>
                        I reviewed and acknowledge any quantity above the PO outstanding amount.
                    </label>
                </p>
            @endif
        </section>
        <section class="card" style="margin-top:18px">
            <div class="page-head"><h2>Received items</h2><button class="secondary" type="button" id="add-receipt-line">Add item</button></div>
            <div id="receipt-lines">
                @foreach ($existingLines as $index => $line)
                    <div class="form-grid receipt-line">
                        <div class="field"><label>Product</label>
                            <select name="lines[{{ $index }}][product_id]" required>
                                <option value="">Choose product</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}" @selected(($line['product_id'] ?? '') == $product->id)>{{ $product->sku }} — {{ $product->name }} ({{ $product->unit }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field"><label>Received quantity</label><input name="lines[{{ $index }}][quantity]" type="number" step="0.001" min="0.001" required value="{{ $line['quantity'] ?? '' }}"></div>
                        <div class="field"><label>Batch number</label><input name="lines[{{ $index }}][batch_number]" maxlength="100" required value="{{ $line['batch_number'] ?? '' }}"></div>
                        <div class="field"><label>Manufacture date</label><input name="lines[{{ $index }}][manufactured_at]" type="date" value="{{ $line['manufactured_at'] ?? '' }}"></div>
                        <div class="field"><label>Expiry date</label><input name="lines[{{ $index }}][expires_at]" type="date" value="{{ $line['expires_at'] ?? '' }}"></div>
                        <div class="field"><label>Destination bin</label>
                            <select name="lines[{{ $index }}][location_id]" required>
                                <option value="">Choose active bin</option>
                                @foreach ($bins as $bin)
                                    <option value="{{ $bin->id }}" @selected(($line['location_id'] ?? ($index === 0 ? $selectedLocationId : '')) == $bin->id)>{{ $bin->code }} — {{ $bin->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field full"><button type="button" class="secondary remove-receipt-line">Remove item</button></div>
                    </div>
                    <hr>
                @endforeach
            </div>
            @error('lines') <small class="errors">{{ $message }}</small> @enderror
            @error('accept_discrepancies') <small class="errors">{{ $message }}</small> @enderror
            <button type="submit">Review receipt</button>
            <small>When offline, this receipt is queued for server review. It does not update stock until you confirm it online.</small>
        </section>
    </form>
    <template id="receipt-line-template">
        <div class="form-grid receipt-line">
            <div class="field"><label>Product</label>
                <select required data-name="product_id"><option value="">Choose product</option>
                    @foreach ($products as $product)<option value="{{ $product->id }}">{{ $product->sku }} — {{ $product->name }} ({{ $product->unit }})</option>@endforeach
                </select>
            </div>
            <div class="field"><label>Received quantity</label><input type="number" step="0.001" min="0.001" required data-name="quantity"></div>
            <div class="field"><label>Batch number</label><input maxlength="100" required data-name="batch_number"></div>
            <div class="field"><label>Manufacture date</label><input type="date" data-name="manufactured_at"></div>
            <div class="field"><label>Expiry date</label><input type="date" data-name="expires_at"></div>
            <div class="field"><label>Destination bin</label>
                <select required data-name="location_id"><option value="">Choose active bin</option>
                    @foreach ($bins as $bin)<option value="{{ $bin->id }}">{{ $bin->code }} — {{ $bin->name }}</option>@endforeach
                </select>
            </div>
            <div class="field full"><button type="button" class="secondary remove-receipt-line">Remove item</button></div>
        </div><hr>
    </template>
@endsection

@push('scripts')
    <script>
        const receiptLines = document.getElementById('receipt-lines');
        let nextReceiptLine = {{ count($existingLines) }};
        document.getElementById('add-receipt-line').addEventListener('click', () => {
            const fragment = document.getElementById('receipt-line-template').content.cloneNode(true);
            fragment.querySelectorAll('[data-name]').forEach((input) => {
                input.name = `lines[${nextReceiptLine}][${input.dataset.name}]`;
            });
            nextReceiptLine += 1;
            receiptLines.append(fragment);
        });
        receiptLines.addEventListener('click', (event) => {
            if (event.target.matches('.remove-receipt-line') && receiptLines.querySelectorAll('.receipt-line').length > 1) {
                event.target.closest('.receipt-line').nextElementSibling?.remove();
                event.target.closest('.receipt-line').remove();
            }
        });
        document.getElementById('purchase_order_id').addEventListener('change', (event) => {
            const supplier = document.getElementById('supplier_id');
            supplier.disabled = event.target.value !== '';
            supplier.required = event.target.value === '';
            if (supplier.disabled) supplier.value = '';
        });
    </script>
@endpush
