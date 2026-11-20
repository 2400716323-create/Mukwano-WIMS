
@extends('catalog.layout')

@section('content')
<div class="container-fluid">

    <div class="page-header-card">
        <div>
            <h1>Manual Stock Adjustments</h1>
            <p>Request, monitor and review manual stock adjustments.</p>
        </div>

        @can('create', \App\Models\ManualStockAdjustment::class)
            <a href="{{ route('manual-stock-adjustments.create') }}" class="button">
                + New Adjustment
            </a>
        @endcan
    </div>

    <div class="stat-grid-4">
        <div class="card">
            <h3>Total Requests</h3>
            <h2>{{ $metrics['total'] }}</h2>
        </div>

        <div class="card">
            <h3>Pending</h3>
            <h2>{{ $metrics['pending'] }}</h2>
        </div>

        <div class="card">
            <h3>Approved</h3>
            <h2>{{ $metrics['approved'] }}</h2>
        </div>

        <div class="card">
            <h3>Rejected</h3>
            <h2>{{ $metrics['rejected'] }}</h2>
        </div>
    </div>

    <div class="modern-table-card">
        <h3>Adjustment Requests</h3>

        <div class="table-responsive">
            <table class="clean-table">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Product</th>
                        <th>Batch</th>
                        <th>Bin</th>
                        <th>Direction</th>
                        <th>Quantity</th>
                        <th>Requested By</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($adjustments as $adjustment)
                        <tr>
                            <td>ADJ-{{ $adjustment->id }}</td>

                            <td>
                                {{ $adjustment->product->sku }}
                                <br>
                                {{ $adjustment->product->name }}
                            </td>

                            <td>{{ $adjustment->batch->batch_number }}</td>

                            <td>{{ $adjustment->location->code }}</td>

                            <td>{{ ucfirst($adjustment->direction) }}</td>

                            <td>
                                {{ number_format((float) $adjustment->adjustment_quantity, 3) }}
                            </td>

                            <td>{{ $adjustment->requester->name }}</td>

                            <td>
                                @if($adjustment->status === 'approved')
                                    <span class="badge-success">Approved</span>
                                @elseif($adjustment->status === 'rejected')
                                    <span class="badge-danger">Rejected</span>
                                @else
                                    <span class="badge-warning">Pending</span>
                                @endif
                            </td>

                            <td>
                                <a href="{{ route('manual-stock-adjustments.show', $adjustment) }}"
                                   class="button">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">
                                No manual stock adjustment requests found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $adjustments->links() }}
    </div>
</div>
@endsection
