<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f172a">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/wims-192.png') }}">
    <title>@yield('title', 'Warehouse Management') · {{ config('app.name', 'Mukwano WIMS') }}</title>
    @vite('resources/js/app.js')
    <style>
        :root {
            --navy-950: #0a0f1d;
            --navy-900: #0f172a;
            --navy-850: #141e33;
            --navy-800: #1e293b;
            --navy-700: #334155;
            --navy-600: #475569;
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --accent-green: #10b981;
            --surface: #ffffff;
            --surface-subtle: #f8fafc;
            --surface-muted: #f1f5f9;
            --border-subtle: #e2e8f0;
            --border-navy: #1e293b;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --text-on-navy: #f8fafc;
            --text-muted-on-navy: #94a3b8;
            --danger: #ef4444;
            --danger-bg: #fef2f2;
            --warning: #f59e0b;
            --warning-bg: #fffbeb;
            --success: #10b981;
            --success-bg: #ecfdf5;
            --sidebar-width: 200px;
            --topbar-height: 56px;
        }

        * {
            box-sizing: border-box;
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 transparent;
        }

        /* Modern Custom Scrollbars */
        ::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 9999px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, "Helvetica Neue", Arial, sans-serif;
            background: var(--surface-subtle);
            color: var(--text-main);
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }

        a { color: var(--primary); text-decoration: none; }
        a:hover { text-decoration: underline; }

        /* App Layout Shell - 100vw Fit */
        .app-shell {
            display: flex;
            min-height: 100vh;
            width: 100%;
            max-width: 100vw;
            overflow-x: hidden;
        }

        /* Slim Navy Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--navy-900);
            color: var(--text-on-navy);
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            border-right: 1px solid var(--navy-800);
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 40;
            overflow-y: auto;
            overflow-x: hidden;
            transition: transform 0.25s ease;
            scrollbar-width: thin;
            scrollbar-color: #334155 #0f172a;
        }

        .sidebar::-webkit-scrollbar {
            width: 4px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: #0f172a;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: #334155;
            border-radius: 9999px;
        }

        .sidebar::-webkit-scrollbar-thumb:hover {
            background: #475569;
        }

        .sidebar-brand {
            height: var(--topbar-height);
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0 14px;
            border-bottom: 1px solid var(--navy-800);
            background: var(--navy-950);
            flex-shrink: 0;
        }

        .sidebar-brand strong {
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 0.02em;
            color: var(--text-on-navy);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.1;
        }

        .sidebar-brand .thumb {
            width: 24px;
            height: 24px;
            object-fit: contain;
            border-radius: 4px;
        }

        .sidebar-nav {
            padding: 10px 8px;
            display: flex;
            flex-direction: column;
            gap: 2px;
            flex: 1;
        }

        .nav-section-title {
            display: none;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 10px;
            color: var(--text-muted-on-navy);
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.15s ease;
            text-decoration: none;
            white-space: nowrap;
        }

        .nav-item svg {
            width: 15px;
            height: 15px;
            flex-shrink: 0;
        }

        .nav-item:hover {
            color: var(--text-on-navy);
            background: var(--navy-800);
            text-decoration: none;
        }

        .nav-item.active {
            color: var(--text-on-navy);
            background: var(--primary);
            font-weight: 600;
        }

        /* Expandable Nav Group (Stock Control) */
        .nav-group {
            margin: 1px 0;
        }

        .nav-group summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 6px 10px;
            color: var(--text-muted-on-navy);
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            user-select: none;
            list-style: none;
            transition: all 0.15s ease;
        }

        .nav-group summary svg {
            width: 15px;
            height: 15px;
            flex-shrink: 0;
        }

        .nav-group summary::-webkit-details-marker { display: none; }

        .nav-group summary:hover {
            color: var(--text-on-navy);
            background: var(--navy-800);
        }

        .nav-group[open] summary {
            color: var(--text-on-navy);
            font-weight: 600;
        }

        .nav-group-items {
            padding-left: 10px;
            margin-top: 2px;
            display: flex;
            flex-direction: column;
            gap: 2px;
            border-left: 2px solid var(--navy-800);
            margin-left: 14px;
        }

        .nav-group-items .nav-item {
            font-size: 11.5px;
            padding: 4px 8px;
        }

        /* Main Content Container - Fits 100vw minus sidebar */
        .app-main-wrap {
            flex: 1;
            margin-left: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width));
            max-width: calc(100% - var(--sidebar-width));
            display: flex;
            flex-direction: column;
            min-width: 0;
            min-height: 100vh;
            background: var(--surface-subtle);
        }

        /* Top Bar */
        .topbar {
            height: var(--topbar-height);
            background: var(--navy-900);
            border-bottom: 1px solid var(--navy-800);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            position: static;
            z-index: 30;
            gap: 20px;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 16px;
            flex: 1;
            max-width: 560px;
        }

        .mobile-toggle {
            display: none;
            background: none;
            border: 0;
            color: var(--text-on-navy);
            padding: 8px;
            cursor: pointer;
            border-radius: 6px;
        }

        .mobile-toggle:hover { background: var(--navy-800); }

        /* Centred Global Search */
        .global-search {
            flex: 1;
            position: relative;
        }

        .global-search input[type="search"] {
            width: 100%;
            height: 38px;
            padding: 0 16px 0 40px;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 9999px;
            font-size: 13px;
            color: var(--navy-950);
            transition: all 0.15s ease;
        }

        .global-search input[type="search"]::placeholder {
            color: #94a3b8;
        }

        .global-search input[type="search"]:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.25);
        }

        .global-search-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            pointer-events: none;
            width: 16px;
            height: 16px;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        /* Short System Status Pill */
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
            background: rgba(16, 185, 129, 0.12);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.25);
            white-space: nowrap;
        }

        .status-pill.offline {
            background: rgba(239, 68, 68, 0.15);
            color: #f87171;
            border-color: rgba(239, 68, 68, 0.3);
        }

        .status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: currentColor;
        }

        /* Notification Icon & Counter */
        .notification-btn {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            color: #cbd5e1;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.15s ease;
        }

        .notification-btn:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.1);
            text-decoration: none;
        }

        .notification-badge {
            position: absolute;
            top: -2px;
            right: -2px;
            background: #ef4444;
            color: white;
            font-size: 10px;
            font-weight: 700;
            padding: 1px 5px;
            border-radius: 9999px;
            border: 2px solid var(--navy-900);
            min-width: 16px;
            text-align: center;
            line-height: 1.2;
        }

        /* User Menu */
        .user-menu {
            display: flex;
            align-items: center;
            gap: 10px;
            padding-left: 12px;
            border-left: 1px solid var(--navy-800);
        }

        .user-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: #2563eb;
            color: #ffffff;
            font-weight: 700;
            font-size: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.15);
            flex-shrink: 0;
        }

        .user-info {
            display: flex;
            flex-direction: column;
            text-align: left;
        }

        .user-name {
            font-size: 13px;
            font-weight: 600;
            color: #ffffff;
            line-height: 1.2;
        }

        .role-badge {
            display: inline-block;
            font-size: 11px;
            font-weight: 500;
            color: #94a3b8;
            text-transform: capitalize;
        }

        .signout-form {
            margin: 0;
        }

        .signout-btn {
            border: 1px solid rgba(255, 255, 255, 0.15);
            background: rgba(255, 255, 255, 0.06);
            color: #cbd5e1;
            border-radius: 6px;
            padding: 5px 10px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .signout-btn:hover {
            background: rgba(239, 68, 68, 0.2);
            color: #f87171;
            border-color: rgba(239, 68, 68, 0.4);
        }

        .sidebar-footer {
            margin-top: auto;
            padding: 10px 14px;
            border-top: 1px solid var(--navy-800);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--navy-950);
            flex-shrink: 0;
        }

        .sidebar-footer-brand {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Main Content Container - Full width 100vw fit */
        main {
            flex: 1;
            padding: 16px 20px 24px;
            max-width: 100%;
            width: 100%;
            margin: 0;
            box-sizing: border-box;
        }

        /* Typography & Layout Primitives */
        h1 { margin: 0 0 4px; font-size: 22px; font-weight: 800; letter-spacing: -0.02em; color: var(--navy-950); }
        h2 { font-size: 16px; font-weight: 700; letter-spacing: -0.01em; color: var(--navy-900); margin: 0 0 10px; }
        p.muted, small { color: var(--text-muted); }

        /* Unified Modern Page Header */
        .page-header-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }

        .page-title-wrap {
            display: flex;
            flex-direction: column;
        }

        .page-title-wrap h1 {
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.02em;
            margin: 0;
            line-height: 1.2;
        }

        .page-title-wrap p {
            font-size: 13px;
            color: #64748b;
            margin: 3px 0 0;
        }

        .page-actions-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        /* Unified Top Metric KPI Cards Grid */
        .stat-grid-4 {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 16px;
        }

        @media (max-width: 1280px) {
            .stat-grid-4 {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .stat-grid-4 {
                grid-template-columns: 1fr;
            }
        }

        .stat-grid-3 {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 16px;
        }

        @media (max-width: 900px) {
            .stat-grid-3 {
                grid-template-columns: 1fr;
            }
        }

        .stat-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px 16px;
            display: flex;
            align-items: center;
            gap: 14px;
            text-decoration: none;
            color: inherit;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
            transition: all 0.15s ease;
            min-width: 0;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 14px rgba(0,0,0,0.05);
            border-color: #cbd5e1;
            text-decoration: none;
        }

        .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .stat-body {
            display: flex;
            flex-direction: column;
            gap: 2px;
            min-width: 0;
            overflow: hidden;
        }

        .stat-title {
            font-size: 12.5px;
            font-weight: 600;
            color: #64748b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .stat-num {
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.15;
            letter-spacing: -0.02em;
        }

        .stat-sub {
            font-size: 11.5px;
            color: #94a3b8;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Unified Search & Filter Bar Card */
        .filter-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }

        .filter-card input[type="search"] {
            padding: 7px 14px 7px 32px;
            border: 1px solid #cbd5e1;
            border-radius: 9999px;
            font-size: 12.5px;
            background: #ffffff;
            min-width: 240px;
        }

        .filter-card select {
            padding: 6px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 12.5px;
            background: #ffffff;
            color: #334155;
        }

        /* Unified Modern Table Card */
        .modern-table-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
            margin-bottom: 16px;
        }

        .clean-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .clean-table th {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #94a3b8;
            background: #ffffff;
            border-bottom: 1px solid #f1f5f9;
            padding: 10px 14px;
            white-space: nowrap;
        }

        .clean-table th a {
            color: #64748b;
            text-decoration: none;
        }

        .clean-table th a:hover {
            color: var(--primary);
        }

        .clean-table td {
            font-size: 13px;
            padding: 11px 14px;
            border-bottom: 1px solid #f8fafc;
            vertical-align: middle;
            color: #334155;
        }

        .clean-table tr:last-child td { border-bottom: 0; }
        .clean-table tbody tr:hover { background: #fbfcfe; }

        /* Unified Badges */
        .badge {
            display: inline-block;
            font-size: 11px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 9999px;
            text-align: center;
            white-space: nowrap;
        }

        .badge-success { background: #dcfce7; color: #16a34a; }
        .badge-info { background: #dbeafe; color: #2563eb; }
        .badge-warning { background: #ffedd5; color: #ea580c; }
        .badge-danger { background: #fee2e2; color: #dc2626; }
        .badge-purple { background: #f3e8ff; color: #7c3aed; }

        .pill-code {
            display: inline-block;
            background: #f1f5f9;
            color: #334155;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 11.5px;
            font-weight: 600;
            padding: 2px 6px;
            border-radius: 4px;
        }

        .page-head, .toolbar, .actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .page-head { justify-content: space-between; margin-bottom: 20px; }
        .toolbar { margin: 18px 0; }
        .card, .table-wrap { background: var(--surface); border: 1px solid var(--border-subtle); border-radius: 10px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
        .table-wrap { padding: 0; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 12px 16px; border-bottom: 1px solid var(--surface-muted); vertical-align: middle; font-size: 14px; }
        th { background: var(--surface-subtle); font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; color: var(--text-muted); white-space: nowrap; }
        tr:last-child td { border-bottom: 0; }
        label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: var(--navy-800); }
        input, select, textarea { font: inherit; font-size: 14px; padding: 9px 12px; border: 1px solid var(--border-subtle); border-radius: 6px; background: var(--surface); color: var(--text-main); max-width: 100%; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12); }
        input[type=checkbox] { width: auto; }
        textarea { min-height: 90px; width: 100%; }
        .field { margin-bottom: 16px; }
        .field input:not([type=checkbox]), .field select { width: 100%; }
        .password-field-wrap { position: relative; display: flex; align-items: center; width: 100%; }
        .password-field-wrap input { padding-right: 42px !important; width: 100%; }
        .password-toggle-btn { position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: transparent !important; border: 0 !important; padding: 6px !important; cursor: pointer; color: #64748b; display: inline-flex; align-items: center; justify-content: center; border-radius: 4px; line-height: 1; }
        .password-toggle-btn:hover { color: #1e293b; background: #f1f5f9 !important; }
        .form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0 18px; }
        .full { grid-column: 1 / -1; }
        .button, button { display: inline-flex; align-items: center; justify-content: center; gap: 6px; border: 0; border-radius: 6px; padding: 9px 14px; background: var(--primary); color: white; font: inherit; font-size: 13px; font-weight: 600; text-decoration: none; cursor: pointer; transition: all 0.15s ease; }
        .button:hover, button:hover { background: var(--primary-hover); text-decoration: none; }
        .button.secondary, button.secondary { background: var(--surface-muted); color: var(--navy-800); border: 1px solid var(--border-subtle); }
        .button.secondary:hover, button.secondary:hover { background: #e2e8f0; }
        .button.danger, button.danger { background: var(--danger); color: white; }
        .inline-form { display: inline-flex; gap: 6px; align-items: center; margin: 0; }
        .inline-form select { min-width: 125px; }
        .status { display: inline-block; border-radius: 20px; padding: 3px 9px; font-size: 12px; font-weight: 600; background: var(--success-bg); color: var(--success); }
        .status.inactive { background: var(--danger-bg); color: var(--danger); }
        .notice { margin: 0 0 18px; padding: 12px 16px; border-radius: 8px; background: var(--success-bg); color: #065f46; border: 1px solid rgba(16,185,129,0.2); font-size: 14px; }
        .errors { background: var(--danger-bg); color: #991b1b; border: 1px solid rgba(239,68,68,0.2); }
        .errors ul { margin: 6px 0 0; padding-left: 20px; }
        .pagination { margin-top: 18px; }
        .thumb { width: 44px; height: 44px; object-fit: cover; border-radius: 6px; background: var(--surface-muted); }
        .preview { width: 180px; height: 180px; object-fit: contain; border: 1px solid var(--border-subtle); border-radius: 8px; background: var(--surface-subtle); padding: 8px; }
        .inline-image { display: flex; align-items: center; gap: 14px; }
        .detail-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
        .detail-grid div { padding: 10px 0; border-bottom: 1px solid var(--border-subtle); }
        .pwa-tools { display: flex; justify-content: flex-end; gap: 10px; align-items: center; margin-bottom: 16px; }
        .offline-queue { margin-bottom: 18px; }
        .offline-queue[hidden], #install-app[hidden] { display: none !important; }

        .install-app-btn {
            background: rgba(37, 99, 235, 0.15);
            border: 1px solid rgba(59, 130, 246, 0.4);
            color: #60a5fa;
            border-radius: 9999px;
            padding: 5px 12px;
            font-size: 11.5px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            transition: all 0.15s ease;
            white-space: nowrap;
        }

        .install-app-btn:hover {
            background: rgba(37, 99, 235, 0.28);
            border-color: #3b82f6;
            color: #ffffff;
            transform: translateY(-1px);
        }

        .install-app-btn[hidden] {
            display: none !important;
        }
        #offline-queue-items { padding-left: 20px; }
        #offline-queue-items li { margin: 12px 0; }
        #offline-queue-items p { margin: 6px 0; }
        #offline-queue-items button { margin-left: 10px; }

        /* Mobile Bottom Action Bar */
        .mobile-bottom-bar {
            display: none;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 56px;
            background: var(--navy-900);
            border-top: 1px solid var(--border-navy);
            z-index: 50;
            justify-content: space-around;
            align-items: center;
        }

        .mobile-bar-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 3px;
            color: var(--text-muted-on-navy);
            font-size: 11px;
            text-decoration: none;
            padding: 6px 12px;
            border-radius: 6px;
        }

        .mobile-bar-item.active, .mobile-bar-item:hover {
            color: var(--text-on-navy);
            text-decoration: none;
        }

        .sidebar-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 39;
        }

        @media (max-width: 900px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.open {
                transform: translateX(0);
            }
            .sidebar-backdrop.open {
                display: block;
            }
            .app-main-wrap {
                margin-left: 0;
            }
            .mobile-toggle {
                display: flex;
            }
            .mobile-bottom-bar {
                display: flex;
            }
            main {
                padding-bottom: 72px;
            }
            .user-info {
                display: none;
            }
            .form-grid, .detail-grid {
                grid-template-columns: 1fr;
            }
            .full {
                grid-column: auto;
            }
        }
    </style>
</head>
<body>
<div class="app-shell">
    @auth
        <!-- Mobile Sidebar Backdrop -->
        <div class="sidebar-backdrop" id="sidebar-backdrop" onclick="toggleSidebar()"></div>

        <!-- Navy Role-Aware Sidebar -->
        <aside class="sidebar" id="app-sidebar" aria-label="Main Navigation">
            <div class="sidebar-brand">
                @if ($systemSettings?->logo_path)
                    <img class="thumb" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($systemSettings->logo_path) }}" alt="{{ $systemSettings->company_name ?? 'Logo' }}" style="width: 28px; height: 28px; object-fit: contain; border-radius: 6px; background: #ffffff; padding: 2px; flex-shrink: 0;">
                @else
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#38bdf8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                @endif
                <div style="display: flex; flex-direction: column; overflow: hidden; min-width: 0;">
                    <strong style="font-size: 13.5px; font-weight: 800; color: #ffffff; letter-spacing: 0.03em; line-height: 1.1; white-space: nowrap;">{{ $systemSettings?->company_name ?: 'MUKWANO WIMS' }}</strong>
                    <span style="font-size: 10px; color: #94a3b8; line-height: 1.2; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Warehouse Inventory System</span>
                </div>
            </div>

            <nav class="sidebar-nav">
                <!-- General -->
                <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                    Dashboard
                </a>

                @if(auth()->user()->hasRole(['administrator', 'warehouse_manager', 'storekeeper']))
                    <a href="{{ route('stock-keeper.index') }}" class="nav-item {{ request()->routeIs('stock-keeper.*') ? 'active' : '' }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7h18v13H3z"></path><path d="M7 7V4h10v3"></path><path d="M8 12h8M8 16h5"></path></svg>
                        Stock Keeper Workspace
                    </a>
                @endif

                @if(auth()->user()->hasRole(['administrator', 'warehouse_manager', 'storekeeper', 'dispatch_officer']))
                    <a href="{{ route('qr.scan') }}" class="nav-item {{ request()->routeIs('qr.scan') ? 'active' : '' }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7V5a2 2 0 0 1 2-2h2"></path><path d="M17 3h2a2 2 0 0 1 2 2v2"></path><path d="M21 17v2a2 2 0 0 1-2 2h-2"></path><path d="M7 21H5a2 2 0 0 1-2-2v-2"></path><rect x="7" y="7" width="10" height="10"></rect></svg>
                        Scan QR
                    </a>
                @endif

                <!-- Catalog Section -->
                <div class="nav-section-title">Catalog</div>
                <a href="{{ route('products.index') }}" class="nav-item {{ request()->routeIs('products.*') ? 'active' : '' }}">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    Products
                </a>
                @if(auth()->user()->hasRole(['administrator', 'warehouse_manager']))
                    <a href="{{ route('categories.index') }}" class="nav-item {{ request()->routeIs('categories.*') ? 'active' : '' }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
                        Categories
                    </a>
                @endif
                @if(auth()->user()->hasRole(['administrator', 'warehouse_manager', 'procurement_officer']))
                    <a href="{{ route('suppliers.index') }}" class="nav-item {{ request()->routeIs('suppliers.*') ? 'active' : '' }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                        Suppliers
                    </a>
                @endif

                <!-- Procurement & Receiving -->
                @if(auth()->user()->hasRole(['administrator', 'warehouse_manager', 'procurement_officer', 'storekeeper', 'auditor']))
                    <div class="nav-section-title">Inbound</div>
                    @if(auth()->user()->hasRole(['administrator', 'warehouse_manager', 'procurement_officer', 'auditor']))
                        <a href="{{ route('purchase-orders.index') }}" class="nav-item {{ request()->routeIs('purchase-orders.*') ? 'active' : '' }}">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                            Purchase Orders
                        </a>
                    @endif
                    @if(auth()->user()->hasRole(['administrator', 'warehouse_manager', 'procurement_officer']))
                        <a href="{{ route('procurement-communications.index') }}"
                           class="nav-item {{ request()->routeIs('procurement-communications.*') ? 'active' : '' }}">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 11.5a8.4 8.4 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.7a8.4 8.4 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.4 8.4 0 0 1 3.8-.9h.5a8.5 8.5 0 0 1 8 8v.5z"></path>
                            </svg>
                            Procurement Communication
                        </a>
                    @endif
                    <a href="{{ route('goods-receipts.index') }}" class="nav-item {{ request()->routeIs('goods-receipts.*') ? 'active' : '' }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        Goods Receiving
                    </a>
                    @if(auth()->user()->hasRole(['administrator', 'warehouse_manager', 'storekeeper']))
                        <a href="{{ route('put-away.index') }}" class="nav-item {{ request()->routeIs('put-away.*') ? 'active' : '' }}">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="17 11 12 6 7 11"></polyline><polyline points="17 18 12 13 7 18"></polyline></svg>
                            Put-away Tasks
                        </a>
                    @endif
                @endif

                <!-- Stock Control (Expandable Group) -->
                <div class="nav-section-title">Inventory</div>
                <details class="nav-group" {{ request()->routeIs('inventory.*', 'stock-transfers.*', 'locations.*', 'put-away.*', 'batches.*') ? 'open' : '' }}>
                    <summary>
                        <span style="display: flex; align-items: center; gap: 10px;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>
                            Stock Control
                        </span>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                    </summary>
                    <div class="nav-group-items">
                        <a href="{{ route('inventory.index') }}" class="nav-item {{ request()->routeIs('inventory.index') ? 'active' : '' }}">Stock Overview</a>
                        <a href="{{ route('inventory.enquiry') }}" class="nav-item {{ request()->routeIs('inventory.enquiry') ? 'active' : '' }}">Stock Enquiry</a>
                        <a href="{{ route('batches.index') }}" class="nav-item {{ request()->routeIs('batches.*') ? 'active' : '' }}">Batches</a>
                        @if(auth()->user()->hasRole(['administrator', 'warehouse_manager', 'storekeeper', 'auditor']))
                            <a href="{{ route('inventory.ledger') }}" class="nav-item {{ request()->routeIs('inventory.ledger') ? 'active' : '' }}">Movement Ledger</a>
                        @endif
                        @if(auth()->user()->hasRole(['administrator', 'warehouse_manager', 'storekeeper']))
                            <a href="{{ route('stock-transfers.create') }}" class="nav-item {{ request()->routeIs('stock-transfers.*') ? 'active' : '' }}">Stock Transfers</a>
                            <a href="{{ route('locations.index') }}" class="nav-item {{ request()->routeIs('locations.*') ? 'active' : '' }}">Warehouse Locations</a>
                        @endif
                    </div>
                </details>

                <!-- Outbound & Dispatch -->
                @if(auth()->user()->hasRole(['administrator', 'warehouse_manager', 'dispatch_officer', 'storekeeper', 'auditor']))
                    <div class="nav-section-title">Outbound</div>
                    <a href="{{ route('dispatch-requests.index') }}" class="nav-item {{ request()->routeIs('dispatch-requests.*') ? 'active' : '' }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
                        Dispatch
                    </a>
                @endif


                <!-- Audit & Verification -->
                @if(auth()->user()->hasRole(['administrator', 'warehouse_manager', 'storekeeper', 'auditor']))
                    <div class="nav-section-title">Verification</div>
                    <a href="{{ route('stock-counts.index') }}" class="nav-item {{ request()->routeIs('stock-counts.*') ? 'active' : '' }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
                        Cycle Counting
                    </a>
                @endif

                <!-- Intelligence & Admin -->
                <div class="nav-section-title">Analytics & Config</div>
                <a href="{{ route('reports.index') }}" class="nav-item {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                    Reports
                </a>
                @if(auth()->user()->hasRole(['administrator', 'warehouse_manager', 'procurement_officer', 'auditor']))
                    <a href="{{ route('erp-exchange.index') }}" class="nav-item {{ request()->routeIs('erp-exchange.*') ? 'active' : '' }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                        ERP Exchange
                    </a>
                @endif
                @php
                    $unresolvedAlertsCount = \Illuminate\Support\Facades\Schema::hasTable('alerts')
                        ? \App\Models\Alert::query()->where('condition_active', true)->whereNull('resolved_at')->count()
                        : 0;

                    $unreadNotificationsCount = auth()->user()->unreadNotifications()->count();
                @endphp
                <a href="{{ route('alerts.index') }}" class="nav-item {{ request()->routeIs('alerts.*') ? 'active' : '' }}">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                    <span style="flex: 1;">Alerts</span>
                    @if ($unresolvedAlertsCount > 0)
                        <span style="background: #ef4444; color: white; font-size: 10px; font-weight: 700; border-radius: 9999px; padding: 1px 7px; min-width: 18px; text-align: center;">{{ $unresolvedAlertsCount }}</span>
                    @endif
                </a>

                <a href="{{ route('notifications.index') }}"
                   class="nav-item {{ request()->routeIs('notifications.*') ? 'active' : '' }}">
                    <svg width="18" height="18" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                    <span style="flex:1;">Notifications</span>

                    @if ($unreadNotificationsCount > 0)
                        <span style="background:#2563eb;color:white;font-size:10px;font-weight:700;border-radius:9999px;padding:1px 7px;">
                            {{ $unreadNotificationsCount > 99 ? '99+' : $unreadNotificationsCount }}
                        </span>
                    @endif
                </a>

                @can('manage-administration')
                    <div class="nav-section-title">Administration</div>
                    <a href="{{ route('admin.users.index') }}" class="nav-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                        Users
                    </a>
                    <a href="{{ route('admin.roles.index') }}" class="nav-item {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                        Roles & Permissions
                    </a>
                    <a href="{{ route('admin.audit.index') }}" class="nav-item {{ request()->routeIs('admin.audit.*') ? 'active' : '' }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                        Audit Logs
                    </a>
                    <a href="{{ route('admin.settings.edit') }}" class="nav-item {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                        Settings
                    </a>
                @endcan

                <div class="nav-section-title">My Account</div>
                <a href="{{ route('profile.edit') }}" class="nav-item {{ request()->routeIs('profile.edit') ? 'active' : '' }}">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    Profile Settings
                </a>
            </nav>

            <div class="sidebar-footer">
                <div class="sidebar-footer-brand">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#38bdf8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                    <div>
                        <div style="font-size: 11px; font-weight: 700; color: #f8fafc; line-height: 1.2;">Smart Inventory</div>
                        <div style="font-size: 10px; color: #94a3b8; line-height: 1.2;">Better Business</div>
                    </div>
                </div>
                <span style="font-size: 11px; color: #64748b; font-weight: 600;">v1.0.0</span>
            </div>
        </aside>
    @endauth

    <div class="app-main-wrap" style="{{ auth()->guest() ? 'margin-left: 0;' : '' }}">
        @auth
            <!-- Top Bar Header -->
            <header class="topbar">
                <div class="topbar-left">
                    <button type="button" class="mobile-toggle" onclick="toggleSidebar()" aria-label="Toggle navigation">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                    </button>

                    <!-- Centred Global Search -->
                    <form class="global-search" action="{{ route('products.index') }}" method="GET">
                        <svg class="global-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        <input type="search" name="q" value="{{ request('q') }}" placeholder="Search products, batches, locations..." aria-label="Global search">
                    </form>
                </div>

                <div class="topbar-right">
                    <!-- Install App Header Pill Button -->
                    <button id="install-app" type="button" class="install-app-btn" hidden title="Install Mukwano WIMS App">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        <span>Install app</span>
                    </button>

                    <!-- Notification Bell -->
                    <a href="{{ route('notifications.index') }}"
                       class="notification-btn"
                       aria-label="Notifications"
                       title="Notifications">
                        <svg width="19" height="19" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                        </svg>

                        @if ($unreadNotificationsCount > 0)
                            <span class="notification-badge">
                                {{ $unreadNotificationsCount > 99 ? '99+' : $unreadNotificationsCount }}
                            </span>
                        @endif
                    </a>

                    <!-- Short System Online Status -->
                    <span id="network-status" class="status-pill" role="status" aria-live="polite">
                        <span class="status-dot"></span>
                        <span id="network-status-label">System Online</span>
                    </span>

                    <!-- User and Role Menu with Avatar -->
                    <div class="user-menu">
                        <div class="user-avatar" style="overflow: hidden;">
                            @if (auth()->user()->avatar_url)
                                <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                            @else
                                <span>{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                            @endif
                        </div>
                        <div class="user-info">
                            <span class="user-name">{{ auth()->user()->name }}</span>
                            <span class="role-badge">{{ auth()->user()->role_label }}</span>
                        </div>
                        <form method="POST" action="{{ route('logout') }}" class="signout-form">
                            @csrf
                            <button type="submit" class="signout-btn">Sign out</button>
                        </form>
                    </div>
                </div>
            </header>
        @endauth

        @guest
            <!-- Guest Floating Install Button -->
            <button id="guest-install-app" type="button" class="install-app-btn install-app-trigger" hidden style="position: fixed; bottom: 20px; right: 20px; z-index: 1000; box-shadow: 0 4px 14px rgba(0,0,0,0.25);">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                <span>Install app</span>
            </button>
        @endguest

        <main>
            <div class="pwa-tools" data-user-id="{{ auth()->id() ?? '' }}" style="display:none;"></div>

            @if (session('status'))
                <div class="notice" role="status">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="notice errors" role="alert">
                    <strong>Please correct the following:</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section id="offline-queue" class="card offline-queue" aria-live="polite" hidden>
                <h2>Offline submissions</h2>
                <p id="offline-queue-summary" class="muted"></p>
                <ul id="offline-queue-items"></ul>
            </section>

            @yield('content')
        </main>
    </div>

    @auth
        <!-- Mobile Bottom Action Bar -->
        <nav class="mobile-bottom-bar" aria-label="Mobile Navigation">
            <a href="{{ route('dashboard') }}" class="mobile-bar-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                <span>Dashboard</span>
            </a>
            <a href="{{ route('qr.scan') }}" class="mobile-bar-item {{ request()->routeIs('qr.scan') ? 'active' : '' }}">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7V5a2 2 0 0 1 2-2h2"></path><path d="M17 3h2a2 2 0 0 1 2 2v2"></path><path d="M21 17v2a2 2 0 0 1-2 2h-2"></path><path d="M7 21H5a2 2 0 0 1-2-2v-2"></path><rect x="7" y="7" width="10" height="10"></rect></svg>
                <span>Scan</span>
            </a>
            <a href="{{ route('alerts.index') }}" class="mobile-bar-item {{ request()->routeIs('alerts.*') ? 'active' : '' }}">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                <span>Alerts</span>
            </a>
            <button type="button" class="mobile-bar-item" onclick="toggleSidebar()" style="background: none; border: 0; cursor: pointer;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                <span>Menu</span>
            </button>
        </nav>
    @endauth
</div>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('app-sidebar');
        const backdrop = document.getElementById('sidebar-backdrop');
        if (sidebar && backdrop) {
            sidebar.classList.toggle('open');
            backdrop.classList.toggle('open');
        }
    }

    // Keep System Online status updated live
    window.addEventListener('online', updateNetworkStatus);
    window.addEventListener('offline', updateNetworkStatus);

    function updateNetworkStatus() {
        const statusPill = document.getElementById('network-status');
        const statusLabel = document.getElementById('network-status-label');
        if (!statusPill || !statusLabel) return;

        if (navigator.onLine) {
            statusPill.className = 'status-pill';
            statusLabel.textContent = 'System Online';
        } else {
            statusPill.className = 'status-pill offline';
            statusLabel.textContent = 'System Offline';
        }
    }

    function togglePasswordVisibility(inputId, btn) {
        const input = document.getElementById(inputId);
        if (!input) return;
        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';

        const eyeIcon = btn.querySelector('.eye-icon');
        const eyeOffIcon = btn.querySelector('.eye-off-icon');
        if (eyeIcon && eyeOffIcon) {
            eyeIcon.style.display = isPassword ? 'none' : 'block';
            eyeOffIcon.style.display = isPassword ? 'block' : 'none';
        }
        btn.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
        btn.setAttribute('title', isPassword ? 'Hide password' : 'Show password');
    }
</script>
@stack('scripts')
</body>
</html>
