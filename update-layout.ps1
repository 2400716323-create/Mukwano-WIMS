
$ErrorActionPreference = "Stop"

$file = "resources/views/catalog/layout.blade.php"

if (!(Test-Path $file)) {
    throw "Layout file not found."
}

Copy-Item $file "$file.before-header-update" -Force
$content = Get-Content $file -Raw

$style = @'

        .header-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .notification-btn {
            position: relative;
            display: grid;
            place-items: center;
            width: 38px;
            height: 38px;
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--muted);
            text-decoration: none;
            background: #fff;
        }

        .notification-btn:hover {
            background: var(--blue-light);
            color: var(--blue);
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            min-width: 17px;
            padding: 2px 5px;
            border-radius: 999px;
            background: var(--danger);
            color: #fff;
            font-size: 10px;
            font-weight: 800;
            text-align: center;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 7px 10px;
            border-radius: 999px;
            background: #f0fdf4;
            color: #15803d;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
        }

        .status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #16a34a;
        }

        .status-pill.offline {
            background: #fef2f2;
            color: #dc2626;
        }

        .status-pill.offline .status-dot {
            background: #dc2626;
        }

        .signout-btn {
            padding: 8px 12px;
            border: 1px solid #fee2e2;
            border-radius: 8px;
            background: #fff;
            color: #dc2626;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
        }

        .signout-btn:hover {
            background: #fef2f2;
        }

        @media (max-width: 760px) {
            .header-actions {
                gap: 8px;
            }

            .header-actions .user-name,
            .header-actions .user-role {
                display: none;
            }

            .status-pill {
                padding: 7px;
                font-size: 0;
            }

            .signout-btn {
                padding: 7px 8px;
            }
        }
'@

$anchor = @'
        .user-area {
            display: flex;
            align-items: center;
            gap: 11px;
        }
'@

if (!$content.Contains($anchor)) {
    throw "Could not find the user-area CSS. Original layout remains unchanged."
}

$content = $content.Replace($anchor, $anchor + $style)

$topbar = @'
<header class="topbar">

    <div style="display:flex;align-items:center;gap:14px;">
        <button type="button"
                class="menu-toggle"
                onclick="document.getElementById('appSidebar').classList.toggle('open')"
                aria-label="Toggle navigation">
            ☰
        </button>

        <div>
            <div class="topbar-title">@yield('title', 'Dashboard')</div>
            <div class="topbar-subtitle">Warehouse Inventory System</div>
        </div>
    </div>

    @auth
        @php
            $unreadNotificationsCount = 0;
            try {
                if (method_exists(auth()->user(), 'unreadNotifications')) {
                    $unreadNotificationsCount = auth()->user()->unreadNotifications()->count();
                }
            } catch (\Throwable $exception) {
                $unreadNotificationsCount = 0;
            }
        @endphp

        <div class="header-actions">

            @if(Route::has('notifications.index'))
                <a href="{{ route('notifications.index') }}"
                   class="notification-btn"
                   aria-label="Notifications"
                   title="Notifications">
                    <span style="font-size:19px;">♧</span>

                    @if($unreadNotificationsCount > 0)
                        <span class="notification-badge">
                            {{ $unreadNotificationsCount > 99 ? '99+' : $unreadNotificationsCount }}
                        </span>
                    @endif
                </a>
            @endif

            <span id="network-status" class="status-pill" role="status">
                <span class="status-dot"></span>
                <span id="network-status-label">Checking connection...</span>
            </span>

            <div class="user-area">
                <div class="user-avatar">
                    {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                </div>
                <div>
                    <div class="user-name">{{ auth()->user()->name ?? 'User' }}</div>
                    <div class="user-role">System User</div>
                </div>
            </div>

            @if(Route::has('logout'))
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="signout-btn">Sign out</button>
                </form>
            @endif

        </div>
    @endauth

</header>
'@

$pattern = '(?s)<header class="topbar">.*?</header>'

if ($content -notmatch $pattern) {
    throw "Could not find the existing topbar. Original layout remains unchanged."
}

$content = [regex]::Replace(
    $content,
    $pattern,
    [System.Text.RegularExpressions.MatchEvaluator]{
        param($match)
        $topbar
    },
    1
)

$script = @'
<script>
    function updateNetworkStatus() {
        const status = document.getElementById('network-status');
        const label = document.getElementById('network-status-label');

        if (!status || !label) return;

        if (navigator.onLine) {
            status.classList.remove('offline');
            label.textContent = 'System Online';
        } else {
            status.classList.add('offline');
            label.textContent = 'System Offline';
        }
    }

    window.addEventListener('online', updateNetworkStatus);
    window.addEventListener('offline', updateNetworkStatus);
    updateNetworkStatus();
</script>

'@

$stack = "@stack('scripts')"

if (!$content.Contains($stack)) {
    throw "Could not find the scripts stack. Original layout remains unchanged."
}

$content = $content.Replace($stack, $script + $stack)

Set-Content -Path $file -Value $content -Encoding UTF8

Write-Host "Layout updated successfully!" -ForegroundColor Green
Write-Host "Backup: $file.before-header-update" -ForegroundColor Cyan
