<?php

namespace App\Policies;

use App\Models\DispatchRequest;
use App\Models\User;

class DispatchRequestPolicy
{
    private function canViewDispatch(User $user): bool
    {
        return in_array($user->role, [
            'administrator',
            'warehouse_manager',
            'dispatch_officer',
        ], true) || $user->hasPermission(
            'View dispatch requests and dispatch history'
        ) || $user->hasPermission(
            'Create and manage dispatch requests'
        );
    }

    private function canManageDispatch(User $user): bool
    {
        return in_array($user->role, [
            'administrator',
            'warehouse_manager',
            'dispatch_officer',
        ], true);
    }

    public function viewAny(User $user): bool
    {
        return $this->canViewDispatch($user);
    }

    public function view(User $user, DispatchRequest $dispatchRequest): bool
    {
        return $this->canViewDispatch($user);
    }

    public function create(User $user): bool
    {
        // Storekeepers may create/manage requests, but cannot perform dispatch operations.
        return $this->canManageDispatch($user)
            || $user->hasPermission('Create and manage dispatch requests');
    }

    // FEFO picking is restricted to operational dispatch roles.
    public function pickList(User $user, DispatchRequest $dispatchRequest): bool
    {
        return $this->canManageDispatch($user);
    }

    // GDN printing is restricted to operational dispatch roles.
    public function print(User $user, DispatchRequest $dispatchRequest): bool
    {
        return $this->canManageDispatch($user);
    }

    public function dispatch(User $user, DispatchRequest $dispatchRequest): bool
    {
        return $this->canManageDispatch($user)
            && $dispatchRequest->status === 'requested';
    }
}
