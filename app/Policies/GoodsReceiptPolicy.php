<?php

namespace App\Policies;

use App\Models\GoodsReceipt;
use App\Models\User;

class GoodsReceiptPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, GoodsReceipt $goodsReceipt): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function createDirect(User $user): bool
    {
        return true;
    }
}
