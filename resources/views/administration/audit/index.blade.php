@extends('catalog.layout')

@section('title', 'Audit log')

@section('breadcrumbs')
    <span class="current">Audit Log</span>
@endsection

@section('content')
    <!-- Unified Page Header -->
    <div class="page-header-card">
        <div class="page-title-wrap">
            <nav class="breadcrumb-trail" aria-label="Breadcrumb">
                <a href="{{ route('dashboard') }}">Dashboard</a>
                <span class="separator">/</span>
                <span class="current">Audit Log</span>
            </nav>
            <h1>Audit Log</h1>
            <p>Immutable forensic record of all user logins, inventory postings, authorizations, and master data revisions.</p>
        </div>
        <div class="page-actions-wrap">
            <a class="button secondary" href="{{ route('admin.users.index') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                <span>User Management</span>
            </a>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="filter-card">
        <form method="get" action="{{ route('admin.audit.index') }}" style="display: flex; align-items: center; gap: 8px; width: 100%; flex-wrap: wrap; margin: 0;">
            <select id="user_id" name="user_id" style="font-size: 12px; padding: 6px 10px;">
                <option value="">All Actors</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" @selected(($filters['user_id'] ?? '') == $user->id)>{{ $user->name }}</option>
                @endforeach
            </select>
            <select id="event" name="event" style="font-size: 12px; padding: 6px 10px;">
                <option value="">All Events</option>
                @foreach ($events as $event)
                    <option value="{{ $event }}" @selected(($filters['event'] ?? '') === $event)>{{ str($event)->replace('_', ' ')->title() }}</option>
                @endforeach
            </select>
            <select id="auditable_type" name="auditable_type" style="font-size: 12px; padding: 6px 10px;">
                <option value="">All Record Types</option>
                @foreach ($recordTypes as $recordType)
                    <option value="{{ $recordType }}" @selected(($filters['auditable_type'] ?? '') === $recordType)>{{ class_basename($recordType) }}</option>
                @endforeach
            </select>
            <input id="from" type="date" name="from" value="{{ $filters['from'] ?? '' }}" style="font-size: 12px; padding: 5px 8px;" title="From Date">
            <input id="to" type="date" name="to" value="{{ $filters['to'] ?? '' }}" style="font-size: 12px; padding: 5px 8px;" title="To Date">
            <button type="submit" class="button" style="padding: 6px 14px; font-size: 12px;">Filter</button>
            @if (!empty($filters['user_id']) || !empty($filters['event']) || !empty($filters['auditable_type']) || !empty($filters['from']) || !empty($filters['to']))
                <a class="button secondary" href="{{ route('admin.audit.index') }}" style="padding: 6px 12px; font-size: 12px;">Reset</a>
            @endif
        </form>
    </div>

    <!-- Modern Table Card -->
    <div class="modern-table-card">
        <table class="clean-table">
            <thead>
                <tr>
                    <th style="width: 15%;">Timestamp</th>
                    <th style="width: 14%;">Actor</th>
                    <th style="width: 12%;">Event</th>
                    <th style="width: 15%;">Record Model</th>
                    <th style="width: 22%;">Previous Values</th>
                    <th style="width: 22%;">New Values</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($logs as $log)
                <tr>
                    <td>
                        <span style="color: #64748b; font-size: 12px; font-family: monospace;">{{ $log->created_at->format('Y-m-d H:i:s') }}</span>
                    </td>
                    <td>
                        <span style="font-weight: 600; color: #0f172a; font-size: 12.5px;">{{ $log->user?->name ?? ($log->new_values['email'] ?? 'System') }}</span>
                    </td>
                    <td>
                        <span class="pill-code">{{ str($log->event)->replace('_', ' ')->title() }}</span>
                    </td>
                    <td>
                        <span style="color: #334155; font-size: 12.5px;">{{ class_basename($log->auditable_type) }}{{ $log->auditable_id ? ' #'.$log->auditable_id : '' }}</span>
                    </td>
                    <td>
                        <small style="color: #64748b; font-family: monospace; font-size: 11px; word-break: break-all;">
                            {{ $log->old_values ? json_encode($log->old_values, JSON_UNESCAPED_UNICODE) : '—' }}
                        </small>
                    </td>
                    <td>
                        <small style="color: #334155; font-family: monospace; font-size: 11px; word-break: break-all;">
                            {{ $log->new_values ? json_encode($log->new_values, JSON_UNESCAPED_UNICODE) : '—' }}
                        </small>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 36px 20px; color: #64748b;">No audit records match the filter criteria.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div style="margin-top: 14px;">
        {{ $logs->links() }}
    </div>
@endsection
