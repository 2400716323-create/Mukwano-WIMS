<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

class AuthenticationAuditListener
{
    public function __construct(private AuditService $auditService) {}

    public function login(Login $event): void
    {
        $this->auditService->record($event->user, 'login', [], [
            'guard' => $event->guard,
            'ip' => request()->ip(),
        ]);
    }

    public function logout(Logout $event): void
    {
        if ($event->user instanceof User) {
            $this->auditService->record($event->user, 'logout', [], [
                'guard' => $event->guard,
                'ip' => request()->ip(),
            ]);
        }
    }

    public function failed(Failed $event): void
    {
        $email = $event->credentials['email'] ?? null;
        $this->auditService->recordActivity('login_failed', $event->user, [], [
            'email' => is_string($email) ? $email : null,
            'guard' => $event->guard,
            'ip' => request()->ip(),
        ]);
    }
}
