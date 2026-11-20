@extends('catalog.layout')

@section('content')
<div class="container-fluid">

    <div class="page-header-card">
        <div>
            <h2>New Manual Stock Adjustment</h2>
            <p>Submit a request to increase or decrease stock.</p>
        </div>

        <a href="{{ route('manual-stock-adjustments.index') }}" class="button">
            Back
        </a>
    </div>

    <div class="card">
        <div class="alert alert-info">
            Submitting this form does not immediately change stock.
            A warehouse manager or administrator must approve the request.
        </div>

        @if($errors->any())
            <div class="alert alert-danger">
                <strong>Please correct the following errors:</strong>
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('manual-stock-adjustments.store') }}">
            @csrf

            <div class="form-group">
                <label for="product_id">Product</label>
                <select name="product_id" id="product_id" class="form-control" required>
                    <option value="">Select product</option>

                    @foreach($products as $product)
                        <option value="{{ $product->id }}"
                            {{ old('product_id') == $product->id ? 'selected' : '' }}>
                            {{ $product->sku }} - {{ $product->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="batch_id">Batch</label>
                <select name="batch_id" id="batch_id" class="form-control" required>
                    <option value="">Select batch</option>

                    @foreach($batches as $batch)
                        <option value="{{ $batch->id }}"
                            data-product="{{ $batch->product_id }}"
                            {{ old('batch_id') == $batch->id ? 'selected' : '' }}>
                            {{ $batch->batch_number }}
                            - {{ $batch->product->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="location_id">Storage Bin</label>
                <select name="location_id" id="location_id" class="form-control" required>
                    <option value="">Select bin</option>

                    @foreach($locations as $location)
                        <option value="{{ $location->id }}"
                            {{ old('location_id') == $location->id ? 'selected' : '' }}>
                            {{ $location->code }} - {{ $location->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="direction">Adjustment Direction</label>
                <select name="direction" id="direction" class="form-control" required>
                    <option value="">Select direction</option>
                    <option value="increase" {{ old('direction') === 'increase' ? 'selected' : '' }}>
                        Increase stock
                    </option>
                    <option value="decrease" {{ old('direction') === 'decrease' ? 'selected' : '' }}>
                        Decrease stock
                    </option>
                </select>
            </div>

            <div class="form-group">
                <label for="adjustment_quantity">Adjustment Quantity</label>
                <input
                    type="number"
                    name="adjustment_quantity"
                    id="adjustment_quantity"
                    class="form-control"
                    min="0.001"
                    max="999999999.999"
                    step="0.001"
                    value="{{ old('adjustment_quantity') }}"
                    required
                >
            </div>

            <div class="form-group">
                <label for="reason">Reason for Adjustment</label>
                <textarea
                    name="reason"
                    id="reason"
                    class="form-control"
                    rows="4"
                    minlength="5"
                    maxlength="2000"
                    required
                >{{ old('reason') }}</textarea>
            </div>

            <button type="submit" class="button">
                Submit Adjustment Request
            </button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const productSelect = document.getElementById('product_id');
    const batchSelect = document.getElementById('batch_id');

    function filterBatches() {
        const selectedProduct = productSelect.value;

        Array.from(batchSelect.options).forEach(function (option) {
            if (!option.value) {
                option.hidden = false;
                return;
            }

            option.hidden = option.dataset.product !== selectedProduct;
        });

        const selectedOption = batchSelect.selectedOptions[0];

        if (selectedOption && selectedOption.hidden) {
            batchSelect.value = '';
        }
    }

    productSelect.addEventListener('change', filterBatches);
    filterBatches();
});
</script>
@endsection