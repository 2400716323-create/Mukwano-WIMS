<?php

namespace App\Policies;

use App\Models\StockCount;
use App\Models\User;

class StockCountPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('Create and approve cycle counts and adjust variances')
            || $user->hasPermission('Execute cycle count recording and blind counts')
            || $user->hasPermission('View cycle count reports, variances, and review history');
    }

    public function view(User $user, StockCount $stockCount): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('Create and approve cycle counts and adjust variances')
            || $user->hasPermission('Execute cycle count recording and blind counts');
    }

    public function submit(User $user, StockCount $stockCount): bool
    {
        return $stockCount->status === 'draft'
            && $this->create($user);
    }

    public function update(User $user, StockCount $stockCount): bool
    {
        return $stockCount->status === 'draft'
            && $this->create($user);
    }

    public function review(User $user, StockCount $stockCount): bool
    {
        return $stockCount->status === 'submitted'
            && $user->isSupervisor();
    }

    public function manageSettings(User $user): bool
    {
        return $user->hasPermission('Manage cycle-count variance and accuracy thresholds');
    }
}
