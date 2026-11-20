
@extends('catalog.layout')

@section('title', 'Procurement Communication Centre')

@section('breadcrumbs')
    <span class="breadcrumbs-separator">/</span>
    <span style="color:#0f172a;font-weight:600;">Procurement Communication</span>
@endsection

@section('content')

<style>
    .procurement-page {
        display: flex;
        flex-direction: column;
        gap: 22px;
        color: #1e293b;
    }

    .procurement-page * {
        box-sizing: border-box;
    }

    .proc-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
        padding: 25px;
        background: linear-gradient(120deg, #ffffff, #f1f5ff);
        border: 1px solid #e2e8f0;
        border-radius: 14px;
    }

    .proc-header h1 {
        margin: 0;
        font-size: 23px;
        font-weight: 750;
        color: #0f172a;
    }

    .proc-header p {
        margin: 7px 0 0;
        font-size: 13px;
        color: #64748b;
    }

    .proc-header-actions {
        display: flex;
        gap: 9px;
        flex-wrap: wrap;
    }

    .proc-btn {
        display: inline-flex;
        justify-content: center;
        align-items: center;
        gap: 7px;
        padding: 10px 14px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 650;
        text-decoration: none;
        border: 1px solid #e2e8f0;
        background: #fff;
        color: #334155;
        transition: .2s;
    }

    .proc-btn:hover {
        background: #f1f5f9;
    }

    .proc-btn-primary {
        background: #1d4ed8;
        color: #fff;
        border-color: #1d4ed8;
    }

    .proc-btn-primary:hover {
        background: #1e40af;
        color: #fff;
    }

    .proc-btn-small {
        padding: 7px 10px;
        font-size: 11px;
    }

    .proc-section-heading {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 15px;
        margin-bottom: 16px;
    }

    .proc-section-heading h2 {
        margin: 0;
        color: #0f172a;
        font-size: 16px;
        font-weight: 750;
    }

    .proc-section-heading p {
        margin: 5px 0 0;
        color: #64748b;
        font-size: 12px;
    }

    .proc-stat-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 13px;
    }

    .proc-stat-card {
        display: flex;
        align-items: center;
        gap: 13px;
        min-width: 0;
        padding: 17px;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 2px 5px rgba(15,23,42,.025);
    }

    .proc-stat-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: #eff6ff;
        color: #2563eb;
        font-size: 18px;
        font-weight: 750;
    }

    .proc-stat-icon.red {
        background: #fef2f2;
        color: #dc2626;
    }

    .proc-stat-icon.green {
        background: #f0fdf4;
        color: #16a34a;
    }

    .proc-stat-icon.orange {
        background: #fff7ed;
        color: #ea580c;
    }

    .proc-stat-icon.purple {
        background: #f5f3ff;
        color: #7c3aed;
    }

    .proc-stat-info {
        min-width: 0;
    }

    .proc-stat-label {
        display: block;
        font-size: 11px;
        color: #64748b;
        font-weight: 600;
    }

    .proc-stat-value {
        display: block;
        margin-top: 3px;
        font-size: 23px;
        line-height: 1.2;
        color: #0f172a;
        font-weight: 800;
    }

    .proc-stat-note {
        display: block;
        margin-top: 3px;
        color: #94a3b8;
        font-size: 10px;
    }

    .proc-card {
        min-width: 0;
        padding: 20px;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 2px 6px rgba(15,23,42,.025);
    }

    .proc-main-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.45fr) minmax(300px, .85fr);
        gap: 18px;
        align-items: start;
    }

    .proc-table-wrap {
        width: 100%;
        overflow-x: auto;
    }

    .proc-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px;
        white-space: nowrap;
    }

    .proc-table th {
        padding: 12px 10px;
        text-align: left;
        color: #64748b;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .proc-table td {
        padding: 14px 10px;
        border-bottom: 1px solid #f1f5f9;
        color: #475569;
    }

    .proc-table tr:last-child td {
        border-bottom: none;
    }

    .proc-order-number {
        color: #1d4ed8;
        font-weight: 750;
    }

    .proc-status {
        display: inline-block;
        padding: 5px 9px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 700;
    }

    .proc-status-draft {
        color: #92400e;
        background: #fef3c7;
    }

    .proc-status-approved {
        color: #1d4ed8;
        background: #dbeafe;
    }

    .proc-status-partial {
        color: #9a3412;
        background: #ffedd5;
    }

    .proc-status-received {
        color: #166534;
        background: #dcfce7;
    }

    .proc-status-cancelled {
        color: #475569;
        background: #e2e8f0;
    }

    .proc-status-overdue {
        color: #b91c1c;
        background: #fee2e2;
    }

    .proc-link {
        color: #2563eb;
        font-size: 11px;
        font-weight: 700;
        text-decoration: none;
    }

    .proc-link:hover {
        text-decoration: underline;
    }

    .proc-supplier {
        padding: 14px 0;
        border-bottom: 1px solid #f1f5f9;
    }

    .proc-supplier:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    .proc-supplier-top {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: flex-start;
    }

    .proc-supplier-name {
        color: #0f172a;
        font-size: 13px;
        font-weight: 750;
    }

    .proc-supplier-contact {
        margin-top: 5px;
        color: #64748b;
        font-size: 11px;
        line-height: 1.7;
        overflow-wrap: anywhere;
    }

    .proc-supplier-orders {
        display: inline-block;
        margin-top: 7px;
        padding: 4px 8px;
        color: #475569;
        background: #f1f5f9;
        border-radius: 5px;
        font-size: 10px;
        font-weight: 650;
    }

    .proc-contact-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 11px;
    }

    .proc-contact-actions a {
        font-size: 11px;
        font-weight: 650;
        text-decoration: none;
    }

    .proc-contact-links {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 11px;
        padding-top: 10px;
        border-top: 1px solid #f1f5f9;
    }

    .proc-contact-links a {
        font-size: 11px;
        text-decoration: none;
        font-weight: 650;
    }

    .proc-messages {
        display: flex;
        flex-direction: column;
    }

    .proc-message {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 15px;
        padding: 16px 0;
        border-bottom: 1px solid #f1f5f9;
    }

    .proc-message:last-child {
        border-bottom: none;
    }

    .proc-message-content {
        min-width: 0;
    }

    .proc-message-subject {
        color: #0f172a;
        font-size: 13px;
        font-weight: 750;
    }

    .proc-message-details {
        margin-top: 5px;
        color: #64748b;
        font-size: 11px;
        line-height: 1.7;
    }

    .proc-message-preview {
        margin-top: 7px;
        color: #475569;
        font-size: 12px;
        line-height: 1.6;
        overflow-wrap: anywhere;
    }

    .proc-message-date {
        margin-top: 7px;
        color: #94a3b8;
        font-size: 10px;
    }

    .proc-message-side {
        flex-shrink: 0;
        text-align: right;
    }

    .proc-message-status {
        display: inline-block;
        padding: 5px 9px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 700;
    }

    .proc-message-unread {
        color: #b91c1c;
        background: #fee2e2;
    }

    .proc-message-read {
        color: #166534;
        background: #dcfce7;
    }

    .proc-message-sent {
        color: #1d4ed8;
        background: #dbeafe;
    }

    .proc-empty {
        padding: 25px 12px;
        text-align: center;
        color: #94a3b8;
        font-size: 12px;
    }

    .proc-bottom-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        align-items: center;
    }

    @media(max-width: 1100px) {
        .proc-stat-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .proc-main-grid {
            grid-template-columns: 1fr;
        }
    }

    @media(max-width: 650px) {
        .proc-header {
            flex-direction: column;
            align-items: flex-start;
            padding: 18px;
        }

        .proc-header h1 {
            font-size: 19px;
        }

        .proc-header-actions {
            width: 100%;
        }

        .proc-header-actions .proc-btn {
            flex: 1;
        }

        .proc-stat-grid {
            grid-template-columns: 1fr 1fr;
            gap: 9px;
        }

        .proc-stat-card {
            padding: 12px;
            gap: 9px;
        }

        .proc-stat-icon {
            width: 33px;
            height: 33px;
            font-size: 14px;
        }

        .proc-stat-value {
            font-size: 19px;
        }

        .proc-stat-label {
            font-size: 10px;
        }

        .proc-stat-note {
            font-size: 9px;
        }

        .proc-card {
            padding: 14px;
        }

        .proc-section-heading {
            align-items: flex-start;
        }

        .proc-message {
            gap: 8px;
        }
    }
