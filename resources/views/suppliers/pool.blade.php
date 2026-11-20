
@extends('catalog.layout')

@section('content')
<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold">Supplier Pool</h2>
            <p class="text-muted">
                External supplier directory for Mukwano Industries Uganda Limited.
            </p>
        </div>

        <a href="{{ asset('downloads/Mukwano_250_Supplier_Pool.xlsx') }}"
           class="btn btn-success"
           download>
            <i data-lucide="download"></i>
            Download Excel
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm p-3">
                <h6 class="text-muted">Supplier Records</h6>
                <h2>250</h2>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm p-3">
                <h6 class="text-muted">Publicly Listed Leads</h6>
                <h2>16</h2>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm p-3">
                <h6 class="text-muted">Product Categories</h6>
                <h2>7</h2>
            </div>
        </div>
    </div>

    <div class="card shadow-sm p-4">
        <h4 class="fw-bold mb-3">Supplier Directory</h4>

        <p>
            Access the external supplier pool to identify businesses
            that can supply goods and services required by Mukwano
            Industries Uganda Limited.
        </p>

        <h5>Available supplier information</h5>

        <ul>
            <li>Supplier and company names</li>
            <li>Contact persons and telephone numbers</li>
            <li>Email addresses and physical locations</li>
            <li>Products and services supplied</li>
            <li>Product categories</li>
            <li>Supplier performance evaluation fields</li>
        </ul>

        <div class="alert alert-info">
            <strong>Note:</strong> This is an external procurement
            reference directory. Suppliers are not system users
            and do not receive login accounts.
        </div>

        <a href="{{ asset('downloads/Mukwano_250_Supplier_Pool.xlsx') }}"
           class="btn btn-primary"
           download>
            <i data-lucide="file-spreadsheet"></i>
            Access Supplier Workbook
        </a>
    </div>

</div>
@endsection
