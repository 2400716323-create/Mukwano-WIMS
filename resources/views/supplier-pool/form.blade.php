
@extends('catalog.layout')

@section('title', $lead->exists ? 'Review Potential Supplier' : 'New Potential Supplier')

@section('content')
<style>
    .pool-form-page {
        max-width: 950px;
        margin: 0 auto;
        color: #172b4d;
    }

    .pool-form-page * {
        box-sizing: border-box;
    }

    .pool-form-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 15px;
        margin-bottom: 22px;
    }

    .pool-form-header h1 {
        margin: 0 0 7px;
        color: #10243e;
        font-size: 24px;
        font-weight: 750;
    }

    .pool-form-header p {
        margin: 0;
        color: #718096;
        font-size: 12px;
    }

    .pool-form-card {
        margin-bottom: 18px;
        padding: 22px;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        box-shadow: 0 2px 7px rgba(15, 23, 42, .035);
    }

    .pool-form-section-title {
        margin: 0 0 18px;
        padding-bottom: 13px;
        border-bottom: 1px solid #e2e8f0;
        color: #172b4d;
        font-size: 14px;
        font-weight: 700;
    }

    .pool-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 17px;
    }

    .pool-form-field {
        display: block;
        color: #475569;
        font-size: 11px;
        font-weight: 650;
    }

    .pool-form-field input,
    .pool-form-field select,
    .pool-form-field textarea {
        display: block;
        width: 100%;
        margin-top: 7px;
        padding: 11px 12px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        background: #fff;
        color: #334155;
        font-family: inherit;
        font-size: 12px;
        outline: none;
    }

    .pool-form-field input,
    .pool-form-field select {
        min-height: 41px;
    }

    .pool-form-field textarea {
        min-height: 90px;
        resize: vertical;
    }

    .pool-form-field input:focus,
    .pool-form-field select:focus,
    .pool-form-field textarea:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .1);
    }

    .pool-form-field-full {
        grid-column: 1 / -1;
    }

    .pool-form-actions {
        display: flex;
        justify-content: flex-end;
        flex-wrap: wrap;
        gap: 10px;
        padding: 16px 20px;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
    }

    .pool-form-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 40px;
        padding: 10px 17px;
        border: 1px solid transparent;
        border-radius: 6px;
        background: #2563eb;
        color: white !important;
        font-size: 12px;
        font-weight: 650;
        text-decoration: none !important;
        cursor: pointer;
    }

    .pool-form-btn:hover {
        background: #1d4ed8;
    }

    .pool-form-btn-secondary {
        border-color: #cbd5e1;
        background: #fff;
        color: #475569 !important;
    }

    .pool-form-btn-secondary:hover {
        background: #f8fafc;
    }

    .pool-form-errors {
        margin-bottom: 17px;
        padding: 13px 16px;
        border: 1px solid #fecaca;
        border-radius: 7px;
        background: #fef2f2;
        color: #b91c1c;
        font-size: 12px;
    }

    .pool-required {
        color: #dc2626;
    }

    @media(max-width: 650px) {
        .pool-form-grid {
            grid-template-columns: 1fr;
        }

        .pool-form-field-full {
            grid-column: auto;
        }

        .pool-form-header {
            flex-direction: column;
        }
    }
</style>

