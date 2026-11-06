@extends('catalog.layout')

@section('title', $supplier->name)

@section('breadcrumbs')
    <span class="breadcrumbs-separator">/</span>
    <a href="{{ route('suppliers.index') }}">Suppliers</a>
    <span class="breadcrumbs-separator">/</span>
    <span style="color: #0f172a; font-weight: 600;">{{ $supplier->name }}</span>
@endsection

@section('content')
    <!-- Inline Breadcrumbs Navigation -->
    <nav style="display: flex; align-items: center; gap: 8px; font-size: 12.5px; color: #64748b; margin-bottom: 14px;" aria-label="Breadcrumb">
        <a href="{{ route('dashboard') }}" style="color: #64748b; text-decoration: none; display: flex; align-items: center; gap: 4px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
            <span>Dashboard</span>
        </a>
        <span style="color: #cbd5e1;">/</span>
        <a href="{{ route('suppliers.index') }}" style="color: #64748b; text-decoration: none;">Suppliers</a>
        <span style="color: #cbd5e1;">/</span>
        <span style="color: #0f172a; font-weight: 600;">{{ $supplier->name }}</span>
    </nav>

    <div class="page-header-card">
        <div class="page-title-wrap">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                <span class="pill-code">{{ $supplier->code }}</span>
                @if ($supplier->status === 'active')
                    <span class="badge-success">Active Supplier</span>
                @else
                    <span class="badge-muted">Inactive</span>
                @endif
            </div>
            <h1>{{ $supplier->name }}</h1>
            <p>{{ $supplier->contact_person ? "Contact: {$supplier->contact_person}" : 'No primary contact designated' }} · Registered {{ $supplier->created_at->format('M Y') }}</p>
        </div>
        <div class="page-actions-wrap">
            <a class="button secondary" href="{{ route('suppliers.edit', $supplier) }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                <span>Edit Supplier</span>
            </a>
            <a class="button secondary" href="{{ route('suppliers.index') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                <span>Back to Suppliers</span>
            </a>
        </div>
    </div>

    <div class="card" style="max-width: 900px; padding: 24px;">
        <h2 style="margin-top: 0; margin-bottom: 16px; font-size: 16px; color: #0f172a; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px;">Supplier Profile & Contact Information</h2>
        <div class="detail-grid">
            <div>
                <span style="font-size: 11.5px; color: #64748b; font-weight: 600; text-transform: uppercase;">Vendor Code</span>
                <p style="margin: 3px 0 0; font-weight: 700; color: #0f172a; font-family: monospace;">{{ $supplier->code }}</p>
            </div>
            <div>
                <span style="font-size: 11.5px; color: #64748b; font-weight: 600; text-transform: uppercase;">Primary Contact Person</span>
                <p style="margin: 3px 0 0; font-weight: 600; color: #334155;">{{ $supplier->contact_person ?: '—' }}</p>
            </div>
            <div>
                <span style="font-size: 11.5px; color: #64748b; font-weight: 600; text-transform: uppercase;">Direct Phone</span>
                <p style="margin: 3px 0 0; font-weight: 600; color: #2563eb;">{{ $supplier->phone ?: '—' }}</p>
            </div>
            <div>
                <span style="font-size: 11.5px; color: #64748b; font-weight: 600; text-transform: uppercase;">Email Address</span>
                <p style="margin: 3px 0 0; font-weight: 600; color: #334155;">{{ $supplier->email ?: '—' }}</p>
            </div>
            <div>
                <span style="font-size: 11.5px; color: #64748b; font-weight: 600; text-transform: uppercase;">Registered On</span>
                <p style="margin: 3px 0 0; color: #64748b; font-size: 12.5px;">{{ $supplier->created_at->format('d M Y, H:i') }}</p>
            </div>
            <div>
                <span style="font-size: 11.5px; color: #64748b; font-weight: 600; text-transform: uppercase;">Last Updated</span>
                <p style="margin: 3px 0 0; color: #64748b; font-size: 12.5px;">{{ $supplier->updated_at->format('d M Y, H:i') }}</p>
            </div>
            <div class="full">
                <span style="font-size: 11.5px; color: #64748b; font-weight: 600; text-transform: uppercase;">Physical Address</span>
                <p style="margin: 4px 0 0; color: #475569; font-size: 13px;">{{ $supplier->address ?: 'No address specified.' }}</p>
            </div>
            <div class="full" style="border-bottom: 0;">
                <span style="font-size: 11.5px; color: #64748b; font-weight: 600; text-transform: uppercase;">Notes & Terms</span>
                <p style="margin: 4px 0 0; color: #475569; font-size: 13px; line-height: 1.5;">{{ $supplier->notes ?: 'No additional notes logged.' }}</p>
            </div>
        </div>
    </div>
@endsection
