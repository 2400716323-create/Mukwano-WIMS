@extends('catalog.layout')

@section('title', $purchaseOrder->exists ? 'Edit purchase order' : 'Create purchase order')

@section('breadcrumbs')
    <a href="{{ route('purchase-orders.index') }}">Purchase Orders</a>
    <span class="sep">/</span>
    @if ($purchaseOrder->exists)
        <a href="{{ route('purchase-orders.show', $purchaseOrder) }}">{{ $purchaseOrder->order_number }}</a>
        <span class="sep">/</span>
        <span class="current">Edit</span>
    @else
        <span class="current">New Order</span>
    @endif
@endsection

@section('content')
    <!-- Unified Page Header -->
    <div class="page-header-card">
        <div class="page-title-wrap">
            <nav class="breadcrumb-trail" aria-label="Breadcrumb">
                <a href="{{ route('dashboard') }}">Dashboard</a>
                <span class="separator">/</span>
                <a href="{{ route('purchase-orders.index') }}">Purchase Orders</a>
                <span class="separator">/</span>
                <span class="current">{{ $purchaseOrder->exists ? 'Edit '.$purchaseOrder->order_number : 'New Purchase Order' }}</span>
            </nav>
            <h1>{{ $purchaseOrder->exists ? 'Edit '.$purchaseOrder->order_number : 'Create purchase order' }}</h1>
            <p>{{ $purchaseOrder->exists ? 'Update supplier order lines and delivery dates.' : 'Authorize a new inventory procurement order with line items.' }}</p>
        </div>
        <div class="page-actions-wrap">
            <a class="button secondary" href="{{ $purchaseOrder->exists ? route('purchase-orders.show', $purchaseOrder) : route('purchase-orders.index') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                <span>Cancel</span>
            </a>
        </div>
    </div>

    @php
        $existingLines = old('lines', $purchaseOrder->exists
            ? $purchaseOrder->lines->map(fn ($line) => ['product_id' => $line->product_id, 'ordered_quantity' => $line->ordered_quantity])->all()
            : [['product_id' => '', 'ordered_quantity' => '']]);
    @endphp

    <form method="POST" action="{{ $purchaseOrder->exists ? route('purchase-orders.update', $purchaseOrder) : route('purchase-orders.store') }}" style="max-width: 900px; margin: 0 auto; display: flex; flex-direction: column; gap: 20px;">
        @csrf
        @if ($purchaseOrder->exists)
            @method('PUT')
        @endif

        <!-- Card 1: Vendor & Schedule Particulars -->
        <div class="card" style="background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 24px;">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 18px; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px;">
                <div style="width: 28px; height: 28px; border-radius: 6px; background: rgba(37, 99, 235, 0.1); color: #2563eb; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px;">1</div>
                <div>
                    <h2 style="font-size: 15px; font-weight: 700; color: #0f172a; margin: 0;">Order Header & Supplier</h2>
                    <p style="font-size: 12px; color: #64748b; margin: 0;">Select vendor account, order issuance date, and expected delivery schedule</p>
                </div>
            </div>

            <div class="form-grid">
                <div class="field full">
                    <label for="supplier_id" style="font-size: 12.5px; font-weight: 600; color: #334155;">Supplier / Vendor <span style="color: #ef4444;">*</span></label>
                    <select id="supplier_id" name="supplier_id" required style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; font-size: 13.5px;">
                        <option value="">Choose supplier</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected(old('supplier_id', $purchaseOrder->supplier_id) == $supplier->id)>{{ $supplier->name }} ({{ $supplier->code }})</option>
                        @endforeach
                    </select>
                    @error('supplier_id') <small class="errors" style="display:block; margin-top:4px; color: #dc2626; font-size: 12px;">{{ $message }}</small> @enderror
                </div>
                <div class="field">
                    <label for="order_date" style="font-size: 12.5px; font-weight: 600; color: #334155;">Order Date <span style="color: #ef4444;">*</span></label>
                    <input id="order_date" name="order_date" type="date" required value="{{ old('order_date', $purchaseOrder->order_date?->toDateString() ?? today()->toDateString()) }}"
                           style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; font-size: 13.5px;">
                    @error('order_date') <small class="errors" style="display:block; margin-top:4px; color: #dc2626; font-size: 12px;">{{ $message }}</small> @enderror
                </div>
                <div class="field">
                    <label for="expected_date" style="font-size: 12.5px; font-weight: 600; color: #334155;">Expected Delivery Date</label>
                    <input id="expected_date" name="expected_date" type="date" value="{{ old('expected_date', $purchaseOrder->expected_date?->toDateString()) }}"
                           style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; font-size: 13.5px;">
                    @error('expected_date') <small class="errors" style="display:block; margin-top:4px; color: #dc2626; font-size: 12px;">{{ $message }}</small> @enderror
                </div>
                <div class="field full">
                    <label for="notes" style="font-size: 12.5px; font-weight: 600; color: #334155;">Purchase Order Notes & Terms</label>
                    <textarea id="notes" name="notes" rows="2" placeholder="e.g. Approved under supply contract #2025-Q2. Notify warehouse receiver on arrival."
                              style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; font-size: 13px;">{{ old('notes', $purchaseOrder->notes) }}</textarea>
                </div>
            </div>
        </div>

        <!-- Card 2: Order Line Items -->
        <div class="card" style="background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 24px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; flex-wrap: wrap; gap: 10px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <div style="width: 28px; height: 28px; border-radius: 6px; background: rgba(16, 185, 129, 0.1); color: #10b981; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px;">2</div>
                    <div>
                        <h2 style="font-size: 15px; font-weight: 700; color: #0f172a; margin: 0;">Order lines</h2>
                        <p style="font-size: 12px; color: #64748b; margin: 0;">Select catalog items and specify exact procurement volumes</p>
                    </div>
                </div>
                <button class="button secondary" type="button" id="add-order-line" style="padding: 6px 14px; font-size: 12.5px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    <span>Add product</span>
                </button>
            </div>

            <div id="order-lines" style="display: flex; flex-direction: column; gap: 12px;">
                @foreach ($existingLines as $index => $line)
                    <div class="order-line" style="display: grid; grid-template-columns: minmax(0, 1.8fr) minmax(0, 1fr) auto; gap: 12px; align-items: flex-end; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px;">
                        <div>
                            <label style="font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 4px; display: block;">Product SKU & Title <span style="color: #ef4444;">*</span></label>
                            <select name="lines[{{ $index }}][product_id]" required style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 7px 10px; font-size: 13px; background: #ffffff;">
                                <option value="">Choose product</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}" @selected(($line['product_id'] ?? '') == $product->id)>{{ $product->sku }} — {{ $product->name }} ({{ $product->unit }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label style="font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 4px; display: block;">Ordered quantity <span style="color: #ef4444;">*</span></label>
                            <input name="lines[{{ $index }}][ordered_quantity]" type="number" step="0.001" min="0.001" required value="{{ $line['ordered_quantity'] ?? '' }}"
                                   placeholder="0.000"
                                   style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 7px 10px; font-size: 13px; font-weight: 600; background: #ffffff;">
                        </div>
                        <button type="button" class="button secondary remove-order-line" style="padding: 7px 12px; color: #ef4444; border-color: #fecaca; background: #ffffff;" title="Remove line">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                            <span>Remove line</span>
                        </button>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Action Footer -->
        <div style="background: #ffffff; border-radius: 10px; border: 1px solid #e2e8f0; padding: 16px 24px; display: flex; align-items: center; justify-content: flex-end; gap: 12px;">
            <a class="button secondary" href="{{ $purchaseOrder->exists ? route('purchase-orders.show', $purchaseOrder) : route('purchase-orders.index') }}" style="padding: 9px 20px;">Cancel</a>
            <button type="submit" class="button" style="background: #2563eb; color: #ffffff; padding: 9px 24px; font-weight: 600;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                <span>Save as draft</span>
            </button>
        </div>
    </form>

    <template id="order-line-template">
        <div class="order-line" style="display: grid; grid-template-columns: minmax(0, 1.8fr) minmax(0, 1fr) auto; gap: 12px; align-items: flex-end; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px;">
            <div>
                <label style="font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 4px; display: block;">Product SKU & Title <span style="color: #ef4444;">*</span></label>
                <select required data-name="product_id" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 7px 10px; font-size: 13px; background: #ffffff;">
                    <option value="">Choose product</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}">{{ $product->sku }} — {{ $product->name }} ({{ $product->unit }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 4px; display: block;">Ordered quantity <span style="color: #ef4444;">*</span></label>
                <input type="number" step="0.001" min="0.001" required data-name="ordered_quantity" placeholder="0.000"
                       style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 7px 10px; font-size: 13px; font-weight: 600; background: #ffffff;">
            </div>
            <button type="button" class="button secondary remove-order-line" style="padding: 7px 12px; color: #ef4444; border-color: #fecaca; background: #ffffff;" title="Remove line">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                <span>Remove line</span>
            </button>
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
            const btn = event.target.closest('.remove-order-line');
            if (btn && orderLines.querySelectorAll('.order-line').length > 1) {
                btn.closest('.order-line').remove();
            }
        });
    </script>
@endpush
