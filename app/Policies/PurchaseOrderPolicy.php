<?php

namespace App\Policies;

use App\Models\PurchaseOrder;
use App\Models\User;

class PurchaseOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(
            'Create, approve, and cancel purchase orders'
        ) || $user->hasPermission(
            'Create and submit purchase orders'
        ) || $user->hasPermission(
            'View purchase orders and goods receipt status'
        );
    }

    public function view(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(
            'Create, approve, and cancel purchase orders'
        ) || $user->hasPermission(
            'Create and submit purchase orders'
        );
    }

    public function update(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->hasPermission(
            'Create, approve, and cancel purchase orders'
        ) || $user->hasPermission(
            'Create and submit purchase orders'
        );
    }

    public function approve(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->hasPermission(
            'Create, approve, and cancel purchase orders'
        );
    }

    public function cancel(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->hasPermission(
            'Create, approve, and cancel purchase orders'
        );
    }
}