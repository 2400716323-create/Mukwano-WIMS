
@extends('catalog.layout')

@section('title', 'Internal Supplier Messages')

@section('content')
<div style="max-width:1100px;margin:0 auto;">

    <nav style="font-size:13px;color:#64748b;margin-bottom:16px;">
        <a href="{{ route('dashboard') }}" style="color:#64748b;">Dashboard</a>
        /
        <a href="{{ route('suppliers.index') }}" style="color:#64748b;">Suppliers</a>
        /
        <a href="{{ route('suppliers.show', $supplier) }}" style="color:#64748b;">
            {{ $supplier->name }}
        </a>
        / Internal Messages
    </nav>

    <div class="page-header-card">
        <div class="page-title-wrap">
            <h1>Internal Messaging Centre</h1>
            <p>
                Staff communication history for
                <strong>{{ $supplier->name }}</strong>
            </p>
        </div>

        <div class="page-actions-wrap">
            <a href="{{ route('suppliers.show', $supplier) }}"
               class="button secondary">
                Back to Supplier
            </a>
        </div>
    </div>

    @if (session('success'))
        <div style="padding:13px 16px;margin-bottom:16px;background:#ecfdf5;color:#166534;border:1px solid #bbf7d0;border-radius:8px;">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div style="padding:13px 16px;margin-bottom:16px;background:#fef2f2;color:#991b1b;border:1px solid #fecaca;border-radius:8px;">
            <strong>Please correct the following:</strong>
            <ul style="margin:8px 0 0 18px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- COMPOSE MESSAGE --}}
    <div class="card" style="padding:22px;margin-bottom:22px;">
        <h2 style="font-size:17px;margin:0 0 6px;color:#0f172a;">
            Compose Internal Message
        </h2>

        <p style="font-size:13px;color:#64748b;margin:0 0 18px;">
            Send a private system message to another active staff member
            about this supplier.
        </p>

        @if ($users->isNotEmpty())
            <form method="POST"
                  action="{{ route('suppliers.messages.store', $supplier) }}">
                @csrf

                <div style="margin-bottom:15px;">
                    <label for="recipient_id"
                           style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">
                        Send To
                    </label>

                    <select name="recipient_id"
                            id="recipient_id"
                            required
                            style="width:100%;padding:11px;border:1px solid #cbd5e1;border-radius:7px;">
                        <option value="">Select staff member</option>

                        @foreach ($users as $user)
                            <option value="{{ $user->getKey() }}"
                                @selected(old('recipient_id') == $user->getKey())>
                                {{ $user->name }} — {{ $user->email }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div style="margin-bottom:15px;">
                    <label for="subject"
                           style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">
                        Subject
                    </label>

                    <input type="text"
                           name="subject"
                           id="subject"
                           maxlength="150"
                           value="{{ old('subject') }}"
                           placeholder="Enter message subject"
                           required
                           style="width:100%;padding:11px;border:1px solid #cbd5e1;border-radius:7px;">
                </div>

                <div style="margin-bottom:16px;">
                    <label for="message"
                           style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">
                        Message
                    </label>

                    <textarea name="message"
                              id="message"
                              rows="5"
                              maxlength="10000"
                              required
                              placeholder="Write your message here..."
                              style="width:100%;padding:11px;border:1px solid #cbd5e1;border-radius:7px;resize:vertical;">{{ old('message') }}</textarea>
                </div>

                <button type="submit"
                        class="button"
                        style="background:#2563eb;color:white;border:0;cursor:pointer;">
                    Send Internal Message
                </button>
            </form>
        @else
            <p style="padding:14px;background:#f8fafc;border-radius:8px;color:#64748b;">
                There are no other active staff accounts available to receive messages.
            </p>
        @endif
    </div>

    {{-- MESSAGE HISTORY --}}
    <div class="card" style="padding:22px;">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:18px;">
            <div>
                <h2 style="font-size:17px;margin:0;color:#0f172a;">
                    Communication History
                </h2>
                <p style="font-size:13px;color:#64748b;margin:5px 0 0;">
                    Messages you have sent or received about this supplier.
                </p>
            </div>

            <span style="font-size:12px;color:#64748b;">
                {{ $messages->total() }} messages
            </span>
        </div>

        @forelse ($messages as $item)
            @php
                $isOutgoing = (string) $item->sender_id
                    === (string) auth()->user()->getAuthIdentifier();
            @endphp

            <div style="border:1px solid #e2e8f0;border-radius:9px;padding:17px;margin-bottom:13px;background:{{ $isOutgoing ? '#f8fafc' : '#ffffff' }};">

                <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:9px;">
                    <div>
                        <strong style="color:#0f172a;">
                            {{ $item->subject }}
                        </strong>

                        <div style="font-size:12px;color:#64748b;margin-top:5px;">
                            {{ $isOutgoing ? 'You sent this to' : 'Received from' }}
                            {{ $isOutgoing
                                ? ($item->recipient?->name ?? 'Former user')
                                : ($item->sender?->name ?? 'Former user') }}
                        </div>
                    </div>

                    <div style="text-align:right;">
                        <span style="font-size:11px;padding:5px 9px;border-radius:20px;background:{{ $isOutgoing ? '#dbeafe' : ($item->read_at ? '#dcfce7' : '#fef3c7') }};color:{{ $isOutgoing ? '#1d4ed8' : ($item->read_at ? '#166534' : '#92400e') }};">
                            {{ $isOutgoing ? 'Sent' : ($item->read_at ? 'Read' : 'Unread') }}
                        </span>

                        <div style="font-size:11px;color:#64748b;margin-top:7px;">
                            {{ $item->created_at->format('d M Y, h:i A') }}
                        </div>
                    </div>
                </div>

                <div style="font-size:13px;color:#334155;line-height:1.7;white-space:pre-wrap;">{{ $item->message }}</div>

                @if (! $isOutgoing && ! $item->read_at)
                    <form method="POST"
                          action="{{ route('suppliers.messages.read', [$supplier, $item]) }}"
                          style="margin-top:13px;">
                        @csrf

                        <button type="submit"
                                class="button secondary"
                                style="cursor:pointer;">
                            Mark as Read
                        </button>
                    </form>
                @endif
            </div>
        @empty
            <div style="padding:35px 15px;text-align:center;background:#f8fafc;border-radius:8px;">
                <h3 style="font-size:15px;color:#334155;margin:0 0 7px;">
                    No messages yet
                </h3>
                <p style="font-size:13px;color:#64748b;margin:0;">
                    Messages sent or received about this supplier will appear here.
                </p>
            </div>
        @endforelse

        <div style="margin-top:18px;">
            {{ $messages->links() }}
        </div>
    </div>

</div>
@endsection
