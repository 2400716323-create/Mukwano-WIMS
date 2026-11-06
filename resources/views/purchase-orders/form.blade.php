@extends('catalog.layout')

@section('title', $purchaseOrder->exists ? 'Edit purchase order' : 'Create purchase order')

@section('breadcrumbs')
    <span class="breadcrumbs-separator">/</span>
    <a href="{{ route('purchase-orders.index') }}">Purchase Orders</a>
    <span class="breadcrumbs-separator">/</span>
    @if ($purchaseOrder->exists)
        <a href="{{ route('purchase-orders.show', $purchaseOrder) }}">{{ $purchaseOrder->order_number }}</a>
        <span class="breadcrumbs-separator">/</span>
        <span style="color: #0f172a; font-weight: 600;">Edit</span>
    @else
        <span style="color: #0f172a; font-weight: 600;">New Purchase Order</span>
    @endif
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
        @if ($purchaseOrder->exists)
            <a href="{{ route('purchase-orders.show', $purchaseOrder) }}" style="color: #64748b; text-decoration: none;">{{ $purchaseOrder->order_number }}</a>
            <span style="color: #cbd5e1;">/</span>
            <span style="color: #0f172a; font-weight: 600;">Edit</span>
        @else
            <span style="color: #0f172a; font-weight: 600;">New Purchase Order</span>
        @endif
    </nav>

    @php
        $existingLines = old('lines', $purchaseOrder->exists
            ? $purchaseOrder->lines->map(fn ($line) => ['product_id' => $line->product_id, 'ordered_quantity' => $line->ordered_quantity])->all()
            : [['product_id' => '', 'ordered_quantity' => '']]);
    @endphp

    <div class="page-header-card">
        <div class="page-title-wrap">
            <h1>{{ $purchaseOrder->exists ? 'Edit '.$purchaseOrder->order_number : 'Create Purchase Order' }}</h1>
            <p>{{ $purchaseOrder->exists ? 'Update supplier order lines and delivery dates.' : 'Authorize a new inventory procurement order with line items.' }}</p>
        </div>
        <div class="page-actions-wrap">
            <a class="button secondary" href="{{ $purchaseOrder->exists ? route('purchase-orders.show', $purchaseOrder) : route('purchase-orders.index') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                <span>Cancel</span>
            </a>
        </div>
    </div>
    <form method="POST" action="{{ $purchaseOrder->exists ? route('purchase-orders.update', $purchaseOrder) : route('purchase-orders.store') }}">
        @csrf
        @if ($purchaseOrder->exists) @method('PUT') @endif
        <section class="card">
            <div class="form-grid">
                <div class="field">
                    <label for="supplier_id">Supplier</label>
                    <select id="supplier_id" name="supplier_id" required>
                        <option value="">Choose supplier</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected(old('supplier_id', $purchaseOrder->supplier_id) == $supplier->id)>{{ $supplier->name }} ({{ $supplier->code }})</option>
                        @endforeach
                    </select>
                    @error('supplier_id') <small class="errors">{{ $message }}</small> @enderror
                </div>
                <div class="field">
                    <label for="order_date">Order date</label>
                    <input id="order_date" name="order_date" type="date" required value="{{ old('order_date', $purchaseOrder->order_date?->toDateString() ?? today()->toDateString()) }}">
                    @error('order_date') <small class="errors">{{ $message }}</small> @enderror
                </div>
                <div class="field">
                    <label for="expected_date">Expected date</label>
                    <input id="expected_date" name="expected_date" type="date" value="{{ old('expected_date', $purchaseOrder->expected_date?->toDateString()) }}">
                    @error('expected_date') <small class="errors">{{ $message }}</small> @enderror
                </div>
                <div class="field full">
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes">{{ old('notes', $purchaseOrder->notes) }}</textarea>
                </div>
            </div>
        </section>
        <section class="card" style="margin-top:18px">
            <div class="page-head"><h2>Order lines</h2><button class="secondary" type="button" id="add-order-line">Add product</button></div>
            <div id="order-lines">
                @foreach ($existingLines as $index => $line)
                    <div class="form-grid order-line">
                        <div class="field">
                            <label>Product</label>
                            <select name="lines[{{ $index }}][product_id]" required>
                                <option value="">Choose product</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}" @selected(($line['product_id'] ?? '') == $product->id)>{{ $product->sku }} — {{ $product->name }} ({{ $product->unit }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label>Ordered quantity</label>
                            <input name="lines[{{ $index }}][ordered_quantity]" type="number" step="0.001" min="0.001" required value="{{ $line['ordered_quantity'] ?? '' }}">
                            <button type="button" class="secondary remove-order-line">Remove line</button>
                        </div>
                    </div>
                @endforeach
            </div>
            <button type="submit">Save as draft</button>
        </section>
    </form>
    <template id="order-line-template">
        <div class="form-grid order-line">
            <div class="field">
                <label>Product</label>
                <select required data-name="product_id">
                    <option value="">Choose product</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}">{{ $product->sku }} — {{ $product->name }} ({{ $product->unit }})</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label>Ordered quantity</label>
                <input type="number" step="0.001" min="0.001" required data-name="ordered_quantity">
                <button type="button" class="secondary remove-order-line">Remove line</button>
            </div>
        </div>
    </template>
@endsection

@push('scripts')
    <script>
        const orderLines = document.getElementById('order-lines');
        let nextOrderLine = {{ count($existingLines) }};
        document.getElementById('add-order-line').addEventListener('click', () => {
            const fragment = document.getElementById('order-line-template').content.cloneNode(true);
            fragment.querySelectorAll('[data-name]').forEach((input) => {
                input.name = `lines[${nextOrderLine}][${input.dataset.name}]`;
            });
            nextOrderLine += 1;
            orderLines.append(fragment);
        });
        orderLines.addEventListener('click', (event) => {
            if (event.target.matches('.remove-order-line') && orderLines.querySelectorAll('.order-line').length > 1) {
                event.target.closest('.order-line').remove();
            }
        });
    </script>
@endpush
