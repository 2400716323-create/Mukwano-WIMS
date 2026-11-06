@extends('catalog.layout')

@section('title', $supplier->exists ? 'Edit supplier' : 'New supplier')

@section('breadcrumbs')
    <span class="breadcrumbs-separator">/</span>
    <a href="{{ route('suppliers.index') }}">Suppliers</a>
    <span class="breadcrumbs-separator">/</span>
    @if ($supplier->exists)
        <a href="{{ route('suppliers.show', $supplier) }}">{{ $supplier->name }}</a>
        <span class="breadcrumbs-separator">/</span>
        <span style="color: #0f172a; font-weight: 600;">Edit</span>
    @else
        <span style="color: #0f172a; font-weight: 600;">New Supplier</span>
    @endif
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
        @if ($supplier->exists)
            <a href="{{ route('suppliers.show', $supplier) }}" style="color: #64748b; text-decoration: none;">{{ $supplier->name }}</a>
            <span style="color: #cbd5e1;">/</span>
            <span style="color: #0f172a; font-weight: 600;">Edit</span>
        @else
            <span style="color: #0f172a; font-weight: 600;">New Supplier</span>
        @endif
    </nav>

    <div class="page-header-card">
        <div class="page-title-wrap">
            <h1>{{ $supplier->exists ? 'Edit Supplier' : 'New Supplier' }}</h1>
            <p>{{ $supplier->exists ? 'Update supplier contact particulars and operational status.' : 'Register a new vendor for procurement and goods receipting.' }}</p>
        </div>
        <div class="page-actions-wrap">
            <a class="button secondary" href="{{ $supplier->exists ? route('suppliers.show', $supplier) : route('suppliers.index') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                <span>Cancel</span>
            </a>
        </div>
    </div>

    <form class="card" method="POST" action="{{ $supplier->exists ? route('suppliers.update', $supplier) : route('suppliers.store') }}" style="max-width: 800px;">
        @csrf
        @if ($supplier->exists)
            @method('PUT')
        @endif
        <div class="form-grid">
            <div class="field">
                <label for="name">Supplier Name</label>
                <input id="name" name="name" required maxlength="255" value="{{ old('name', $supplier->name) }}" placeholder="e.g. Unilever Uganda Ltd">
                @error('name') <small class="errors" style="display:block; margin-top:4px; padding:4px 8px; border-radius:4px;">{{ $message }}</small> @enderror
            </div>
            <div class="field">
                <label for="code">Supplier Code</label>
                <input id="code" name="code" required maxlength="100" value="{{ old('code', $supplier->code) }}" placeholder="e.g. SUP-001">
                @error('code') <small class="errors" style="display:block; margin-top:4px; padding:4px 8px; border-radius:4px;">{{ $message }}</small> @enderror
            </div>
            <div class="field">
                <label for="contact_person">Contact Person</label>
                <input id="contact_person" name="contact_person" maxlength="255" value="{{ old('contact_person', $supplier->contact_person) }}" placeholder="e.g. John Doe">
            </div>
            <div class="field">
                <label for="phone">Phone Number</label>
                <input id="phone" name="phone" maxlength="50" value="{{ old('phone', $supplier->phone) }}" placeholder="e.g. +256 700 000000">
            </div>
            <div class="field">
                <label for="email">Email Address</label>
                <input id="email" name="email" type="email" maxlength="255" value="{{ old('email', $supplier->email) }}" placeholder="e.g. orders@supplier.com">
                @error('email') <small class="errors" style="display:block; margin-top:4px; padding:4px 8px; border-radius:4px;">{{ $message }}</small> @enderror
            </div>
            <div class="field">
                <label for="status">Vendor Status</label>
                <select id="status" name="status">
                    <option value="active" @selected(old('status', $supplier->status ?: 'active') === 'active')>Active</option>
                    <option value="inactive" @selected(old('status', $supplier->status) === 'inactive')>Inactive</option>
                </select>
            </div>
            <div class="field full">
                <label for="address">Physical / Postal Address</label>
                <textarea id="address" name="address" maxlength="2000" rows="2" placeholder="e.g. Plot 12, 1st Street Industrial Area, Kampala">{{ old('address', $supplier->address) }}</textarea>
            </div>
            <div class="field full">
                <label for="notes">Internal Notes & Payment Terms</label>
                <textarea id="notes" name="notes" maxlength="5000" rows="3" placeholder="e.g. Net 30 days payment terms, primary contact for bulk crude palm oil.">{{ old('notes', $supplier->notes) }}</textarea>
            </div>
        </div>
        <div style="margin-top: 18px; display: flex; align-items: center; gap: 10px;">
            <button type="submit" class="button">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                <span>{{ $supplier->exists ? 'Save Changes' : 'Create Supplier' }}</span>
            </button>
            <a class="button secondary" href="{{ $supplier->exists ? route('suppliers.show', $supplier) : route('suppliers.index') }}">Cancel</a>
        </div>
    </form>
@endsection