<div class="pool-form-page">

    <div class="pool-form-header">
        <div>
            <h1>{{ $lead->exists ? 'Review Potential Supplier' : 'Add Potential Supplier' }}</h1>
            <p>
                This record stays in the discovery pool until it is separately registered.
            </p>
        </div>

        <a href="{{ route('supplier-pool.index') }}"
           class="pool-form-btn pool-form-btn-secondary">
            ← Back to Supplier Pool
        </a>
    </div>

    @if($errors->any())
        <div class="pool-form-errors">
            <strong>Please correct the following:</strong>
            <ul style="margin:8px 0 0 18px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST"
          action="{{ $lead->exists ? route('supplier-pool.update', $lead) : route('supplier-pool.store') }}">

        @csrf

        @if($lead->exists)
            @method('PUT')
        @endif

        <div class="pool-form-card">
            <h3 class="pool-form-section-title">
                1. Company Identification
            </h3>

            <div class="pool-form-grid">
                <label class="pool-form-field">
                    Company Name <span class="pool-required">*</span>
                    <input type="text"
                           name="company_name"
                           required
                           maxlength="255"
                           placeholder="e.g. Afripack Packaging Solutions"
                           value="{{ old('company_name', $lead->company_name) }}">
                </label>

                <label class="pool-form-field">
                    Contact Person
                    <input type="text"
                           name="contact_person"
                           placeholder="e.g. John Doe"
                           value="{{ old('contact_person', $lead->contact_person) }}">
                </label>

                <label class="pool-form-field">
                    Email Address
                    <input type="email"
                           name="email"
                           placeholder="e.g. supplier@example.com"
                           value="{{ old('email', $lead->email) }}">
                </label>

                <label class="pool-form-field">
                    Phone Number
                    <input type="text"
                           name="phone"
                           placeholder="e.g. +256 700 000000"
                           value="{{ old('phone', $lead->phone) }}">
                </label>

                <label class="pool-form-field">
                    Website
                    <input type="url"
                           name="website"
                           placeholder="https://example.com"
                           value="{{ old('website', $lead->website) }}">
                </label>

                <label class="pool-form-field">
                    Location
                    <input type="text"
                           name="location"
                           placeholder="e.g. Kampala, Uganda"
                           value="{{ old('location', $lead->location) }}">
                </label>
            </div>
        </div>

        <div class="pool-form-card">
            <h3 class="pool-form-section-title">
                2. Discovery Source
            </h3>

            <div class="pool-form-grid">
                <label class="pool-form-field">
                    Discovery Source <span class="pool-required">*</span>
                    <select name="source_type" required>
                        @foreach(['manual','website','facebook','linkedin','other'] as $source)
                            <option value="{{ $source }}"
                                @selected(old('source_type', $lead->source_type ?: 'manual') === $source)>
                                {{ ucfirst($source) }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="pool-form-field">
                    Source Profile or Page URL
                    <input type="url"
                           name="source_url"
                           placeholder="https://..."
                           value="{{ old('source_url', $lead->source_url) }}">
                </label>

                <label class="pool-form-field pool-form-field-full">
                    Company Description / Products Supplied
                    <textarea name="description"
                              rows="4"
                              placeholder="Describe the company and the products or services it provides...">{{ old('description', $lead->description) }}</textarea>
                </label>
            </div>
        </div>

        @if($lead->exists)
            <div class="pool-form-card">
                <h3 class="pool-form-section-title">
                    3. Supplier Review
                </h3>

                <div class="pool-form-grid">
                    <label class="pool-form-field">
                        Review Status
                        <select name="status">
                            @foreach(['discovered','under_review','verified','rejected'] as $status)
                                <option value="{{ $status }}"
                                    @selected(old('status', $lead->status) === $status)>
                                    {{ ucfirst(str_replace('_', ' ', $status)) }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label class="pool-form-field pool-form-field-full">
                        Review Notes
                        <textarea name="review_notes"
                                  rows="4"
                                  placeholder="Enter supplier review observations...">{{ old('review_notes', $lead->review_notes) }}</textarea>
                    </label>
                </div>
            </div>
        @endif

        <div class="pool-form-actions">
            <a href="{{ route('supplier-pool.index') }}"
               class="pool-form-btn pool-form-btn-secondary">
                Cancel
            </a>

            <button type="submit" class="pool-form-btn">
                {{ $lead->exists ? 'Save Review' : 'Add to Supplier Pool' }}
            </button>
        </div>

    </form>
</div>
@endsection
