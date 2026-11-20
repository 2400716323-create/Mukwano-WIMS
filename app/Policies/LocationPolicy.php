<?php

namespace App\Policies;

use App\Models\Location;
use App\Models\User;

class LocationPolicy
{
    private function canManage(User $user): bool
    {
        return $user->hasPermission(
            'Manage warehouse locations and bin layouts'
        );
    }

    public function viewAny(User $user): bool
    {
        // P12: Only authorized location managers can open the hierarchy.
        return $this->canManage($user);
    }

    public function view(User $user, Location $location): bool
    {
        // P13: All six roles with viewing permission can view location details.
        return $this->canManage($user)
            || $user->hasPermission(
                'View warehouse locations and bin details'
            );
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, Location $location): bool
    {
        return $this->canManage($user);
    }

    public function delete(User $user, Location $location): bool
    {
        return $this->canManage($user);
    }

    public function printLabel(User $user, Location $location): bool
    {
        return $user->hasPermission(
            'Print location and product QR labels'
        );
    }

    public function scan(User $user): bool
    {
        return $user->hasPermission(
            'Scan location and product QR codes'
        );
    }

    public function restore(User $user, Location $location): bool
    {
        return $this->canManage($user);
    }

    public function forceDelete(User $user, Location $location): bool
    {
        return $this->canManage($user);
    }
}
