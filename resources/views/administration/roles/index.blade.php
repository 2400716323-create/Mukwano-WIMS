@extends('catalog.layout')

@section('title', 'Roles and permissions')

@section('breadcrumbs')
    <a href="{{ route('admin.users.index') }}">User Management</a>
    <span class="sep">/</span>
    <span class="current">Roles & Permissions</span>
@endsection

@section('content')
    <!-- Unified Page Header -->
    <div class="page-header-card">
        <div class="page-title-wrap">
            <nav class="breadcrumb-trail" aria-label="Breadcrumb">
                <a href="{{ route('dashboard') }}">Dashboard</a>
                <span class="separator">/</span>
                <a href="{{ route('admin.users.index') }}">User Management</a>
                <span class="separator">/</span>
                <span class="current">Roles & Permissions</span>
            </nav>
            <h1>Roles & Permissions</h1>
            <p>Role-based access control matrix (RBAC). Roles are enforced at route and controller policy gates.</p>
        </div>
        <div class="page-actions-wrap">
            <a class="button secondary" href="{{ route('admin.users.index') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                <span>User Management</span>
            </a>
        </div>
    </div>

    <!-- Roles Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 16px;">
        @foreach ($roles as $role => $definition)
            <div class="card" style="padding: 20px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="display: inline-flex; width: 28px; height: 28px; border-radius: 6px; background: rgba(37, 99, 235, 0.1); color: #2563eb; align-items: center; justify-content: center;">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        </span>
                        <h2 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0;">{{ $definition['label'] }}</h2>
                    </div>
                    <span class="pill-code" style="font-size: 11px;">{{ $role }}</span>
                </div>
                <p style="font-size: 12px; color: #64748b; margin: 0 0 12px;">Authorized Capabilities:</p>
                <div style="display: flex; flex-direction: column; gap: 6px;">
                    @foreach ($definition['permissions'] as $permission)
                        <div style="display: flex; align-items: center; gap: 8px; font-size: 12.5px; color: #334155;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                            <span>{{ str($permission)->replace('.', ' › ')->replace('_', ' ')->title() }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
@endsection
