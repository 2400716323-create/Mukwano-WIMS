
@extends('catalog.layout')

@section('title', 'Put-away Tasks')

@section('breadcrumbs')
    <span class="breadcrumbs-separator">/</span>
    <span style="color: #0f172a; font-weight: 600;">Put-away Tasks</span>
@endsection

@section('content')
    {{-- Breadcrumb navigation --}}
    <nav style="display:flex;align-items:center;gap:8px;font-size:12.5px;color:#64748b;margin-bottom:14px;" aria-label="Breadcrumb">
        <a href="{{ route('dashboard') }}" style="color:#64748b;text-decoration:none;display:flex;align-items:center;gap:4px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
            <span>Dashboard</span>
        </a>
        <span style="color:#cbd5e1;">/</span>
        <span style="color:#0f172a;font-weight:600;">Put-away Tasks</span>
    </nav>

    {{-- Page header --}}
    <div class="page-header-card">
        <div class="page-title-wrap">
            <h1>Put-away Tasks</h1>
            <p>Move received inventory from Receiving and Staging Areas, or other eligible source locations, into designated storage bins and racks.</p>
        </div>

        <div class="page-actions-wrap">
            <a class="button secondary" href="{{ route('goods-receipts.index') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                </svg>
                <span>Goods Receipts</span>
            </a>

            <a class="button secondary" href="{{ route('inventory.index') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4z"/>
                </svg>
                <span>Stock Overview</span>
            </a>
        </div>
    </div>

    {{-- KPI cards --}}
    <div class="stat-grid-4">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(37,99,235,.12);color:#2563eb;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="17 1 21 5 17 9"/>
                    <path d="M3 11V9a4 4 0 0 1 4-4h14"/>
                    <polyline points="7 23 3 19 7 15"/>
                    <path d="M21 13v2a4 4 0 0 1-4 4H3"/>
                </svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Moveable Balances</span>
                <span class="stat-num">{{ number_format($metrics['total_tasks'] ?? $stockBalances->total()) }}</span>
                <span class="stat-sub">Eligible stock holdings</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(16,185,129,.12);color:#10b981;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="7" width="20" height="14" rx="2"/>
                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                </svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Total Units</span>
                <span class="stat-num">{{ number_format($metrics['total_units'] ?? 0) }}</span>
                <span class="stat-sub">Units across eligible locations</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(245,158,11,.12);color:#f59e0b;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4z"/>
                </svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Active Batches</span>
                <span class="stat-num">{{ number_format($metrics['distinct_batches'] ?? 0) }}</span>
                <span class="stat-sub">Distinct stock lots</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(124,58,237,.12);color:#7c3aed;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                    <circle cx="12" cy="10" r="3"/>
                </svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Available Bins</span>
                <span class="stat-num">{{ number_format($metrics['bins_count'] ?? 0) }}</span>
                <span class="stat-sub">Operational storage bins</span>
            </div>
        </div>
    </div>

    {{-- Success and error messages --}}
    @if (session('status'))
        <div class="card" style="padding:12px 16px;margin-bottom:14px;border-left:4px solid #16a34a;color:#166534;">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->has('task'))
        <div class="card" style="padding:12px 16px;margin-bottom:14px;border-left:4px solid #dc2626;color:#991b1b;">
            {{ $errors->first('task') }}
        </div>
    @endif

    {{-- Assigned tasks --}}
    <div class="card" style="padding:18px;margin-bottom:18px;">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:12px;">
            <div>
                <h2 style="margin:0;font-size:16px;color:#0f172a;">Assigned Put-away Tasks</h2>
                <p style="margin:4px 0 0;color:#64748b;font-size:12px;">
                    Track assigned work and confirm stock movements when tasks are completed.
                </p>
            </div>
            <span class="badge-info">{{ $putAwayTasks->count() }} tasks shown</span>
        </div>

        <div style="overflow-x:auto;">
            <table class="clean-table">
                <thead>
                    <tr>
                        <th>Task</th>
                        <th>Product / Batch</th>
                        <th>Move</th>
                        <th>Assigned To</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th style="text-align:right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($putAwayTasks as $task)
                        @php
                            $taskBadge = match($task->status) {
                                'completed' => 'badge-success',
                                'in_progress' => 'badge-warning',
                                default => 'badge-info',
                            };
                        @endphp

                        <tr>
                            <td>
                                <strong>{{ $task->task_number }}</strong><br>
                                <small>{{ $task->creator?->name ?? '—' }} created</small>
                            </td>
                            <td>
                                <strong>{{ $task->batch?->product?->name ?? 'Product unavailable' }}</strong><br>
                                <small>{{ $task->batch?->batch_number ?? '—' }}</small>
                            </td>
                            <td>
                                {{ number_format((float) $task->quantity, 3) }}
                                {{ $task->batch?->product?->unit ?? 'units' }}<br>
                                <small>
                                    {{ $task->sourceLocation?->code ?? $task->sourceLocation?->name ?? '—' }}
                                    →
                                    {{ $task->destinationLocation?->code ?? $task->destinationLocation?->name ?? '—' }}
                                </small>
                            </td>
                            <td>{{ $task->assignee?->name ?? 'Unassigned' }}</td>
                            <td>
                                <span class="{{ $taskBadge }}">
                                    {{ ucwords(str_replace('_', ' ', $task->status)) }}
                                </span>
                            </td>
                            <td>{{ $task->created_at?->format('d M Y H:i') }}</td>
                            <td style="text-align:right;white-space:nowrap;">
                                @if ($task->status === 'pending' && (auth()->user()->isSupervisor() || (int) $task->assigned_to === (int) auth()->id()))
                                    <form method="POST" action="{{ route('put-away.tasks.start', $task) }}" style="display:inline;">
                                        @csrf
                                        @method('PATCH')
                                        <button class="button" type="submit" style="padding:6px 10px;font-size:11px;">Start</button>
                                    </form>
                                @elseif ($task->status === 'in_progress' && (auth()->user()->isSupervisor() || (int) $task->assigned_to === (int) auth()->id()))
                                    <form method="POST" action="{{ route('put-away.tasks.complete', $task) }}" style="display:inline;" onsubmit="return confirm('Confirm that this stock has physically been moved to the destination bin?');">
                                        @csrf
                                        @method('PATCH')
                                        <button class="button" type="submit" style="padding:6px 10px;font-size:11px;background:#15803d;">Complete & Move Stock</button>
                                    </form>
                                @else
                                    <span style="color:#94a3b8;font-size:12px;">No action</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align:center;padding:24px;color:#64748b;">
                                No put-away tasks have been created yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Search and filter --}}
    <div class="filter-card">
        <form method="GET" action="{{ route('put-away.index') }}" style="display:flex;align-items:center;gap:10px;width:100%;flex-wrap:wrap;margin:0;">
            <div style="position:relative;flex:1;min-width:260px;">
                <svg style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94a3b8;pointer-events:none;" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Search by product SKU, name, batch number, or location code..." aria-label="Search put-away stock" style="width:100%;">
            </div>

            <button type="submit" class="button">Filter</button>

            @if (request()->filled('search'))
                <a class="button secondary" href="{{ route('put-away.index') }}">Reset</a>
            @endif
        </form>
    </div>

    {{-- Stock balances available for put-away --}}
    <div class="modern-table-card">
        <div style="padding:18px 18px 8px;">
            <h2 style="margin:0;font-size:16px;color:#0f172a;">Stock Balances Available for Put-away</h2>
            <p style="margin:4px 0 0;color:#64748b;font-size:12px;">
                Inventory held in Receiving Areas, Staging Areas and eligible storage locations. Select a balance to move it into an operational storage bin.
            </p>
        </div>

        <div style="overflow-x:auto;">
            <table class="clean-table">
                <thead>
                    <tr>
                        <th style="width:25%;">Product</th>
                        <th style="width:15%;">Batch Details</th>
                        <th style="width:20%;">Current Source Location</th>
                        <th style="width:12%;text-align:right;">Quantity</th>
                        <th style="width:14%;">Expiry Status</th>
                        <th style="width:14%;text-align:right;">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($stockBalances as $balance)
                        @php
                            $product = $balance->batch?->product;
                            $batch = $balance->batch;
                            $location = $balance->location;

                            $expiryStatus = $batch?->expiry_status ?? 'unknown';

                            $expiryClass = match($expiryStatus) {
                                'good' => 'badge-success',
                                'expiring_soon' => 'badge-warning',
                                'expired' => 'badge-danger',
                                default => 'badge-info',
                            };
                        @endphp

                        <tr>
                            <td>
                                <div style="display:flex;flex-direction:column;">
                                    @if ($product)
                                        <a href="{{ route('products.show', $product) }}" style="font-weight:700;color:#0f172a;text-decoration:none;">
                                            {{ $product->name }}
                                        </a>
                                        <div style="display:flex;align-items:center;gap:6px;margin-top:2px;">
                                            <span class="pill-code">{{ $product->sku }}</span>
                                            <span style="font-size:11px;color:#64748b;">
                                                {{ $product->category?->name ?? '' }}
                                            </span>
                                        </div>
                                    @else
                                        <strong>Product unavailable</strong>
                                    @endif
                                </div>
                            </td>

                            <td>
                                @if ($batch)
                                    <div style="display:flex;flex-direction:column;">
                                        <a href="{{ route('batches.show', $batch) }}" style="font-family:monospace;font-weight:700;color:#2563eb;text-decoration:none;">
                                            {{ $batch->batch_number }}
                                        </a>
                                        <span style="font-size:11px;color:#64748b;">
                                            Exp: {{ $batch->expires_at?->format('d M Y') ?? 'N/A' }}
                                        </span>
                                    </div>
                                @else
                                    <span>Batch unavailable</span>
                                @endif
                            </td>

                            <td>
                                @if ($location)
                                    <div style="display:flex;flex-direction:column;">
                                        <a href="{{ route('locations.show', $location) }}" style="font-weight:600;color:#334155;text-decoration:none;">
                                            {{ $location->name }}{{ $location->code ? ' (' . $location->code . ')' : '' }}
                                        </a>
                                        <span style="font-size:11px;color:#64748b;">
                                            {{ $location->parent?->name ?? '—' }}
                                            &rsaquo;
                                            {{ ucfirst($location->type) }}
                                        </span>
                                    </div>
                                @else
                                    <span>Location unavailable</span>
                                @endif
                            </td>

                            <td style="text-align:right;font-weight:700;font-size:14px;color:#0f172a;">
                                {{ number_format((float) $balance->quantity, 3) }}
                                <span style="font-size:11px;font-weight:normal;color:#64748b;">
                                    {{ $product?->unit ?? 'units' }}
                                </span>
                            </td>

                            <td>
                                <span class="{{ $expiryClass }}">
                                    {{ ucwords(str_replace('_', ' ', $expiryStatus)) }}
                                </span>
                            </td>

                            <td style="text-align:right;">
                                @if ($canManageTasks)
                                    <a href="{{ route('put-away.tasks.create', $balance) }}" class="button" style="padding:5px 12px;font-size:12px;background:#2563eb;">
                                        Create Task
                                    </a>
                                @else
                                    <a href="{{ route('put-away.show', $balance) }}" class="button" style="padding:5px 12px;font-size:12px;background:#64748b;">
                                        Direct Move
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center;padding:36px 20px;color:#64748b;">
                                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" style="margin-bottom:8px;">
                                    <polyline points="17 1 21 5 17 9"/>
                                    <path d="M3 11V9a4 4 0 0 1 4-4h14"/>
                                    <polyline points="7 23 3 19 7 15"/>
                                    <path d="M21 13v2a4 4 0 0 1-4 4H3"/>
                                </svg>
                                <p style="margin:0;font-weight:600;color:#334155;">No stock currently awaiting put-away</p>
                                <small style="color:#94a3b8;">
                                    There are no eligible stock balances at the moment, or no matching results were found.
                                </small>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div style="padding:14px 18px;">
            {{ $stockBalances->links() }}
        </div>
    </div>
@endsection
