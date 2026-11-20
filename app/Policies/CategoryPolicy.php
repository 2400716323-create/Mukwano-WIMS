<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    private function canManage(User $user): bool
    {
        return $user->hasPermission('Manage product catalog, categories, and suppliers');
    }

    public function viewAny(User $user): bool
    {
        return $this->canManage($user)
            || $user->hasPermission('View product catalog and current stock levels')
            || $user->hasPermission('View product catalog, stock balances, and bin locations')
            || $user->hasPermission('View stock balances and product details');
    }

    public function view(User $user, Category $category): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, Category $category): bool
    {
        return $this->canManage($user);
    }

    public function delete(User $user, Category $category): bool
    {
        return $this->canManage($user);
    }

    public function restore(User $user, Category $category): bool
    {
        return $this->canManage($user);
    }

    public function forceDelete(User $user, Category $category): bool
    {
        return $this->canManage($user);
    }
}
