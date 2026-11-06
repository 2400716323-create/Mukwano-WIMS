<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Console\Command;

class SetUserRole extends Command
{
    protected $signature = 'users:set-role {email} {role : admin, user, dispatch_officer, or supervisor}';

    protected $description = 'Assign or remove dispatch-officer access for a user.';

    public function handle(AuditService $auditService): int
    {
        $role = $this->argument('role');
        $validRoles = array_merge(array_keys(config('roles.roles')), ['admin', 'supervisor', 'user']);
        if (! in_array($role, $validRoles, true)) {
            $this->error('Choose one of the configured roles: '.implode(', ', array_keys(config('roles.roles'))).'.');

            return self::INVALID;
        }

        $user = User::query()->where('email', $this->argument('email'))->first();
        if ($user === null) {
            $this->error('No user exists with that email address.');

            return self::FAILURE;
        }

        $oldRole = $user->role;
        $user->forceFill(['role' => $role])->save();
        if ($oldRole !== $role) {
            $auditService->record($user, 'role_changed', ['role' => $oldRole], ['role' => $role]);
        }
        $this->info("Updated {$user->email} to role {$role}.");

        return self::SUCCESS;
    }
}
