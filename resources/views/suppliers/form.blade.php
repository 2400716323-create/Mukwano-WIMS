@extends('catalog.layout')

@section('title', $supplier->exists ? 'Edit supplier' : 'New supplier')

@section('breadcrumbs')
    <a href="{{ route('suppliers.index') }}">Suppliers</a>
    <span class="sep">/</span>
    @if ($supplier->exists)
        <a href="{{ route('suppliers.show', $supplier) }}">{{ $supplier->name }}</a>
        <span class="sep">/</span>
        <span class="current">Edit</span>
    @else
        <span class="current">New Supplier</span>
    @endif
@endsection

@section('content')
    <!-- Unified Page Header -->
    <div class="page-header-card">
        <div class="page-title-wrap">
            <nav class="breadcrumb-trail" aria-label="Breadcrumb">
                <a href="{{ route('dashboard') }}">Dashboard</a>
                <span class="separator">/</span>
                <a href="{{ route('suppliers.index') }}">Suppliers</a>
                <span class="separator">/</span>
                <span class="current">{{ $supplier->exists ? 'Edit '.$supplier->name : 'New Supplier' }}</span>
            </nav>
            <h1>{{ $supplier->exists ? 'Edit Supplier' : 'New Supplier' }}</h1>
            <p>{{ $supplier->exists ? 'Update vendor contact particulars, operational status, and credit terms.' : 'Register a new vendor for purchase orders and goods receipting.' }}</p>
        </div>
        <div class="page-actions-wrap">
            <a class="button secondary" href="{{ $supplier->exists ? route('suppliers.show', $supplier) : route('suppliers.index') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                <span>Cancel</span>
            </a>
        </div>
    </div>

    <!-- Form Container -->
    <form method="POST" action="{{ $supplier->exists ? route('suppliers.update', $supplier) : route('suppliers.store') }}" style="max-width: 820px; margin: 0 auto; display: flex; flex-direction: column; gap: 20px;">
        @csrf
        @if ($supplier->exists)
            @method('PUT')
        @endif

        <!-- Card 1: Vendor Entity Details -->
        <div class="card" style="background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 24px;">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 18px; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px;">
                <div style="width: 28px; height: 28px; border-radius: 6px; background: rgba(37, 99, 235, 0.1); color: #2563eb; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px;">1</div>
                <div>
                    <h2 style="font-size: 15px; font-weight: 700; color: #0f172a; margin: 0;">Vendor Identification</h2>
                    <p style="font-size: 12px; color: #64748b; margin: 0;">Legal company title, internal code, and trading status</p>
                </div>
            </div>

            <div class="form-grid">
                <div class="field">
                    <label for="name" style="font-size: 12.5px; font-weight: 600; color: #334155;">Supplier Name <span style="color: #ef4444;">*</span></label>
                    <input id="name" name="name" required maxlength="255" value="{{ old('name', $supplier->name) }}" placeholder="e.g. Unilever Uganda Ltd"
                           style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; font-size: 13.5px;">
                    @error('name') <small class="errors" style="display:block; margin-top:4px; color: #dc2626; font-size: 12px;">{{ $message }}</small> @enderror
                </div>
                <div class="field">
                    <label for="code" style="font-size: 12.5px; font-weight: 600; color: #334155;">Supplier Code <span style="color: #ef4444;">*</span></label>
                    <input id="code" name="code" required maxlength="100" value="{{ old('code', $supplier->code) }}" placeholder="e.g. SUP-001"
                           style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; font-family: monospace; font-size: 13.5px;">
                    @error('code') <small class="errors" style="display:block; margin-top:4px; color: #dc2626; font-size: 12px;">{{ $message }}</small> @enderror
                </div>
                <div class="field">
                    <label for="status" style="font-size: 12.5px; font-weight: 600; color: #334155;">Vendor Status</label>
                    <select id="status" name="status" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; font-size: 13.5px;">
                        <option value="active" @selected(old('status', $supplier->status ?: 'active') === 'active')>Active</option>
                        <option value="inactive" @selected(old('status', $supplier->status) === 'inactive')>Inactive</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Card 2: Contact Particulars & Address -->
        <div class="card" style="background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 24px;">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 18px; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px;">
                <div style="width: 28px; height: 28px; border-radius: 6px; background: rgba(16, 185, 129, 0.1); color: #10b981; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px;">2</div>
                <div>
                    <h2 style="font-size: 15px; font-weight: 700; color: #0f172a; margin: 0;">Contact & Commercial Terms</h2>
                    <p style="font-size: 12px; color: #64748b; margin: 0;">Representative contact details, phone, email, and billing notes</p>
                </div>
            </div>

            <div class="form-grid">
                <div class="field">
                    <label for="contact_person" style="font-size: 12.5px; font-weight: 600; color: #334155;">Contact Person</label>
                    <input id="contact_person" name="contact_person" maxlength="255" value="{{ old('contact_person', $supplier->contact_person) }}" placeholder="e.g. John Doe"
                           style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; font-size: 13.5px;">
                </div>
                <div class="field">
                    <label for="phone" style="font-size: 12.5px; font-weight: 600; color: #334155;">Phone Number</label>
                    <input id="phone" name="phone" maxlength="50" value="{{ old('phone', $supplier->phone) }}" placeholder="e.g. +256 700 000000"
                           style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; font-size: 13.5px;">
                </div>
                <div class="field full">
                    <label for="email" style="font-size: 12.5px; font-weight: 600; color: #334155;">Email Address</label>
                    <input id="email" name="email" type="email" maxlength="255" value="{{ old('email', $supplier->email) }}" placeholder="e.g. orders@supplier.com"
                           style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; font-size: 13.5px;">
                    @error('email') <small class="errors" style="display:block; margin-top:4px; color: #dc2626; font-size: 12px;">{{ $message }}</small> @enderror
                </div>
                <div class="field full">
                    <label for="address" style="font-size: 12.5px; font-weight: 600; color: #334155;">Physical / Postal Address</label>
                    <textarea id="address" name="address" maxlength="2000" rows="2" placeholder="e.g. Plot 12, 1st Street Industrial Area, Kampala"
                              style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; font-size: 13px;">{{ old('address', $supplier->address) }}</textarea>
                </div>
                <div class="field full">
                    <label for="notes" style="font-size: 12.5px; font-weight: 600; color: #334155;">Internal Notes & Payment Terms</label>
                    <textarea id="notes" name="notes" maxlength="5000" rows="3" placeholder="e.g. Net 30 days payment terms, primary vendor for bulk crude palm oil."
                              style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; font-size: 13px;">{{ old('notes', $supplier->notes) }}</textarea>
                </div>
            </div>
        </div>

        <!-- Action Footer -->
        <div style="background: #ffffff; border-radius: 10px; border: 1px solid #e2e8f0; padding: 16px 24px; display: flex; align-items: center; justify-content: flex-end; gap: 12px;">
            <a class="button secondary" href="{{ $supplier->exists ? route('suppliers.show', $supplier) : route('suppliers.index') }}" style="padding: 9px 20px;">Cancel</a>
            <button type="submit" class="button" style="background: #2563eb; color: #ffffff; padding: 9px 24px; font-weight: 600;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                <span>{{ $supplier->exists ? 'Save Changes' : 'Create Supplier' }}</span>
            </button>
        </div>
    </form>
@endsection
