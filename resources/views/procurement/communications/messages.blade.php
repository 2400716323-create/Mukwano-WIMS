
@extends('catalog.layout')

@section('title', 'Internal Communication History')

@section('breadcrumbs')
    <span class="breadcrumbs-separator">/</span>
    <a href="{{ route('procurement-communications.index') }}"
       style="color:#64748b;text-decoration:none;">
        Procurement Communication
    </a>
    <span class="breadcrumbs-separator">/</span>
    <span style="color:#0f172a;font-weight:600;">Message History</span>
@endsection

@section('content')

<style>
    .comms-page {
        display: flex;
        flex-direction: column;
        gap: 18px;
    }

    .comms-hero {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 24px;
        border: 1px solid #dbeafe;
        border-radius: 15px;
        background: linear-gradient(120deg,#fff,#eff6ff);
    }

    .comms-hero h1 {
        margin: 0;
        color: #0f172a;
        font-size: 23px;
        font-weight: 800;
    }

    .comms-hero p {
        margin: 7px 0 0;
        color: #64748b;
        font-size: 13px;
    }

    .comms-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 10px 14px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background: #fff;
        color: #334155;
        font-size: 12px;
        font-weight: 700;
        text-decoration: none;
        cursor: pointer;
    }

    .comms-button-primary {
        border-color: #1d4ed8;
        background: #1d4ed8;
        color: #fff;
    }

    .comms-stats {
        display: grid;
        grid-template-columns: repeat(4,minmax(0,1fr));
        gap: 12px;
    }

    .comms-stat {
        padding: 16px;
        border: 1px solid #e2e8f0;
        border-radius: 11px;
        background: #fff;
    }

    .comms-stat span {
        display: block;
        color: #64748b;
        font-size: 11px;
        font-weight: 650;
    }

    .comms-stat strong {
        display: block;
        margin-top: 7px;
        color: #0f172a;
        font-size: 23px;
        font-weight: 800;
    }

    .comms-panel {
        padding: 20px;
        border: 1px solid #e2e8f0;
        border-radius: 13px;
        background: #fff;
    }

    .comms-panel-heading {
        margin: 0 0 15px;
        color: #0f172a;
        font-size: 16px;
        font-weight: 800;
    }

    .comms-filters {
        display: grid;
        grid-template-columns: minmax(200px,1fr) 200px auto;
        gap: 11px;
        align-items: end;
    }

    .comms-field label {
        display: block;
        margin-bottom: 6px;
        color: #475569;
        font-size: 11px;
        font-weight: 750;
    }

    .comms-field input,
    .comms-field select {
        width: 100%;
        min-height: 41px;
        padding: 9px 11px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background: #fff;
        color: #334155;
        font-size: 12px;
    }

    .comms-filter-actions {
        display: flex;
        gap: 8px;
    }

    .comms-list {
        display: grid;
        gap: 13px;
    }

    .comms-message {
        padding: 17px;
        border: 1px solid #e2e8f0;
        border-radius: 11px;
        background: #fff;
        transition: border-color .2s, box-shadow .2s;
    }

    .comms-message:hover {
        border-color: #bfdbfe;
        box-shadow: 0 4px 13px rgba(15,23,42,.04);
    }

    .comms-message-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
    }

    .comms-message h3 {
        margin: 0;
        color: #0f172a;
        font-size: 14px;
        font-weight: 800;
    }

    .comms-message-preview {
        margin: 8px 0 14px;
        color: #64748b;
        font-size: 12px;
        line-height: 1.7;
        overflow-wrap: anywhere;
    }

    .comms-badge {
        display: inline-flex;
        flex-shrink: 0;
        align-items: center;
        padding: 5px 9px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 800;
    }

    .comms-badge-sent {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .comms-badge-read {
        background: #dcfce7;
        color: #166534;
    }

    .comms-badge-unread {
        background: #fee2e2;
        color: #b91c1c;
    }

    .comms-details {
        display: grid;
        grid-template-columns: repeat(4,minmax(0,1fr));
        gap: 12px;
        padding: 13px 0;
        border-top: 1px solid #f1f5f9;
        border-bottom: 1px solid #f1f5f9;
    }

    .comms-detail span {
        display: block;
        margin-bottom: 5px;
        color: #94a3b8;
        font-size: 10px;
        font-weight: 750;
        text-transform: uppercase;
    }

    .comms-detail strong {
        color: #334155;
        font-size: 12px;
        font-weight: 700;
        overflow-wrap: anywhere;
    }

    .comms-message-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        padding-top: 13px;
    }

    .comms-direction {
        color: #64748b;
        font-size: 11px;
    }

    .comms-open {
        color: #1d4ed8;
        font-size: 12px;
        font-weight: 800;
        text-decoration: none;
    }

    .comms-open:hover {
        text-decoration: underline;
    }

    .comms-empty {
        padding: 35px 15px;
        border: 1px dashed #cbd5e1;
        border-radius: 10px;
        color: #64748b;
        text-align: center;
        font-size: 13px;
    }

    .comms-pagination {
        padding-top: 16px;
    }

    @media(max-width:850px) {
        .comms-stats {
            grid-template-columns: repeat(2,minmax(0,1fr));
        }

        .comms-details {
            grid-template-columns: repeat(2,minmax(0,1fr));
        }
    }

    @media(max-width:600px) {
        .comms-hero {
            align-items: flex-start;
            flex-direction: column;
        }

        .comms-filters {
            grid-template-columns: 1fr;
        }

        .comms-filter-actions {
            flex-wrap: wrap;
        }

        .comms-message-top {
            flex-direction: column;
        }

        .comms-details {
            grid-template-columns: 1fr 1fr;
        }
    }
