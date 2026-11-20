
@extends('catalog.layout')

@section('title', 'Receive goods')

@section('breadcrumbs')
    <a href="{{ route('goods-receipts.index') }}">Goods Receipts (GRN)</a>
    <span class="sep">/</span>
    <span class="current">Receive Goods</span>
@endsection

@section('content')
    <div class="page-header-card">
        <div class="page-title-wrap">
            <nav class="breadcrumb-trail" aria-label="Breadcrumb">
                <a href="{{ route('dashboard') }}">Dashboard</a>
                <span class="separator">/</span>
                <a href="{{ route('goods-receipts.index') }}">Goods Receipts</a>
                <span class="separator">/</span>
                <span class="current">Receive Goods</span>
            </nav>

            <h1>Receive goods</h1>
            <p>Record incoming vendor deliveries, capture batch details, and prepare stock receipts for warehouse review.</p>
        </div>

        <div class="page-actions-wrap">
            <a class="button secondary" href="{{ route('goods-receipts.index') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/>
                </svg>
                <span>Receipt history</span>
            </a>
        </div>
    </div>

    @php
        $existingLines = old('lines', [[
            'product_id' => '',
            'quantity' => '',
            'batch_number' => '',
            'manufactured_at' => '',
            'expires_at' => '',
            'location_id' => '',
        ]]);

        $purchaseOrderId = old('purchase_order_id', $selectedPurchaseOrder?->id);
        $today = now()->toDateString();
    @endphp

    <form method="POST"
          action="{{ route('goods-receipts.review') }}"
          data-offline-operation="Goods receipt review"
          data-offline-sync-url="{{ route('offline.sync.goods-receipt') }}"
          style="max-width: 960px; margin: 0 auto; display: flex; flex-direction: column; gap: 20px;">

        @csrf

        <input type="hidden" name="client_id" value="{{ (string) \Illuminate\Support\Str::uuid() }}">

        {{-- Card 1: Inbound Order Reference --}}
        <div class="card" style="background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 24px;">

            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 18px; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px;">
                <div style="width: 28px; height: 28px; border-radius: 6px; background: rgba(37, 99, 235, 0.1); color: #2563eb; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px;">1</div>

                <div>
                    <h2 style="font-size: 15px; font-weight: 700; color: #0f172a; margin: 0;">Inbound Order Reference</h2>
                    <p style="font-size: 12px; color: #64748b; margin: 0;">Link an existing purchase order or record a direct vendor shipment.</p>
                </div>
            </div>

            <div class="form-grid">
                <div class="field">
                    <label for="purchase_order_id" style="font-size: 12.5px; font-weight: 600; color: #334155;">
                        Purchase order
                    </label>

                    <select id="purchase_order_id"
                            name="purchase_order_id"
                            style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; font-size: 13.5px;">

                        <option value="">Direct receipt (no purchase order)</option>

                        @foreach ($purchaseOrders as $purchaseOrder)
                            <option value="{{ $purchaseOrder->id }}" @selected($purchaseOrderId == $purchaseOrder->id)>
                                {{ $purchaseOrder->order_number }} — {{ $purchaseOrder->supplier->name }}
                                ({{ ucwords(str_replace('_', ' ', $purchaseOrder->status)) }})
                            </option>
                        @endforeach
                    </select>

                    @error('purchase_order_id')
                        <small class="errors" style="display:block; margin-top:4px; color:#dc2626; font-size:12px;">
                            {{ $message }}
                        </small>
                    @enderror
                </div>

                <div class="field">
                    <label for="supplier_id" style="font-size: 12.5px; font-weight: 600; color: #334155;">
                        Supplier (required for direct receipt)
                    </label>

                    <select id="supplier_id"
                            name="supplier_id"
                            @disabled($purchaseOrderId)
                            @required(!$purchaseOrderId)
                            style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; font-size: 13.5px;">

                        <option value="">Choose supplier</option>

                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>
                                {{ $supplier->name }} ({{ $supplier->code }})
                            </option>
                        @endforeach
                    </select>

                    @error('supplier_id')
                        <small class="errors" style="display:block; margin-top:4px; color:#dc2626; font-size:12px;">
                            {{ $message }}
                        </small>
                    @enderror
                </div>

                <div class="field full">
                    <label for="notes" style="font-size: 12.5px; font-weight: 600; color: #334155;">
                        Receiving notes
                    </label>

                    <textarea id="notes"
                              name="notes"
                              rows="2"
                              maxlength="5000"
                              placeholder="e.g. Delivery truck #UBA 123A, driver delivery sheet signed, seals intact."
                              style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; font-size: 13px;">{{ old('notes') }}</textarea>

                    @error('notes')
                        <small class="errors" style="display:block; margin-top:4px; color:#dc2626; font-size:12px;">
                            {{ $message }}
                        </small>
                    @enderror
                </div>
            </div>

            @if ($selectedPurchaseOrder)
                <div style="margin-top: 18px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                        <h2 style="font-size: 14px; font-weight: 700; color: #0f172a; margin: 0;">
                            Outstanding purchase order lines
                        </h2>

                        <span class="pill-code">{{ $selectedPurchaseOrder->order_number }}</span>
                    </div>

                    <table class="clean-table" style="background: #ffffff; border-radius: 6px; overflow: hidden; border: 1px solid #e2e8f0;">
                        <thead>
                            <tr>
                                <th style="width: 45%;">Product</th>
                                <th style="width: 18%;">Ordered</th>
                                <th style="width: 18%;">Received</th>
                                <th style="width: 19%; text-align: right;">Outstanding</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($selectedPurchaseOrder->lines as $orderLine)
                                <tr>
                                    <td>
                                        <strong style="color: #0f172a;">{{ $orderLine->product->sku }}</strong>
                                        — {{ $orderLine->product->name }}
                                    </td>

                                    <td>{{ $orderLine->ordered_quantity }} {{ $orderLine->product->unit }}</td>
                                    <td>{{ $orderLine->received_quantity }} {{ $orderLine->product->unit }}</td>

                                    <td style="text-align: right; font-weight: 700; color: #2563eb;">
                                        {{ $orderLine->outstandingQuantity() }} {{ $orderLine->product->unit }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div style="margin-top: 12px;">
                        <label style="display: flex; align-items: center; gap: 8px; font-size: 12.5px; font-weight: 500; color: #334155; cursor: pointer; margin: 0;">
                            <input type="checkbox"
                                   name="accept_discrepancies"
                                   value="1"
                                   @checked(old('accept_discrepancies'))
                                   style="width: 16px; height: 16px;">

                            <span>I reviewed and acknowledge any quantity above the PO outstanding amount.</span>
                        </label>

                        <p style="font-size:11px; color:#64748b; margin:6px 0 0 24px;">
                            Only select this when the quantity difference has been reviewed.
                        </p>

                        @error('accept_discrepancies')
                            <small class="errors" style="display:block; margin-top:6px; color:#dc2626; font-size:12px;">
                                {{ $message }}
                            </small>
                        @enderror
                    </div>
                </div>
            @endif
        </div>

        {{-- Card 2: Received Items --}}
        <div class="card" style="background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 24px;">

            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; flex-wrap: wrap; gap: 10px;">

                <div style="display: flex; align-items: center; gap: 8px;">
                    <div style="width: 28px; height: 28px; border-radius: 6px; background: rgba(16, 185, 129, 0.1); color: #10b981; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px;">2</div>

                    <div>
                        <h2 style="font-size: 15px; font-weight: 700; color: #0f172a; margin: 0;">Received items</h2>
                        <p style="font-size: 12px; color: #64748b; margin: 0;">
                            Record batch numbers, manufacturing/expiry dates, quantities, and the Receiving Area for each item.
                        </p>
                    </div>
                </div>

                <button class="button secondary"
                        type="button"
                        id="add-receipt-line"
                        style="padding: 6px 14px; font-size: 12.5px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>Add item</span>
                </button>
            </div>

            <div id="receipt-lines" style="display: flex; flex-direction: column; gap: 14px;">

                @foreach ($existingLines as $index => $line)
                    <div class="receipt-line" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px;">

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; margin-bottom: 12px;">

                            {{-- Product --}}
                            <div class="field" style="margin: 0;">
                                <label style="font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 4px; display: block;">
                                    Product <span style="color: #ef4444;">*</span>
                                </label>

                                <select name="lines[{{ $index }}][product_id]"
                                        required
                                        style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 7px 10px; font-size: 13px; background: #ffffff;">
                                    <option value="">Choose product</option>

                                    @foreach ($products as $product)
                                        <option value="{{ $product->id }}" @selected(($line['product_id'] ?? '') == $product->id)>
                                            {{ $product->sku }} — {{ $product->name }} ({{ $product->unit }})
                                        </option>
                                    @endforeach
                                </select>

                                @error("lines.$index.product_id")
                                    <small class="errors" style="display:block; margin-top:4px; color:#dc2626; font-size:12px;">
                                        {{ $message }}
                                    </small>
                                @enderror
                            </div>

                            {{-- Quantity --}}
                            <div class="field" style="margin: 0;">
                                <label style="font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 4px; display: block;">
                                    Received quantity <span style="color: #ef4444;">*</span>
                                </label>

                                <input name="lines[{{ $index }}][quantity]"
                                       type="number"
                                       step="0.001"
                                       min="0.001"
                                       required
                                       value="{{ $line['quantity'] ?? '' }}"
                                       placeholder="0.000"
                                       style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 7px 10px; font-size: 13px; font-weight: 600; background: #ffffff;">

                                @error("lines.$index.quantity")
                                    <small class="errors" style="display:block; margin-top:4px; color:#dc2626; font-size:12px;">
                                        {{ $message }}
                                    </small>
                                @enderror
                            </div>

                            {{-- Batch Number --}}
                            <div class="field" style="margin: 0;">
                                <label style="font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 4px; display: block;">
                                    Batch number <span style="color: #ef4444;">*</span>
                                </label>

                                <input name="lines[{{ $index }}][batch_number]"
                                       maxlength="100"
                                       required
                                       value="{{ $line['batch_number'] ?? '' }}"
                                       placeholder="e.g. BATCH-2025-01"
                                       style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 7px 10px; font-size: 13px; font-family: monospace; font-weight: 600; background: #ffffff;">

                                @error("lines.$index.batch_number")
                                    <small class="errors" style="display:block; margin-top:4px; color:#dc2626; font-size:12px;">
                                        {{ $message }}
                                    </small>
                                @enderror
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; margin-bottom: 12px;">

                            {{-- Manufacture Date --}}
                            <div class="field" style="margin: 0;">
                                <label style="font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 4px; display: block;">
                                    Manufacture date
                                </label>

                                <input name="lines[{{ $index }}][manufactured_at]"
                                       type="date"
                                       max="{{ $today }}"
                                       value="{{ $line['manufactured_at'] ?? '' }}"
                                       style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 7px 10px; font-size: 13px; background: #ffffff;">

                                @error("lines.$index.manufactured_at")
                                    <small class="errors" style="display:block; margin-top:4px; color:#dc2626; font-size:12px;">
                                        {{ $message }}
                                    </small>
                                @enderror
                            </div>

                            {{-- Expiry Date --}}
                            <div class="field" style="margin: 0;">
                                <label style="font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 4px; display: block;">
                                    Expiry date
                                </label>

                                <input name="lines[{{ $index }}][expires_at]"
                                       type="date"
                                       min="{{ $today }}"
                                       value="{{ $line['expires_at'] ?? '' }}"
                                       style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 7px 10px; font-size: 13px; background: #ffffff;">

                                @error("lines.$index.expires_at")
                                    <small class="errors" style="display:block; margin-top:4px; color:#dc2626; font-size:12px;">
                                        {{ $message }}
                                    </small>
                                @enderror
                            </div>

                            {{-- Receiving Area --}}
                            <div class="field" style="margin: 0;">
                                <label style="font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 4px; display: block;">
                                    Receiving Area <span style="color: #ef4444;">*</span>
                                </label>

                                <select name="lines[{{ $index }}][location_id]"
                                        required
                                        style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 7px 10px; font-size: 13px; background: #ffffff;">

                                    <option value="">Choose Receiving Area</option>

                                    @foreach ($receivingLocations as $receivingLocation)
                                        <option value="{{ $receivingLocation->id }}"
                                            @selected(($line['location_id'] ?? ($index === 0 ? $selectedLocationId : '')) == $receivingLocation->id)>
                                            {{ $receivingLocation->name }} ({{ $receivingLocation->code }})
                                        </option>
                                    @endforeach
                                </select>

                                @error("lines.$index.location_id")
                                    <small class="errors" style="display:block; margin-top:4px; color:#dc2626; font-size:12px;">
                                        {{ $message }}
                                    </small>
                                @enderror
                            </div>
                        </div>

                        <div style="display: flex; justify-content: flex-end; padding-top: 8px; border-top: 1px dashed #e2e8f0;">
                            <button type="button"
                                    class="button secondary remove-receipt-line"
                                    style="padding: 5px 12px; font-size: 12px; color: #ef4444; border-color: #fecaca; background: #ffffff;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                </svg>
                                <span>Remove item</span>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            @error('lines')
                <small class="errors" style="display:block; margin-top:8px; color:#dc2626;">
                    {{ $message }}
                </small>
            @enderror

        </div>

        {{-- Action Footer --}}
        <div style="background: #ffffff; border-radius: 10px; border: 1px solid #e2e8f0; padding: 18px 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;">

            <span style="font-size: 12.5px; color: #64748b;">
                When offline, this receipt is queued on this device for server review. No stock changes until a separate confirmation step.
            </span>

            <div style="display: flex; align-items: center; gap: 10px;">
                <a class="button secondary"
                   href="{{ route('goods-receipts.index') }}"
                   style="padding: 9px 18px;">
                    Cancel
                </a>

                <button type="submit"
                        class="button"
                        style="background: #2563eb; color: #ffffff; padding: 9px 24px; font-weight: 600;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    <span>Review receipt</span>
                </button>
            </div>
        </div>
    </form>

    {{-- Template for additional receipt items --}}
    <template id="receipt-line-template">
        <div class="receipt-line" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px;">

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; margin-bottom: 12px;">

                <div class="field" style="margin: 0;">
                    <label style="font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 4px; display: block;">
                        Product <span style="color: #ef4444;">*</span>
                    </label>

                    <select required
                            data-name="product_id"
                            style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 7px 10px; font-size: 13px; background: #ffffff;">
                        <option value="">Choose product</option>

                        @foreach ($products as $product)
                            <option value="{{ $product->id }}">
                                {{ $product->sku }} — {{ $product->name }} ({{ $product->unit }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="field" style="margin: 0;">
                    <label style="font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 4px; display: block;">
                        Received quantity <span style="color: #ef4444;">*</span>
                    </label>

                    <input type="number"
                           step="0.001"
                           min="0.001"
                           required
                           data-name="quantity"
                           placeholder="0.000"
                           style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 7px 10px; font-size: 13px; font-weight: 600; background: #ffffff;">
                </div>

                <div class="field" style="margin: 0;">
                    <label style="font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 4px; display: block;">
                        Batch number <span style="color: #ef4444;">*</span>
                    </label>

                    <input maxlength="100"
                           required
                           data-name="batch_number"
                           placeholder="e.g. BATCH-2025-01"
                           style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 7px 10px; font-size: 13px; font-family: monospace; font-weight: 600; background: #ffffff;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; margin-bottom: 12px;">

                <div class="field" style="margin: 0;">
                    <label style="font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 4px; display: block;">
                        Manufacture date
                    </label>

                    <input type="date"
                           data-name="manufactured_at"
                           max="{{ $today }}"
                           style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 7px 10px; font-size: 13px; background: #ffffff;">
                </div>

                <div class="field" style="margin: 0;">
                    <label style="font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 4px; display: block;">
                        Expiry date
                    </label>

                    <input type="date"
                           data-name="expires_at"
                           min="{{ $today }}"
                           style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 7px 10px; font-size: 13px; background: #ffffff;">
                </div>

                <div class="field" style="margin: 0;">
                    <label style="font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 4px; display: block;">
                        Receiving Area <span style="color: #ef4444;">*</span>
                    </label>

                    <select required
                            data-name="location_id"
                            style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 7px 10px; font-size: 13px; background: #ffffff;">

                        <option value="">Choose Receiving Area</option>

                        @foreach ($receivingLocations as $receivingLocation)
                            <option value="{{ $receivingLocation->id }}">
                                {{ $receivingLocation->name }} ({{ $receivingLocation->code }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; padding-top: 8px; border-top: 1px dashed #e2e8f0;">
                <button type="button"
                        class="button secondary remove-receipt-line"
                        style="padding: 5px 12px; font-size: 12px; color: #ef4444; border-color: #fecaca; background: #ffffff;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                    <span>Remove item</span>
                </button>
            </div>
        </div>
    </template>
@endsection

@push('scripts')
    <script>
        const receiptLines = document.getElementById('receipt-lines');
        const receiptForm = document.querySelector('form[data-offline-operation]');
        const addReceiptLineButton = document.getElementById('add-receipt-line');
        const purchaseOrderSelect = document.getElementById('purchase_order_id');
        const supplierSelect = document.getElementById('supplier_id');

        let nextReceiptLine = {{ count($existingLines) }};

        // Use the browser's local calendar date.
        function getLocalDate() {
            const date = new Date();
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');

            return `${year}-${month}-${day}`;
        }

        // Configure date validation for one receipt line.
        function configureReceiptDates(line) {
            const manufacture = line.querySelector('[name$="[manufactured_at]"]');
            const expiry = line.querySelector('[name$="[expires_at]"]');

            if (!manufacture || !expiry) return;

            function updateDateLimits() {
                const today = getLocalDate();

                manufacture.max = today;

                expiry.min = manufacture.value && manufacture.value > today
                    ? today
                    : manufacture.value || today;

                manufacture.setCustomValidity('');
                expiry.setCustomValidity('');

                if (manufacture.value && manufacture.value > today) {
                    manufacture.setCustomValidity(
                        'Manufacture date cannot be in the future.'
                    );
                }

                if (expiry.value && expiry.value < today) {
                    expiry.setCustomValidity(
                        'Expiry date cannot be earlier than today.'
                    );
                } else if (
                    manufacture.value &&
                    expiry.value &&
                    expiry.value < manufacture.value
                ) {
                    expiry.setCustomValidity(
                        'Expiry date cannot be earlier than the manufacture date.'
                    );
                }
            }

            manufacture.addEventListener('change', updateDateLimits);
            expiry.addEventListener('change', updateDateLimits);

            updateDateLimits();
        }

        // Configure existing receipt lines.
        receiptLines.querySelectorAll('.receipt-line').forEach(configureReceiptDates);

        // Add another received item.
        addReceiptLineButton.addEventListener('click', () => {
            const fragment = document
                .getElementById('receipt-line-template')
                .content.cloneNode(true);

            fragment.querySelectorAll('[data-name]').forEach((input) => {
                input.name = `lines[${nextReceiptLine}][${input.dataset.name}]`;
            });

            const newLine = fragment.querySelector('.receipt-line');

            nextReceiptLine++;

            receiptLines.append(fragment);

            configureReceiptDates(newLine);
        });

        // Remove an item, but always keep at least one item.
        receiptLines.addEventListener('click', (event) => {
            const button = event.target.closest('.remove-receipt-line');

            if (
                button &&
                receiptLines.querySelectorAll('.receipt-line').length > 1
            ) {
                button.closest('.receipt-line').remove();
            }
        });

        // Enable supplier selection only for direct receipts.
        function updateSupplierAvailability() {
            const hasPurchaseOrder = purchaseOrderSelect.value !== '';

            supplierSelect.disabled = hasPurchaseOrder;
            supplierSelect.required = !hasPurchaseOrder;

            if (hasPurchaseOrder) {
                supplierSelect.value = '';
            }
        }

        purchaseOrderSelect.addEventListener('change', updateSupplierAvailability);
        updateSupplierAvailability();
    </script>
@endpush
