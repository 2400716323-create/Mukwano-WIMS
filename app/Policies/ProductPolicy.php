<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    /**
     * Determine whether the user can view the product catalogue.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(
            'Manage product catalog, categories, and suppliers'
        ) || $user->hasPermission(
            'View product catalog, stock balances, and bin locations'
        ) || $user->hasPermission(
            'View product catalog and current stock levels'
        ) || $user->hasPermission(
            'View stock balances and product details'
        );
    }

    /**
     * Determine whether the user can view a product.
     */
    public function view(User $user, Product $product): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Authorise ERP product imports separately.
     */
    public function import(User $user, ?Product $product = null): bool
    {
        return $user->hasPermission(
            'Perform ERP purchase and item catalog imports'
        );
    }

    /**
     * Determine whether the user can create products.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission(
            'Create products in product catalog'
        ) || $user->hasPermission(
            'Manage product catalog, categories, and suppliers'
        );
    }

    /**
     * Determine whether the user can update products.
     */
    public function update(User $user, Product $product): bool
    {
        return $user->hasPermission(
            'Manage product catalog, categories, and suppliers'
        );
    }

    /**
     * Determine whether the user can delete products.
     */
    public function delete(User $user, Product $product): bool
    {
        return $user->hasPermission(
            'Manage product catalog, categories, and suppliers'
        );
    }

    /**
     * Determine whether the user can restore products.
     */
    public function restore(User $user, Product $product): bool
    {
        return $user->hasPermission(
            'Manage product catalog, categories, and suppliers'
        );
    }

    /**
     * Determine whether the user can permanently delete products.
     */
    public function forceDelete(User $user, Product $product): bool
    {
        return $user->hasPermission(
            'Manage product catalog, categories, and suppliers'
        );
    }

    /**
     * Determine whether the user can print product QR labels.
     */
    public function printLabel(User $user, Product $product): bool
    {
        return $user->hasPermission(
            'Print location and product QR labels'
        );
    }

    /**
     * Determine whether the user can scan product QR codes.
     */
    public function scan(User $user): bool
    {
        return $user->hasPermission(
            'Scan location and product QR codes'
        );
    }
}
