<?php

namespace App\Policies;

use App\Models\DispatchRequest;
use App\Models\User;

class DispatchRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, DispatchRequest $dispatchRequest): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function dispatch(User $user, DispatchRequest $dispatchRequest): bool
    {
        return ($user->isDispatchOfficer() || $user->isWarehouseManager() || $user->isAdministrator())
            && $dispatchRequest->status === 'requested';
    }
}
