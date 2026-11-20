@extends('catalog.layout')

@section('title', 'Stock Keeper Workspace — Mukwano WIMS')

@section('content')
    <div class="page-header-card" style="margin-bottom:18px;">
        <div class="page-title-wrap">
            <h1>Stock Keeper Workspace</h1>
            <p>Record stock movements, manage warehouse put-away and monitor daily warehouse activity.</p>
        </div>
        <div class="page-actions-wrap">
            <a class="button secondary" href="{{ route('inventory.ledger') }}">View Stock Ledger</a>
        </div>
    </div>

    @if (session('status'))
        <div class="card" style="padding:12px 16px;margin-bottom:16px;border-left:4px solid #16a34a;color:#166534;">{{ session('status') }}</div>
    @endif

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:14px;margin-bottom:20px;">
        @php
            $cards = [
                ['label' => 'Available Stock', 'value' => number_format($metrics['available_units'], 3), 'note' => 'Units currently in inventory', 'url' => route('inventory.index'), 'color' => '#2563eb'],
                ['label' => 'Incoming · 30 days', 'value' => number_format($metrics['incoming_units'], 3), 'note' => 'Units received and posted', 'url' => route('goods-receipts.index'), 'color' => '#059669'],
                ['label' => 'Outgoing · 30 days', 'value' => number_format($metrics['outgoing_units'], 3), 'note' => 'Units dispatched and posted', 'url' => route('dispatch-requests.index'), 'color' => '#d97706'],
                ['label' => 'Pending Dispatches', 'value' => number_format($metrics['pending_dispatches']), 'note' => 'Requests awaiting dispatch', 'url' => route('dispatch-requests.index'), 'color' => '#7c3aed'],
                ['label' => 'Put-away Stock Lines', 'value' => number_format($metrics['put_away_lines']), 'note' => 'Positive stock balance lines', 'url' => route('put-away.index'), 'color' => '#0891b2'],
                ['label' => 'Critical / High Alerts', 'value' => number_format($metrics['open_alerts']), 'note' => 'Active high-priority alerts', 'url' => route('alerts.index'), 'color' => '#dc2626'],
            ];
        @endphp
        @foreach ($cards as $card)
            <a href="{{ $card['url'] }}" class="card" style="display:block;padding:17px 18px;border-top:3px solid {{ $card['color'] }};color:inherit;text-decoration:none;">
                <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;">{{ $card['label'] }}</div>
                <div style="font-size:25px;font-weight:800;color:#0f172a;margin:8px 0 3px;font-variant-numeric:tabular-nums;">{{ $card['value'] }}</div>
                <div style="font-size:11px;color:#64748b;">{{ $card['note'] }}</div>
            </a>
        @endforeach
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px;margin-bottom:20px;">
        <a href="{{ route('goods-receipts.create') }}" class="card" style="padding:20px;color:inherit;text-decoration:none;">
            <div style="font-size:24px;color:#059669;margin-bottom:8px;">＋</div>
            <h2 style="font-size:15px;margin:0 0 5px;">Record Incoming Stock</h2>
            <p style="font-size:12px;color:#64748b;margin:0 0 12px;">Receive goods, capture supplier and batch details, expiry dates and storage location.</p>
            <span style="font-size:12px;font-weight:700;color:#2563eb;">Open goods receiving →</span>
        </a>
        <a href="{{ route('dispatch-requests.create') }}" class="card" style="padding:20px;color:inherit;text-decoration:none;">
            <div style="font-size:24px;color:#d97706;margin-bottom:8px;">↗</div>
            <h2 style="font-size:15px;margin:0 0 5px;">Record Outgoing Stock</h2>
            <p style="font-size:12px;color:#64748b;margin:0 0 12px;">Create a dispatch request and use the FEFO pick list to issue available stock safely.</p>
            <span style="font-size:12px;font-weight:700;color:#2563eb;">Create dispatch →</span>
        </a>
        <a href="{{ route('put-away.index') }}" class="card" style="padding:20px;color:inherit;text-decoration:none;">
            <div style="font-size:24px;color:#0891b2;margin-bottom:8px;">▦</div>
            <h2 style="font-size:15px;margin:0 0 5px;">Manage Put-away</h2>
            <p style="font-size:12px;color:#64748b;margin:0 0 12px;">Move stock between operational warehouse bins and maintain location balances.</p>
            <span style="font-size:12px;font-weight:700;color:#2563eb;">Open put-away →</span>
        </a>
        <a href="{{ route('inventory.ledger') }}" class="card" style="padding:20px;color:inherit;text-decoration:none;">
            <div style="font-size:24px;color:#7c3aed;margin-bottom:8px;">≡</div>
            <h2 style="font-size:15px;margin:0 0 5px;">Stock Movement History</h2>
            <p style="font-size:12px;color:#64748b;margin:0 0 12px;">Review receipts, dispatches, transfers, adjustments and responsible users.</p>
            <span style="font-size:12px;font-weight:700;color:#2563eb;">View movement ledger →</span>
        </a>
    </div>

    <div class="card" style="padding:18px;margin-bottom:18px;">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:12px;">
            <div>
                <h2 style="font-size:15px;margin:0;">Recent Stock Activity</h2>
                <p style="font-size:11px;color:#64748b;margin:3px 0 0;">Latest movements recorded by the system.</p>
            </div>
            <a href="{{ route('inventory.ledger') }}" style="font-size:12px;font-weight:700;">Full ledger →</a>
        </div>
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:12px;min-width:700px;">
                <thead><tr style="text-align:left;color:#64748b;border-bottom:1px solid #e2e8f0;">
                    <th style="padding:9px 8px;">Date</th><th style="padding:9px 8px;">Product / Batch</th><th style="padding:9px 8px;">Movement</th><th style="padding:9px 8px;">Quantity</th><th style="padding:9px 8px;">Location</th><th style="padding:9px 8px;">Recorded by</th>
                </tr></thead>
                <tbody>
                @forelse ($recentMovements as $movement)
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:10px 8px;white-space:nowrap;">{{ $movement->moved_at?->format('d M Y H:i') ?? '—' }}</td>
                        <td style="padding:10px 8px;"><strong>{{ $movement->batch?->product?->name ?? 'Unknown product' }}</strong><br><span style="color:#64748b;">{{ $movement->batch?->batch_number ?? 'No batch' }}</span></td>
                        <td style="padding:10px 8px;">{{ ucwords(str_replace('_', ' ', $movement->movement_type)) }}</td>
                        <td style="padding:10px 8px;font-weight:700;">{{ number_format((float) $movement->quantity, 3) }}</td>
                        <td style="padding:10px 8px;">{{ $movement->fromLocation?->code ?? '—' }} → {{ $movement->toLocation?->code ?? '—' }}</td>
                        <td style="padding:10px 8px;">{{ $movement->performer?->name ?? 'System / legacy' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="padding:24px;text-align:center;color:#64748b;">No stock movements have been recorded yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:14px;">
        <div class="card" style="padding:18px;">
            <h2 style="font-size:14px;margin:0 0 12px;">My Recent Goods Receipts</h2>
            @forelse ($myReceipts as $receipt)
                <div style="display:flex;justify-content:space-between;gap:10px;padding:9px 0;border-bottom:1px solid #f1f5f9;font-size:12px;">
                    <span><strong>{{ $receipt->receipt_number ?? 'GRN-'.$receipt->id }}</strong><br><span style="color:#64748b;">{{ $receipt->supplier?->name ?? 'No supplier' }}</span></span>
                    <span style="color:#64748b;white-space:nowrap;">{{ $receipt->received_at?->format('d M Y') }}</span>
                </div>
            @empty
                <p style="font-size:12px;color:#64748b;">You have not recorded any goods receipts yet.</p>
            @endforelse
        </div>
        <div class="card" style="padding:18px;">
            <h2 style="font-size:14px;margin:0 0 12px;">My Recent Dispatch Requests</h2>
            @forelse ($myDispatches as $dispatch)
                <div style="display:flex;justify-content:space-between;gap:10px;padding:9px 0;border-bottom:1px solid #f1f5f9;font-size:12px;">
                    <span><strong>{{ $dispatch->product?->name ?? 'Product' }}</strong><br><span style="color:#64748b;">{{ number_format((float) $dispatch->requested_quantity, 3) }} · {{ ucfirst($dispatch->status) }}</span></span>
                    <a href="{{ route('dispatch-requests.pick-list', $dispatch) }}" style="white-space:nowrap;">Open →</a>
                </div>
            @empty
                <p style="font-size:12px;color:#64748b;">You have not created any dispatch requests yet.</p>
            @endforelse
        </div>
    </div>
@endsection
