<?php

namespace App\Policies;

use App\Models\StockCount;
use App\Models\User;

class StockCountPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [
            User::ROLE_ADMINISTRATOR,
            User::ROLE_WAREHOUSE_MANAGER,
            User::ROLE_STOREKEEPER,
            User::ROLE_AUDITOR,
        ], true);
    }

    public function view(User $user, StockCount $stockCount): bool
    {
        return in_array($user->role, [
            User::ROLE_ADMINISTRATOR,
            User::ROLE_WAREHOUSE_MANAGER,
            User::ROLE_STOREKEEPER,
            User::ROLE_AUDITOR,
        ], true);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [
            User::ROLE_ADMINISTRATOR,
            User::ROLE_WAREHOUSE_MANAGER,
            User::ROLE_STOREKEEPER,
        ], true);
    }

    public function submit(User $user, StockCount $stockCount): bool
    {
        return in_array($user->role, [
            User::ROLE_ADMINISTRATOR,
            User::ROLE_WAREHOUSE_MANAGER,
            User::ROLE_STOREKEEPER,
        ], true) && $stockCount->status === 'draft';
    }

    public function review(User $user, StockCount $stockCount): bool
    {
        return ($user->isAdministrator() || $user->isWarehouseManager()) && $stockCount->status === 'submitted';
    }
}
