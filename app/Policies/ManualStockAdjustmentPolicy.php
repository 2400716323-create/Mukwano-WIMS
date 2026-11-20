<?php

namespace App\Policies;

use App\Models\ManualStockAdjustment;
use App\Models\User;

class ManualStockAdjustmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('Request manual stock adjustments')
            || $user->hasPermission('Approve or reject manual stock adjustments');
    }

    public function view(User $user, ManualStockAdjustment $adjustment): bool
    {
        return $user->hasPermission('Approve or reject manual stock adjustments')
            || (
                $user->hasPermission('Request manual stock adjustments')
                && (int) $adjustment->requested_by === (int) $user->id
            );
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('Request manual stock adjustments');
    }

    public function review(User $user, ManualStockAdjustment $adjustment): bool
    {
        return $adjustment->status === 'pending'
            && $user->hasPermission('Approve or reject manual stock adjustments')
            && (int) $adjustment->requested_by !== (int) $user->id;
    }
}
