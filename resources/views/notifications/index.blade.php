
@extends('catalog.layout')

@section('content')

<div class="page-header-card">
    <div class="page-title-wrap">
        <h1>Notifications</h1>
        <p>View inventory alerts and important system updates.</p>
    </div>

    @if ($unreadCount > 0)
        <form method="POST" action="{{ route('notifications.read-all') }}">
            @csrf
            <button type="submit" class="button">
                Mark all as read
            </button>
        </form>
    @endif
</div>

<div class="modern-table-card">

    @forelse ($notifications as $notification)

        @php
            $data = $notification->data;
            $isUnread = is_null($notification->read_at);

            $actionUrl = $data['action_url'] ?? null;

            $isLocalUrl = is_string($actionUrl)
                && str_starts_with($actionUrl, '/')
                && ! str_starts_with($actionUrl, '//');
        @endphp

        <div style="padding:16px 20px; border-bottom:1px solid #e2e8f0; background:{{ $isUnread ? '#eff6ff' : '#ffffff' }};">

            <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px; flex-wrap:wrap;">

                <div style="flex:1; min-width:220px;">

                    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">

                        <strong style="color:#0f172a;">
                            {{ $data['title'] ?? 'System notification' }}
                        </strong>

                        @if ($isUnread)
                            <span class="badge badge-info">Unread</span>
                        @else
                            <span class="badge badge-success">Read</span>
                        @endif

                        @if (!empty($data['severity']))
                            <span class="badge {{ ($data['severity'] ?? '') === 'critical' ? 'badge-danger' : 'badge-warning' }}">
                                {{ ucfirst($data['severity']) }}
                            </span>
                        @endif

                    </div>

                    <p style="margin:8px 0; color:#475569; font-size:13px;">
                        {{ $data['description'] ?? 'You have a new system notification.' }}
                    </p>

                    <small>
                        {{ $notification->created_at->format('d M Y, h:i A') }}
                    </small>

                </div>

                @if ($isUnread)

                    <form method="POST"
                          action="{{ route('notifications.read', $notification->id) }}">

                        @csrf

                        <button type="submit" class="button secondary">
                            View / Mark as read
                        </button>

                    </form>

                @elseif ($isLocalUrl)

                    <a href="{{ $actionUrl }}" class="button secondary">
                        Open
                    </a>

                @endif

            </div>

        </div>

    @empty

        <div style="padding:35px 20px; text-align:center;">
            <h2>No notifications yet</h2>
            <p class="muted">
                New inventory notifications will appear here.
            </p>
        </div>

    @endforelse

</div>

<div class="pagination">
    {{ $notifications->links() }}
</div>

@endsection
