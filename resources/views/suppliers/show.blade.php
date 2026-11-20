
@extends('catalog.layout')

@section('title', $supplier->name)

@section('breadcrumbs')
    <span class="breadcrumbs-separator">/</span>
    <a href="{{ route('suppliers.index') }}">Suppliers</a>
    <span class="breadcrumbs-separator">/</span>
    <span style="color:#0f172a;font-weight:600;">
        {{ $supplier->name }}
    </span>
@endsection

@section('content')

    @php
        $whatsappNumber = preg_replace(
            '/\D+/',
            '',
            (string) $supplier->phone
        );

        $emailSubject = 'Supplier enquiry - '.$supplier->name;

        $gmailUrl = $supplier->email
            ? 'https://mail.google.com/mail/?view=cm&fs=1&to='
                .rawurlencode($supplier->email)
                .'&su='.rawurlencode($emailSubject)
            : null;
    @endphp

    {{-- BREADCRUMB --}}
    <nav style="display:flex;align-items:center;gap:8px;font-size:12.5px;color:#64748b;margin-bottom:14px;" aria-label="Breadcrumb">

        <a href="{{ route('dashboard') }}"
           style="color:#64748b;text-decoration:none;">
            Dashboard
        </a>

        <span>/</span>

        <a href="{{ route('suppliers.index') }}"
           style="color:#64748b;text-decoration:none;">
            Suppliers
        </a>

        <span>/</span>

        <span style="color:#0f172a;font-weight:600;">
            {{ $supplier->name }}
        </span>
    </nav>

    {{-- SUPPLIER HEADER --}}
    <div class="page-header-card">

        <div class="page-title-wrap">

            <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">

                <span class="pill-code">
                    {{ $supplier->code }}
                </span>

                @if ($supplier->status === 'active')
                    <span class="badge-success">
                        Active Supplier
                    </span>
                @else
                    <span class="badge-muted">
                        Inactive
                    </span>
                @endif

            </div>

            <h1>{{ $supplier->name }}</h1>

            <p>
                {{ $supplier->contact_person
                    ? "Contact: {$supplier->contact_person}"
                    : 'No primary contact designated' }}

                · Registered {{ $supplier->created_at->format('M Y') }}
            </p>

        </div>

        <div class="page-actions-wrap">

            <a class="button secondary"
               href="{{ route('suppliers.edit', $supplier) }}">
                Edit Supplier
            </a>

            <a class="button secondary"
               href="{{ route('suppliers.index') }}">
                Back to Suppliers
            </a>

        </div>
    </div>

    {{-- SUPPLIER COMMUNICATION CENTRE --}}
    <div class="card" style="padding:22px;margin-bottom:20px;">

        <h2 style="font-size:17px;margin:0 0 6px;color:#0f172a;">
            Supplier Communication Centre
        </h2>

        <p style="font-size:13px;color:#64748b;margin:0 0 18px;">
            Contact {{ $supplier->name }} through your preferred communication channel.
        </p>

        <div style="display:flex;gap:10px;flex-wrap:wrap;">

            {{-- NORMAL PHONE CALL --}}
            @if ($supplier->phone)

                <a href="tel:{{ $supplier->phone }}"
                   class="button"
                   style="background:#2563eb;color:white;text-decoration:none;">
                    ☎ Call Supplier
                </a>

                {{-- WHATSAPP --}}
                @if ($whatsappNumber)

                    <a href="https://wa.me/{{ $whatsappNumber }}"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="button"
                       style="background:#16a34a;color:white;text-decoration:none;">
                        WhatsApp
                    </a>

                @endif

                {{-- SMS --}}
                <a href="sms:{{ $supplier->phone }}"
                   class="button"
                   style="background:#7c3aed;color:white;text-decoration:none;">
                    ✉ Send SMS
                </a>

            @else

                <span class="button secondary" style="opacity:.6;">
                    No phone number available
                </span>

            @endif

            {{-- GMAIL --}}
            @if ($supplier->email)

                <a href="{{ $gmailUrl }}"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="button secondary"
                   style="text-decoration:none;">
                    ✉ Email Supplier
                </a>

            @else

                <span class="button secondary" style="opacity:.6;">
                    No email available
                </span>

            @endif

            {{-- INTERNAL MESSAGING --}}
            <a href="{{ route('suppliers.messages.index', $supplier) }}"
               class="button"
               style="background:#7c3aed;color:white;text-decoration:none;">
                ✉ Internal Messages
            </a>

        </div>

        {{-- COMMUNICATION INFORMATION --}}
        <div style="margin-top:16px;padding:12px;background:#f8fafc;border-radius:8px;">

            <p style="font-size:12px;color:#64748b;margin:0;line-height:1.8;">

                <strong>Call:</strong>
                Opens a phone application configured to handle calls.

                <br>

                <strong>WhatsApp:</strong>
                Opens the supplier's WhatsApp chat.

                <br>

                <strong>SMS:</strong>
                Opens a messaging application where supported.

                <br>

                <strong>Email:</strong>
                Opens Gmail with the supplier's address filled in.

                <br>

                <strong>Internal Messages:</strong>
                Allows authorised staff to exchange private messages about this supplier inside Mukwano-MIS.

            </p>

        </div>

    </div>

    {{-- SUPPLIER PROFILE INFORMATION --}}
    <div class="card" style="max-width:900px;padding:24px;">

        <h2 style="margin-top:0;margin-bottom:16px;font-size:16px;color:#0f172a;border-bottom:1px solid #f1f5f9;padding-bottom:10px;">
            Supplier Profile & Contact Information
        </h2>

        <div class="detail-grid">

            {{-- VENDOR CODE --}}
            <div>
                <span style="font-size:11.5px;color:#64748b;font-weight:600;text-transform:uppercase;">
                    Vendor Code
                </span>

                <p style="margin:3px 0 0;font-weight:700;color:#0f172a;font-family:monospace;">
                    {{ $supplier->code }}
                </p>
            </div>

            {{-- CONTACT PERSON --}}
            <div>
                <span style="font-size:11.5px;color:#64748b;font-weight:600;text-transform:uppercase;">
                    Primary Contact Person
                </span>

                <p style="margin:3px 0 0;font-weight:600;color:#334155;">
                    {{ $supplier->contact_person ?: '—' }}
                </p>
            </div>

            {{-- PHONE --}}
            <div>
                <span style="font-size:11.5px;color:#64748b;font-weight:600;text-transform:uppercase;">
                    Direct Phone
                </span>

                <p style="margin:3px 0 0;font-weight:600;color:#2563eb;">
                    {{ $supplier->phone ?: '—' }}
                </p>
            </div>

            {{-- EMAIL --}}
            <div>
                <span style="font-size:11.5px;color:#64748b;font-weight:600;text-transform:uppercase;">
                    Email Address
                </span>

                <p style="margin:3px 0 0;font-weight:600;color:#334155;">
                    {{ $supplier->email ?: '—' }}
                </p>
            </div>

            {{-- REGISTERED DATE --}}
            <div>
                <span style="font-size:11.5px;color:#64748b;font-weight:600;text-transform:uppercase;">
                    Registered On
                </span>

                <p style="margin:3px 0 0;color:#64748b;font-size:12.5px;">
                    {{ $supplier->created_at->format('d M Y, H:i') }}
                </p>
            </div>

            {{-- UPDATED DATE --}}
            <div>
                <span style="font-size:11.5px;color:#64748b;font-weight:600;text-transform:uppercase;">
                    Last Updated
                </span>

                <p style="margin:3px 0 0;color:#64748b;font-size:12.5px;">
                    {{ $supplier->updated_at->format('d M Y, H:i') }}
                </p>
            </div>

            {{-- ADDRESS --}}
            <div class="full">

                <span style="font-size:11.5px;color:#64748b;font-weight:600;text-transform:uppercase;">
                    Physical Address
                </span>

                <p style="margin:4px 0 0;color:#475569;font-size:13px;">
                    {{ $supplier->address ?: 'No address specified.' }}
                </p>

            </div>

            {{-- NOTES --}}
            <div class="full" style="border-bottom:0;">

                <span style="font-size:11.5px;color:#64748b;font-weight:600;text-transform:uppercase;">
                    Notes & Terms
                </span>

                <p style="margin:4px 0 0;color:#475569;font-size:13px;line-height:1.5;">
                    {{ $supplier->notes ?: 'No additional notes logged.' }}
                </p>

            </div>

        </div>

    </div>

@endsection
