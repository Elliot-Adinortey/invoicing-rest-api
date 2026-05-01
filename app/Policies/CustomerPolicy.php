<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    /**
     * Any authenticated user can list customers.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Any authenticated user can view a specific customer.
     */
    public function view(User $user, Customer $customer): bool
    {
        return true;
    }

    /**
     * Any authenticated user can create a customer.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Any authenticated user can update a customer.
     */
    public function update(User $user, Customer $customer): bool
    {
        return true;
    }

    /**
     * Any authenticated user can delete a customer.
     */
    public function delete(User $user, Customer $customer): bool
    {
        return true;
    }
}
