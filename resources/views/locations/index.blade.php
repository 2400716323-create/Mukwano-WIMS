@extends('catalog.layout')

@section('title', 'Warehouse locations')

@section('breadcrumbs')
    <span class="breadcrumbs-separator">/</span>
    <span style="color: #0f172a; font-weight: 600;">Warehouse Locations</span>
@endsection

@section('content')
    <!-- Breadcrumb Navigation -->
    <nav style="display: flex; align-items: center; gap: 8px; font-size: 12.5px; color: #64748b; margin-bottom: 14px;" aria-label="Breadcrumb">
        <a href="{{ route('dashboard') }}" style="color: #64748b; text-decoration: none; display: flex; align-items: center; gap: 4px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                <polyline points="9 22 9 12 15 12 15 22"></polyline>
            </svg>
            <span>Dashboard</span>
        </a>
        <span style="color: #cbd5e1;">/</span>
        <span style="color: #0f172a; font-weight: 600;">Warehouse Locations</span>
    </nav>

    <!-- Page Header -->
    <div class="page-header-card">
        <div class="page-title-wrap">
            <h1>Warehouse Locations</h1>
            <p>Manage warehouse areas, receiving bays, staging areas, zones, racks and storage bins.</p>
        </div>

        <div class="page-actions-wrap">
            <a class="button secondary" href="{{ route('qr.scan') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 7V5a2 2 0 0 1 2-2h2"/>
                    <path d="M17 3h2a2 2 0 0 1 2 2v2"/>
                    <path d="M21 17v2a2 2 0 0 1-2 2h-2"/>
                    <path d="M7 21H5a2 2 0 0 1-2-2v-2"/>
                    <line x1="7" y1="12" x2="17" y2="12"/>
                </svg>
                <span>Scan QR</span>
            </a>

            <a class="button" href="{{ route('locations.create') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>Add Warehouse</span>
            </a>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="stat-grid-4">
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(37, 99, 235, 0.12); color: #2563eb;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                    <circle cx="12" cy="10" r="3"/>
                </svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Total Locations</span>
                <span class="stat-num">{{ number_format($metrics['total_locations'] ?? count($rows)) }}</span>
                <span class="stat-sub">Defined warehouse areas</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(124, 58, 237, 0.12); color: #7c3aed;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                </svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Warehouses</span>
                <span class="stat-num">{{ number_format($metrics['warehouses'] ?? 1) }}</span>
                <span class="stat-sub">Primary facility sites</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(16, 185, 129, 0.12); color: #10b981;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Available Bins</span>
                <span class="stat-num">{{ number_format($availableBins) }}</span>
                <span class="stat-sub">Empty and operational</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(245, 158, 11, 0.12); color: #f59e0b;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                </svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Occupied Bins</span>
                <span class="stat-num">{{ number_format($occupiedBins) }}</span>
                <span class="stat-sub">Containing active stock</span>
            </div>
        </div>
    </div>

    <!-- Search and Filter -->
    <div class="filter-card">
        <form method="GET" action="{{ route('locations.index') }}" style="display: flex; align-items: center; gap: 10px; width: 100%; flex-wrap: wrap; margin: 0;">
            <div style="position: relative; flex: 1; min-width: 260px;">
                <svg style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none;" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>

                <input
                    type="search"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Search warehouse, receiving, staging, zone, rack or bin..."
                    aria-label="Search locations"
                    style="width: 100%;"
                >
            </div>

            <button type="submit" class="button">Filter</button>

            @if (request()->filled('search'))
                <a class="button secondary" href="{{ route('locations.index') }}">Reset</a>
            @endif
        </form>
    </div>

    <!-- Location Hierarchy Table -->
    <div class="modern-table-card">
        <table class="clean-table">
            <thead>
                <tr>
                    <th style="width: 32%;">Location Hierarchy</th>
                    <th style="width: 12%;">Type</th>
                    <th style="width: 14%;">Code</th>
                    <th style="width: 12%;">Availability</th>
                    <th style="width: 10%;">Status</th>
                    <th style="width: 20%; text-align: right;">Actions</th>
                </tr>
            </thead>

            <tbody>
            @forelse ($rows as $row)
                @php
                    $location = $row['location'];
                    $isOperational = $location->isOperational();

                    $availability = $location->type !== 'bin'
                        ? '—'
                        : ($row['quantity'] > 0
                            ? 'Occupied'
                            : ($isOperational ? 'Available' : 'Inactive'));

                    $typeBadgeColor = match ($location->type) {
                        'warehouse' => 'background: #eff6ff; color: #1d4ed8;',
                        'receiving' => 'background: #ecfeff; color: #0e7490;',
                        'staging' => 'background: #fff7ed; color: #c2410c;',
                        'zone' => 'background: #fdf4ff; color: #a21caf;',
                        'rack' => 'background: #fffbeb; color: #b45309;',
                        'bin' => 'background: #f0fdf4; color: #15803d;',
                        default => 'background: #f1f5f9; color: #475569;',
                    };
                @endphp

                <tr>
                    <!-- Location Name -->
                    <td style="padding-left: {{ 16 + $row['depth'] * 26 }}px">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            @if ($row['depth'] > 0)
                                <span style="color: #cbd5e1; font-weight: 300;">└─</span>
                            @endif

                            <a
                                href="{{ route('locations.show', $location) }}"
                                style="font-weight: {{ $location->type === 'warehouse' ? '800' : ($location->type === 'bin' ? '500' : '700') }}; color: #0f172a; text-decoration: none;"
                            >
                                {{ $location->name }}
                            </a>
                        </div>
                    </td>

                    <!-- Location Type -->
                    <td>
                        <span style="display: inline-block; font-size: 11px; font-weight: 700; text-transform: uppercase; padding: 2px 8px; border-radius: 4px; {{ $typeBadgeColor }}">
                            {{ $location->type }}
                        </span>
                    </td>

                    <!-- Location Code -->
                    <td>
                        @if ($location->code)
                            <span class="pill-code">{{ $location->code }}</span>
                        @else
                            <span style="color: #94a3b8;">—</span>
                        @endif
                    </td>

                    <!-- Availability -->
                    <td>
                        @if ($availability === 'Available')
                            <span class="badge-success">Available</span>
                        @elseif ($availability === 'Occupied')
                            <span class="badge-warning">Occupied</span>
                        @elseif ($availability === 'Inactive')
                            <span class="badge-danger">Inactive</span>
                        @else
                            <span style="color: #94a3b8;">—</span>
                        @endif
                    </td>

                    <!-- Status -->
                    <td>
                        @if ($isOperational)
                            <span class="badge-success">Active</span>
                        @else
                            <span class="badge-muted">Disabled</span>
                        @endif
                    </td>

                    <!-- Actions -->
                    <td style="text-align: right;">
                        <div style="display: inline-flex; align-items: center; gap: 5px; justify-content: flex-end; flex-wrap: wrap;">

                            <a
                                href="{{ route('locations.edit', $location) }}"
                                class="button secondary"
                                style="padding: 3px 8px; font-size: 11.5px;"
                            >Edit</a>

                            <a
                                href="{{ route('locations.label', $location) }}"
                                class="button secondary"
                                target="_blank"
                                style="padding: 3px 8px; font-size: 11.5px;"
                            >QR</a>

                            <!-- Create Allowed Child Locations -->
                            @foreach ($location->childTypes() as $nextType)
                                <a
                                    href="{{ route('locations.create', [
                                        'type' => $nextType,
                                        'parent_id' => $location->id
                                    ]) }}"
                                    class="button"
                                    style="padding: 3px 8px; font-size: 11.5px; background: #2563eb;"
                                >
                                    + {{ ucfirst($nextType) }}
                                </a>
                            @endforeach

                            <!-- Deactivate or Activate -->
                            @if ($location->status === 'active')
                                <form
                                    class="inline-form"
                                    method="POST"
                                    action="{{ route('locations.deactivate', $location) }}"
                                    style="margin: 0;"
                                >
                                    @csrf
                                    @method('PATCH')

                                    <button
                                        class="button secondary"
                                        type="submit"
                                        style="padding: 3px 6px; font-size: 11.5px; color: #d97706;"
                                    >
                                        Deactivate
                                    </button>
                                </form>
                            @else
                                <form
                                    class="inline-form"
                                    method="POST"
                                    action="{{ route('locations.activate', $location) }}"
                                    style="margin: 0;"
                                >
                                    @csrf
                                    @method('PATCH')

                                    <button
                                        class="button secondary"
                                        type="submit"
                                        style="padding: 3px 6px; font-size: 11.5px; color: #16a34a;"
                                    >
                                        Activate
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>

            @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 36px 20px; color: #64748b;">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" style="margin-bottom: 8px;">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>

                        <p style="margin: 0; font-weight: 600; color: #334155;">
                            No locations match the query
                        </p>

                        <small style="color: #94a3b8;">
                            Try clearing the search query or create a new warehouse location.
                        </small>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
