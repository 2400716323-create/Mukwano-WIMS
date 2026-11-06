@extends('catalog.layout')

@section('title', 'User management')

@section('breadcrumbs')
    <span class="current">User Management</span>
@endsection

@section('content')
    <!-- Unified Page Header -->
    <div class="page-header-card">
        <div class="page-title-wrap">
            <nav class="breadcrumb-trail" aria-label="Breadcrumb">
                <a href="{{ route('dashboard') }}">Dashboard</a>
                <span class="separator">/</span>
                <span class="current">User Management</span>
            </nav>
            <h1>User Management</h1>
            <p>Maintain warehouse user accounts, assign role permissions, and control security access.</p>
        </div>
        <div class="page-actions-wrap">
            <a class="button secondary" href="{{ route('admin.roles.index') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                <span>Roles & Permissions</span>
            </a>
            <a class="button secondary" href="{{ route('admin.audit.index') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                <span>Audit Logs</span>
            </a>
        </div>
    </div>

    <!-- 4 Top KPI Cards -->
    <div class="stat-grid-4">
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(37, 99, 235, 0.12); color: #2563eb;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Total Users</span>
                <span class="stat-num">{{ number_format($metrics['total'] ?? $users->total()) }}</span>
                <span class="stat-sub">Registered logins</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(16, 185, 129, 0.12); color: #10b981;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Active Accounts</span>
                <span class="stat-num">{{ number_format($metrics['active'] ?? 0) }}</span>
                <span class="stat-sub">Permitted to sign in</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(124, 58, 237, 0.12); color: #7c3aed;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Administrators</span>
                <span class="stat-num">{{ number_format($metrics['admins'] ?? 0) }}</span>
                <span class="stat-sub">Full system control</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(100, 116, 139, 0.12); color: #64748b;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-title">Inactive Users</span>
                <span class="stat-num">{{ number_format($metrics['inactive'] ?? 0) }}</span>
                <span class="stat-sub">Deactivated credentials</span>
            </div>
        </div>
    </div>

    <!-- Create User Card -->
    <details class="card" style="margin-bottom: 20px; padding: 20px;">
        <summary style="font-weight: 700; font-size: 15px; color: #0f172a; cursor: pointer; display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="display: inline-flex; width: 28px; height: 28px; border-radius: 6px; background: rgba(37, 99, 235, 0.1); color: #2563eb; align-items: center; justify-content: center;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                </span>
                <span>Create New User Account</span>
            </div>
            <span style="font-size: 12px; color: #64748b; font-weight: normal;">Click to expand</span>
        </summary>

        <form method="post" action="{{ route('admin.users.store') }}" style="margin-top: 18px;">
            @csrf
            <div class="form-grid">
                <div class="field">
                    <label for="name">Full Name</label>
                    <input id="name" name="name" value="{{ old('name') }}" required maxlength="255" placeholder="e.g. John Mukwano">
                </div>
                <div class="field">
                    <label for="email">Email Address</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required maxlength="255" placeholder="e.g. jmukwano@mukwano.com">
                </div>
                <div class="field">
                    <label for="role">System Role</label>
                    <select id="role" name="role" required>
                        @foreach ($roles as $key => $role)
                            <option value="{{ $key }}" @selected(old('role', 'user') === $key)>{{ $role['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div></div>
                <div class="field">
                    <label for="password">Temporary Password (min 12 chars)</label>
                    <div class="password-field-wrap">
                        <input id="password" name="password" type="password" autocomplete="new-password" minlength="12" required placeholder="••••••••••••">
                        <button type="button" class="password-toggle-btn" aria-label="Toggle password visibility">
                            <svg class="eye-open" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg class="eye-closed" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                        </button>
                    </div>
                </div>
                <div class="field">
                    <label for="password_confirmation">Confirm Password</label>
                    <div class="password-field-wrap">
                        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required placeholder="••••••••••••">
                        <button type="button" class="password-toggle-btn" aria-label="Toggle password visibility">
                            <svg class="eye-open" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg class="eye-closed" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                        </button>
                    </div>
                </div>
            </div>
            <div style="margin-top: 14px;">
                <button type="submit" class="button">Create Account</button>
            </div>
        </form>
    </details>

    <!-- Modern Table Card: Users -->
    <div class="modern-table-card">
        <div style="padding: 14px 18px; border-bottom: 1px solid #f1f5f9;">
            <h2 style="margin: 0; font-size: 15px; color: #0f172a;">Active & Registered Accounts</h2>
        </div>
        <table class="clean-table">
            <thead>
                <tr>
                    <th style="width: 25%;">User</th>
                    <th style="width: 25%;">Email Address</th>
                    <th style="width: 15%;">Role</th>
                    <th style="width: 10%;">Status</th>
                    <th style="width: 25%; text-align: right;">Permissions & Control</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($users as $user)
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <div style="width: 32px; height: 32px; border-radius: 50%; background: #2563eb; color: #fff; font-weight: 700; font-size: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                            <span style="font-weight: 700; color: #0f172a;">{{ $user->name }}</span>
                        </div>
                    </td>
                    <td>
                        <span style="color: #64748b; font-size: 12.5px;">{{ $user->email }}</span>
                    </td>
                    <td>
                        <span class="pill-code" style="text-transform: capitalize;">{{ $roles[$user->role]['label'] ?? $user->role }}</span>
                    </td>
                    <td>
                        @if ($user->is_active)
                            <span class="badge-success">Active</span>
                        @else
                            <span class="badge-danger">Suspended</span>
                        @endif
                    </td>
                    <td style="text-align: right;">
                        <div style="display: inline-flex; align-items: center; gap: 6px; justify-content: flex-end;">
                            <form class="inline-form" method="post" action="{{ route('admin.users.role', $user) }}" style="margin: 0;">
                                @csrf
                                @method('PATCH')
                                <select name="role" aria-label="Role for {{ $user->name }}" style="padding: 3px 6px; font-size: 11px;">
                                    @foreach ($roles as $key => $role)
                                        <option value="{{ $key }}" @selected($user->role === $key)>{{ $role['label'] }}</option>
                                    @endforeach
                                </select>
                                <button class="button secondary" type="submit" style="padding: 3px 8px; font-size: 11px;">Update</button>
                            </form>
                            @if ($user->is_active)
                                <form class="inline-form" method="post" action="{{ route('admin.users.deactivate', $user) }}" style="margin: 0;">
                                    @csrf
                                    @method('PATCH')
                                    <button class="button secondary" type="submit" style="padding: 3px 8px; font-size: 11px; color: #d97706;">Suspend</button>
                                </form>
                            @else
                                <form class="inline-form" method="post" action="{{ route('admin.users.activate', $user) }}" style="margin: 0;">
                                    @csrf
                                    @method('PATCH')
                                    <button class="button secondary" type="submit" style="padding: 3px 8px; font-size: 11px; color: #16a34a;">Restore</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; padding: 36px 20px; color: #64748b;">No user accounts found.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div style="margin-top: 14px;">
        {{ $users->links() }}
    </div>
@endsection
