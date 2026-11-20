<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;

class SupplierPolicy
{
    /**
     * Check whether a user can manage supplier records.
     */
    private function canManage(User $user): bool
    {
        return $user->hasPermission(
            'Manage product catalog, categories, and suppliers'
        ) || $user->hasPermission(
            'Manage supplier records and contacts'
        );
    }

    /**
     * Only administrators and warehouse managers can permanently delete.
     */
    private function canDelete(User $user): bool
    {
        return $user->isAdministrator()
            || $user->isWarehouseManager();
    }

    public function viewAny(User $user): bool
    {
        return $this->canManage($user);
    }

    public function view(User $user, Supplier $supplier): bool
    {
        return $this->canManage($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, Supplier $supplier): bool
    {
        return $this->canManage($user);
    }

    public function delete(User $user, Supplier $supplier): bool
    {
        return $this->canDelete($user);
    }

    public function restore(User $user, Supplier $supplier): bool
    {
        return $this->canDelete($user);
    }

    public function forceDelete(User $user, Supplier $supplier): bool
    {
        return $this->canDelete($user);
    }
}
