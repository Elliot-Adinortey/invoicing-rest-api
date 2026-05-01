<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    /**
     * Any authenticated user can list products.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Any authenticated user can view a specific product.
     */
    public function view(User $user, Product $product): bool
    {
        return true;
    }

    /**
     * Any authenticated user can create a product.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Any authenticated user can update a product.
     */
    public function update(User $user, Product $product): bool
    {
        return true;
    }

    /**
     * Any authenticated user can delete a product.
     */
    public function delete(User $user, Product $product): bool
    {
        return true;
    }

    /**
     * Any authenticated user can restock a product.
     */
    public function restock(User $user, Product $product): bool
    {
        return true;
    }
}