</style>

<div class="procurement-page">

    {{-- Page heading --}}
    <header class="proc-header">
        <div>
            <h1>Procurement Communication Centre</h1>
            <p>Manage purchase orders, supplier contacts, delivery follow-ups and internal communication.</p>
        </div>

        <div class="proc-header-actions">
            <a href="{{ route('procurement-communications.messages') }}" class="proc-btn">
                Messages
            </a>

            <a href="{{ route('purchase-orders.create') }}" class="proc-btn proc-btn-primary">
                + Create Purchase Order
            </a>
        </div>
    </header>

    {{-- Procurement overview --}}
    <section>
        <div class="proc-section-heading">
            <div>
                <h2>Procurement Overview</h2>
                <p>A summary of your purchase orders and supplier activity.</p>
            </div>
        </div>

        <div class="proc-stat-grid">

            <div class="proc-stat-card">
                <div class="proc-stat-icon">PO</div>
                <div class="proc-stat-info">
                    <span class="proc-stat-label">Total Purchase Orders</span>
                    <span class="proc-stat-value">{{ number_format($metrics['total_orders']) }}</span>
                    <span class="proc-stat-note">All recorded orders</span>
                </div>
            </div>

            <div class="proc-stat-card">
                <div class="proc-stat-icon orange">D</div>
                <div class="proc-stat-info">
                    <span class="proc-stat-label">Draft Orders</span>
                    <span class="proc-stat-value">{{ number_format($metrics['draft']) }}</span>
                    <span class="proc-stat-note">Awaiting approval</span>
                </div>
            </div>

            <div class="proc-stat-card">
                <div class="proc-stat-icon">A</div>
                <div class="proc-stat-info">
                    <span class="proc-stat-label">Approved Orders</span>
                    <span class="proc-stat-value">{{ number_format($metrics['approved']) }}</span>
                    <span class="proc-stat-note">Approved for supply</span>
                </div>
            </div>

            <div class="proc-stat-card">
                <div class="proc-stat-icon red">!</div>
                <div class="proc-stat-info">
                    <span class="proc-stat-label">Overdue Orders</span>
                    <span class="proc-stat-value" style="{{ $metrics['overdue'] > 0 ? 'color:#dc2626;' : '' }}">
                        {{ number_format($metrics['overdue']) }}
                    </span>
                    <span class="proc-stat-note">Past expected delivery</span>
                </div>
            </div>

            <div class="proc-stat-card">
                <div class="proc-stat-icon orange">½</div>
                <div class="proc-stat-info">
                    <span class="proc-stat-label">Partially Received</span>
                    <span class="proc-stat-value">{{ number_format($metrics['partially_received']) }}</span>
                    <span class="proc-stat-note">Awaiting remaining goods</span>
                </div>
            </div>

            <div class="proc-stat-card">
                <div class="proc-stat-icon green">✓</div>
                <div class="proc-stat-info">
                    <span class="proc-stat-label">Fully Received</span>
                    <span class="proc-stat-value">{{ number_format($metrics['received']) }}</span>
                    <span class="proc-stat-note">Completed deliveries</span>
                </div>
            </div>

            <div class="proc-stat-card">
                <div class="proc-stat-icon purple">S</div>
                <div class="proc-stat-info">
                    <span class="proc-stat-label">Registered Suppliers</span>
                    <span class="proc-stat-value">{{ number_format($supplierCount) }}</span>
                    <span class="proc-stat-note">Available suppliers</span>
                </div>
            </div>

            <div class="proc-stat-card">
                <div class="proc-stat-icon red">M</div>
                <div class="proc-stat-info">
                    <span class="proc-stat-label">Unread Messages</span>
                    <span class="proc-stat-value">{{ number_format($unreadMessagesCount) }}</span>
                    <span class="proc-stat-note">{{ number_format($messageCount) }} messages involving you</span>
                </div>
            </div>

        </div>
    </section>

    {{-- Purchase orders and supplier contacts --}}
    <div class="proc-main-grid">

        {{-- Purchase order follow-up --}}
        <section class="proc-card">
            <div class="proc-section-heading">
                <div>
                    <h2>Purchase Order Follow-up</h2>
                    <p>Monitor supplier orders and expected deliveries.</p>
                </div>

                <a href="{{ route('purchase-orders.index') }}" class="proc-btn proc-btn-small">
                    View All
                </a>
            </div>

            <div class="proc-table-wrap">
                <table class="proc-table">
                    <thead>
                        <tr>
                            <th>Order Number</th>
                            <th>Supplier</th>
                            <th>Expected Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($purchaseOrders as $order)
                            @php
                                $expectedDate = $order->expected_date
                                    ? \Illuminate\Support\Carbon::parse($order->expected_date)
                                    : null;

                                $isOverdue = $expectedDate
                                    && $expectedDate->lt(today())
                                    && in_array($order->status, [
                                        \App\Models\PurchaseOrder::STATUS_APPROVED,
                                        \App\Models\PurchaseOrder::STATUS_PARTIALLY_RECEIVED
                                    ]);

                                $statusClass = match($order->status) {
                                    'draft' => 'proc-status-draft',
                                    'approved' => 'proc-status-approved',
                                    'partially_received' => 'proc-status-partial',
                                    'received' => 'proc-status-received',
                                    'cancelled' => 'proc-status-cancelled',
                                    default => 'proc-status-approved',
                                };
                            @endphp

                            <tr>
                                <td>
                                    <span class="proc-order-number">
                                        {{ $order->order_number ?? ('PO-' . $order->id) }}
                                    </span>
                                </td>

                                <td>{{ $order->supplier?->name ?? 'Supplier unavailable' }}</td>

                                <td>
                                    {{ $expectedDate?->format('d M Y') ?? 'Not set' }}

                                    @if($isOverdue)
                                        <div style="margin-top:5px;">
                                            <span class="proc-status proc-status-overdue">Overdue</span>
                                        </div>
                                    @endif
                                </td>

                                <td>
                                    <span class="proc-status {{ $statusClass }}">
                                        {{ ucwords(str_replace('_', ' ', $order->status)) }}
                                    </span>
                                </td>

                                <td>
                                    <a href="{{ route('purchase-orders.show', $order) }}" class="proc-link">
                                        Open →
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="proc-empty">
                                    No purchase orders have been recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Supplier directory --}}
        <section class="proc-card">
            <div class="proc-section-heading">
                <div>
                    <h2>Supplier Directory</h2>
                    <p>Contact and communicate with your suppliers.</p>
                </div>

                <a href="{{ route('suppliers.index') }}" class="proc-btn proc-btn-small">
                    All Suppliers
                </a>
            </div>

            @forelse($suppliers as $supplier)
                @php
                    $phone = preg_replace('/\D+/', '', $supplier->phone ?? '');

                    if (str_starts_with($phone, '0')) {
                        $phone = '256' . substr($phone, 1);
                    }
                @endphp

                <div class="proc-supplier">
                    <div class="proc-supplier-top">
                        <div style="min-width:0;">
                            <div class="proc-supplier-name">{{ $supplier->name }}</div>

                            <div class="proc-supplier-contact">
                                Contact: {{ $supplier->contact_person ?: 'Not recorded' }}<br>

                                Phone: {{ $supplier->phone ?: 'Not recorded' }}<br>

                                Email: {{ $supplier->email ?: 'Not recorded' }}
                            </div>

                            <span class="proc-supplier-orders">
                                {{ number_format($supplier->open_purchase_orders_count) }}
                                outstanding order(s)
                            </span>
                        </div>
                    </div>

                    <div class="proc-contact-actions">
                        @if($supplier->phone)
                            <a href="tel:{{ $supplier->phone }}" style="color:#2563eb;">Call</a>

                            @if($phone)
                                <a href="https://wa.me/{{ $phone }}"
                                   target="_blank"
                                   rel="noopener"
                                   style="color:#16a34a;">WhatsApp</a>
                            @endif
                        @endif

                        @if($supplier->email)
                            <a href="mailto:{{ $supplier->email }}" style="color:#7c3aed;">Email</a>
                        @endif
                    </div>

                    <div class="proc-contact-links">
                        <a href="{{ route('suppliers.messages.index', $supplier) }}" class="proc-link">
                            Internal Conversation →
                        </a>

                        <a href="{{ route('suppliers.show', $supplier) }}" style="color:#64748b;">
                            Supplier Profile
                        </a>
                    </div>
                </div>

            @empty
                <div class="proc-empty">
                    No suppliers have been registered.
                </div>
            @endforelse
        </section>

    </div>

    {{-- Internal communication history --}}
    <section class="proc-card">
        <div class="proc-section-heading">
            <div>
                <h2>Recent Internal Communication</h2>
                <p>Latest supplier-related messages sent or received by your account.</p>
            </div>

            <a href="{{ route('procurement-communications.messages') }}"
               class="proc-btn proc-btn-primary proc-btn-small">
                View All Messages →
            </a>
        </div>

        <div class="proc-messages">
            @forelse($recentMessages as $message)

                @php
                    $isReceived = (int) $message->recipient_id === (int) auth()->id();
                    $isUnread = $isReceived && !$message->read_at;
                @endphp

                <div class="proc-message">
                    <div class="proc-message-content">
                        <div class="proc-message-subject">
                            {{ $message->subject }}
                        </div>

                        <div class="proc-message-details">
                            Supplier:
                            <strong>{{ $message->supplier?->name ?? 'Supplier unavailable' }}</strong>
                            <br>

                            From {{ $message->sender?->name ?? 'Unknown' }}
                            to {{ $message->recipient?->name ?? 'Unknown' }}
                        </div>

                        <div class="proc-message-preview">
                            {{ \Illuminate\Support\Str::limit($message->message, 180) }}
                        </div>

                        <div class="proc-message-date">
                            {{ $message->created_at?->format('d M Y, H:i') ?? 'Date unavailable' }}
                        </div>
                    </div>

                    <div class="proc-message-side">
                        @if($isUnread)
                            <span class="proc-message-status proc-message-unread">Unread</span>
                        @elseif($isReceived)
                            <span class="proc-message-status proc-message-read">Read</span>
                        @else
                            <span class="proc-message-status proc-message-sent">Sent</span>
                        @endif

                        @if($message->supplier)
                            <div style="margin-top:10px;">
                                <a href="{{ route('suppliers.messages.index', $message->supplier) }}"
                                   class="proc-link">
                                    Open →
                                </a>
                            </div>
                        @endif
                    </div>
                </div>

            @empty
                <div class="proc-empty">
                    <div style="font-size:25px;margin-bottom:8px;">✉</div>
                    No internal supplier messages are available for your account yet.
                </div>
            @endforelse
        </div>

        <div style="padding-top:15px;border-top:1px solid #f1f5f9;">
            <div class="proc-bottom-actions">
                <a href="{{ route('procurement-communications.messages') }}" class="proc-btn proc-btn-primary">
                    View All Messages
                </a>

                <a href="{{ route('suppliers.index') }}" class="proc-btn">
                    Browse Suppliers
                </a>

                <a href="{{ route('purchase-orders.index') }}" class="proc-btn">
                    Manage Purchase Orders
                </a>
            </div>
        </div>
    </section>

</div>

@endsection
