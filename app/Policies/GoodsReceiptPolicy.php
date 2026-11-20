<?php

namespace App\Policies;

use App\Models\GoodsReceipt;
use App\Models\User;

class GoodsReceiptPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(
            'Receive, transfer, dispatch, and reconcile stock'
        ) || $user->hasPermission(
            'Perform goods receipt and staging put-away'
        ) || $user->hasPermission(
            'View goods receipts and receiving history'
        );
    }

    public function view(User $user, GoodsReceipt $goodsReceipt): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(
            'Receive, transfer, dispatch, and reconcile stock'
        ) || $user->hasPermission(
            'Perform goods receipt and staging put-away'
        );
    }

    public function createDirect(User $user): bool
    {
        return $this->create($user);
    }
}