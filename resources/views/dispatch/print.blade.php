@extends('catalog.layout')

@section('title', 'Goods delivery note '.$dispatchRequest->gdn_number)

@section('breadcrumbs')
    <a href="{{ route('dispatch-requests.index') }}">Dispatch Requests</a>
    <span class="sep">/</span>
    <span class="current">{{ $dispatchRequest->gdn_number }}</span>
@endsection

@section('content')
    <style>
        @media print {
            .sidebar, .topbar, .breadcrumbs-bar, .mobile-bottom-bar, .no-print { display: none !important; }
            .app-shell { display: block !important; width: 100% !important; }
            .main-content { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
            .print-document { border: none !important; box-shadow: none !important; padding: 0 !important; }
        }
    </style>

    <!-- Page Header (Screen Only) -->
    <div class="page-header-card no-print">
        <div class="page-title-wrap">
            <nav class="breadcrumb-trail" aria-label="Breadcrumb">
                <a href="{{ route('dashboard') }}">Dashboard</a>
                <span class="separator">/</span>
                <a href="{{ route('dispatch-requests.index') }}">Dispatch Requests</a>
                <span class="separator">/</span>
                <span class="current">{{ $dispatchRequest->gdn_number }}</span>
            </nav>
            <h1>Goods delivery note {{ $dispatchRequest->gdn_number }}</h1>
            <p>Official outbound delivery note generated upon automated FEFO pick verification.</p>
        </div>
        <div class="page-actions-wrap">
            <a class="button secondary" href="{{ route('dispatch-requests.index') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                <span>Dispatch Requests</span>
            </a>
            <button type="button" class="button" onclick="window.print()" style="background: #2563eb; color: #ffffff;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                <span>Print GDN</span>
            </button>
        </div>
    </div>

    <!-- Printable GDN Slip Card -->
    <div class="card print-document" style="max-width: 820px; margin: 0 auto; background: #ffffff; border-radius: 10px; border: 1px solid #e2e8f0; padding: 32px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #0f172a; padding-bottom: 18px; margin-bottom: 24px;">
            <div>
                <div style="font-size: 20px; font-weight: 800; color: #0f172a; letter-spacing: -0.01em;">MUKWANO INDUSTRIES UGANDA LIMITED</div>
                <div style="font-size: 13px; color: #64748b; margin-top: 2px;">Warehouse Inventory Management System (WIMS)</div>
                <div style="font-size: 12px; color: #94a3b8;">Namanve Industrial Area, Kampala, Uganda</div>
            </div>
            <div style="text-align: right;">
                <span class="pill-code" style="font-size: 13px; font-weight: 800; color: #2563eb; background: #eff6ff; padding: 6px 12px;">{{ $dispatchRequest->gdn_number }}</span>
                <div style="font-size: 12px; color: #64748b; margin-top: 6px;">Date: {{ $dispatchRequest->dispatched_at->format('d M Y, H:i') }}</div>
            </div>
        </div>

        <h1 style="font-size: 18px; font-weight: 800; color: #0f172a; margin: 0 0 18px 0; text-transform: uppercase; letter-spacing: 0.04em;">Goods Delivery Note</h1>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; margin-bottom: 24px;">
            <div>
                <span style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: #64748b; letter-spacing: 0.04em;">Product Material</span>
                <div style="font-weight: 700; color: #0f172a; font-size: 14px; margin-top: 3px;">{{ $dispatchRequest->product->name }}</div>
                <span class="pill-code" style="margin-top: 4px; display: inline-block;">{{ $dispatchRequest->product->sku }}</span>
            </div>
            <div>
                <span style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: #64748b; letter-spacing: 0.04em;">Total Dispatched Quantity</span>
                <div style="font-weight: 800; color: #16a34a; font-size: 18px; margin-top: 3px;">
                    {{ $dispatchRequest->requested_quantity }} <span style="font-size: 13px; font-weight: 600; color: #64748b;">{{ $dispatchRequest->product->unit }}</span>
                </div>
            </div>
            <div style="border-top: 1px solid #e2e8f0; padding-top: 10px;">
                <span style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: #64748b; letter-spacing: 0.04em;">Requested By</span>
                <div style="font-weight: 600; color: #0f172a; font-size: 13px; margin-top: 2px;">{{ $dispatchRequest->requester->name }}</div>
            </div>
            <div style="border-top: 1px solid #e2e8f0; padding-top: 10px;">
                <span style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: #64748b; letter-spacing: 0.04em;">Dispatched By Officer</span>
                <div style="font-weight: 600; color: #0f172a; font-size: 13px; margin-top: 2px;">{{ $dispatchRequest->dispatcher->name }}</div>
            </div>
        </div>

        <div class="modern-table-card" style="border: 1px solid #e2e8f0; margin-bottom: 24px;">
            <table class="clean-table">
                <thead>
                    <tr>
                        <th style="width: 30%;">Batch Number</th>
                        <th style="width: 25%;">Expiry Date</th>
                        <th style="width: 25%;">Source Bin</th>
                        <th style="width: 20%; text-align: right;">Quantity</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($dispatchRequest->lines as $line)
                    <tr>
                        <td><span style="font-family: monospace; font-weight: 700; color: #2563eb;">{{ $line->batch->batch_number }}</span></td>
                        <td><span style="color: #64748b; font-size: 12.5px;">{{ $line->batch->expires_at?->toDateString() ?? '—' }}</span></td>
                        <td><span class="pill-code">{{ $line->location->code }}</span> <small style="color: #64748b;">{{ $line->location->name }}</small></td>
                        <td style="text-align: right; font-weight: 700; color: #0f172a;">{{ $line->quantity }} {{ $dispatchRequest->product->unit }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        @if ($dispatchRequest->deviation_reason)
            <div style="background: #fffbeb; border: 1px solid #fef3c7; border-radius: 6px; padding: 12px 16px; margin-bottom: 24px;">
                <strong style="color: #92400e; font-size: 12.5px;">Authorized FEFO deviation reason:</strong>
                <p style="margin: 4px 0 0 0; font-size: 12.5px; color: #78350f;">{{ $dispatchRequest->deviation_reason }}</p>
            </div>
        @endif

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-top: 40px; padding-top: 20px; border-top: 1px dashed #cbd5e1;">
            <div>
                <div style="border-bottom: 1px solid #94a3b8; height: 35px; margin-bottom: 6px;"></div>
                <span style="font-size: 11.5px; color: #64748b; text-transform: uppercase; font-weight: 600;">Dispatch Officer Signature & Date</span>
            </div>
            <div>
                <div style="border-bottom: 1px solid #94a3b8; height: 35px; margin-bottom: 6px;"></div>
                <span style="font-size: 11.5px; color: #64748b; text-transform: uppercase; font-weight: 600;">Driver / Recipient Signature & Date</span>
            </div>
        </div>
    </div>
@endsection