</style>

<div class="comms-page">

    {{-- Page heading --}}
    <section class="comms-hero">
        <div>
            <h1>Internal Communication History</h1>
            <p>Manage and review supplier-related messages sent and received by your account.</p>
        </div>

        <a href="{{ route('procurement-communications.index') }}"
           class="comms-button">
            ← Back to Dashboard
        </a>
    </section>

    {{-- Pagination summary cards --}}
    <section class="comms-stats">
        <div class="comms-stat">
            <span>Total matching messages</span>
            <strong>{{ number_format($messages->total()) }}</strong>
        </div>

        <div class="comms-stat">
            <span>Current page</span>
            <strong>{{ $messages->currentPage() }}</strong>
        </div>

        <div class="comms-stat">
            <span>Messages per page</span>
            <strong>{{ $messages->perPage() }}</strong>
        </div>

        <div class="comms-stat">
            <span>Total pages</span>
            <strong>{{ $messages->lastPage() }}</strong>
        </div>
    </section>

    {{-- Search and filters --}}
    <section class="comms-panel">
        <h2 class="comms-panel-heading">Find a Message</h2>

        <form method="GET"
              action="{{ route('procurement-communications.messages') }}"
              class="comms-filters">

            <div class="comms-field">
                <label for="q">Search messages</label>
                <input
                    type="search"
                    id="q"
                    name="q"
                    value="{{ $search }}"
                    placeholder="Search subject, supplier, staff or message..."
                >
            </div>

            <div class="comms-field">
                <label for="filter">Message category</label>
                <select name="filter" id="filter">
                    <option value="all" @selected($filter === 'all')>All messages</option>
                    <option value="unread" @selected($filter === 'unread')>Unread messages</option>
                    <option value="read" @selected($filter === 'read')>Read messages</option>
                    <option value="sent" @selected($filter === 'sent')>Sent messages</option>
                    <option value="received" @selected($filter === 'received')>Received messages</option>
                </select>
            </div>

            <div class="comms-filter-actions">
                <button type="submit"
                        class="comms-button comms-button-primary">
                    Search
                </button>

                <a href="{{ route('procurement-communications.messages') }}"
                   class="comms-button">
                    Reset
                </a>
            </div>
        </form>
    </section>

    {{-- Messages --}}
    <section class="comms-panel">
        <h2 class="comms-panel-heading">All Internal Messages</h2>

        <div class="comms-list">
            @forelse($messages as $message)

                @php
                    $isReceived = (int) $message->recipient_id === (int) auth()->id();
                    $isUnread = $isReceived && !$message->read_at;
                @endphp

                <article class="comms-message">

                    <div class="comms-message-top">
                        <div>
                            <h3>{{ $message->subject }}</h3>
                        </div>

                        @if($isUnread)
                            <span class="comms-badge comms-badge-unread">
                                Unread
                            </span>
                        @elseif($isReceived)
                            <span class="comms-badge comms-badge-read">
                                Read
                            </span>
                        @else
                            <span class="comms-badge comms-badge-sent">
                                Sent
                            </span>
                        @endif
                    </div>

                    <p class="comms-message-preview">
                        {{ $message->message }}
                    </p>

                    <div class="comms-details">

                        <div class="comms-detail">
                            <span>Supplier</span>
                            <strong>
                                {{ $message->supplier?->name ?? 'Supplier unavailable' }}
                            </strong>
                        </div>

                        <div class="comms-detail">
                            <span>Sender</span>
                            <strong>
                                {{ $message->sender?->name ?? 'Unknown' }}
                            </strong>
                        </div>

                        <div class="comms-detail">
                            <span>Recipient</span>
                            <strong>
                                {{ $message->recipient?->name ?? 'Unknown' }}
                            </strong>
                        </div>

                        <div class="comms-detail">
                            <span>Date sent</span>
                            <strong>
                                {{ $message->created_at?->format('d M Y, H:i') ?? 'Date unavailable' }}
                            </strong>
                        </div>

                    </div>

                    <div class="comms-message-footer">
                        <span class="comms-direction">
                            {{ $isReceived ? 'Received by your account' : 'Sent by your account' }}
                        </span>

                        @if($message->supplier)
                            <a href="{{ route('suppliers.messages.index', $message->supplier) }}"
                               class="comms-open">
                                Open supplier conversation →
                            </a>
                        @else
                            <span class="comms-direction">Conversation unavailable</span>
                        @endif
                    </div>

                </article>

            @empty

                <div class="comms-empty">
                    <div style="font-size:26px;margin-bottom:8px;">✉</div>
                    <strong>No messages found</strong>
                    <p style="margin:6px 0 0;">
                        Try changing your search words or message category.
                    </p>
                </div>

            @endforelse
        </div>

        <div class="comms-pagination">
            {{ $messages->links() }}
        </div>
    </section>

</div>

@endsection
