@extends('catalog.layout')

@section('content')
<div class="container-fluid">

    <div class="page-header-card">
        <div>
            <h2>Adjustment Request ADJ-{{ $adjustment->id }}</h2>
            <p>View adjustment details and approval status.</p>
        </div>

        <a href="{{ route('manual-stock-adjustments.index') }}" class="button">
            Back to Requests
        </a>
    </div>

    @if(session('status'))
        <div class="alert alert-success">
            {{ session('status') }}
        </div>
    @endif

    <div class="card">
        <h3>Request Information</h3>

        <div class="detail-grid">
            <div>
                <strong>Product</strong>
                <p>{{ $adjustment->product->sku }} - {{ $adjustment->product->name }}</p>
            </div>

            <div>
                <strong>Batch Number</strong>
                <p>{{ $adjustment->batch->batch_number }}</p>
            </div>

            <div>
                <strong>Storage Bin</strong>
                <p>{{ $adjustment->location->code }} - {{ $adjustment->location->name }}</p>
            </div>

            <div>
                <strong>Direction</strong>
                <p>{{ ucfirst($adjustment->direction) }}</p>
            </div>

            <div>
                <strong>Adjustment Quantity</strong>
                <p>{{ number_format((float) $adjustment->adjustment_quantity, 3) }}</p>
            </div>

            <div>
                <strong>Quantity at Request</strong>
                <p>{{ number_format((float) $adjustment->current_quantity, 3) }}</p>
            </div>

            <div>
                <strong>Expected Quantity After Approval</strong>
                <p>
                    {{ number_format(
                        (float) $adjustment->current_quantity
                        + ($adjustment->direction === 'increase'
                            ? (float) $adjustment->adjustment_quantity
                            : -(float) $adjustment->adjustment_quantity),
                        3
                    ) }}
                </p>
            </div>

            <div>
                <strong>Requested By</strong>
                <p>{{ $adjustment->requester->name }}</p>
            </div>

            <div>
                <strong>Date Requested</strong>
                <p>{{ $adjustment->created_at->format('d M Y H:i') }}</p>
            </div>

            <div>
                <strong>Status</strong>
                <p>{{ ucfirst($adjustment->status) }}</p>
            </div>

            <div>
                <strong>Reviewed By</strong>
                <p>{{ $adjustment->reviewer?->name ?? 'Not reviewed' }}</p>
            </div>

            <div>
                <strong>Review Date</strong>
                <p>{{ $adjustment->reviewed_at?->format('d M Y H:i') ?? 'Not reviewed' }}</p>
            </div>
        </div>

        <hr>

        <strong>Reason for Adjustment</strong>
        <p>{{ $adjustment->reason }}</p>

        @if($adjustment->review_note)
            <strong>Review Note</strong>
            <p>{{ $adjustment->review_note }}</p>
        @endif
    </div>

    @if($canReview)
        <div class="card">
            <h3>Review Adjustment Request</h3>

            <p>Approve or reject this request. Approval will update the stock balances.</p>

            <form
                method="POST"
                action="{{ route('manual-stock-adjustments.review', $adjustment) }}"
                id="reviewForm"
            >
                @csrf

                <div class="form-group">
                    <label for="decision">Decision</label>
                    <select name="decision" id="decision" class="form-control" required>
                        <option value="">Select decision</option>
                        <option value="approved">Approve</option>
                        <option value="rejected">Reject</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="reason">Review Note (required when rejecting)</label>
                    <textarea
                        name="reason"
                        id="reviewReason"
                        class="form-control"
                        rows="3"
                        maxlength="2000"
                    ></textarea>
                </div>

                <button type="submit" class="button">
                    Submit Review
                </button>
            </form>
        </div>
    @endif

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('reviewForm');

    if (!form) return;

    form.addEventListener('submit', function (event) {
        const decision = document.getElementById('decision').value;
        const reason = document.getElementById('reviewReason').value.trim();

        if (decision === 'rejected' && reason.length === 0) {
            event.preventDefault();
            alert('Please provide a reason for rejecting this request.');
        }
    });
});
</script>
@endsection