<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    /**
     * Any authenticated user can list invoices.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Any authenticated user can view a specific invoice.
     */
    public function view(User $user, Invoice $invoice): bool
    {
        return true;
    }

    /**
     * Any authenticated user can create an invoice.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Only the invoice owner can update it.
     */
    public function update(User $user, Invoice $invoice): bool
    {
        return $user->id === $invoice->user_id;
    }

    /**
     * Only the invoice owner can delete it.
     */
    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->id === $invoice->user_id;
    }

    /**
     * Only the invoice owner can issue it.
     */
    public function issue(User $user, Invoice $invoice): bool
    {
        return $user->id === $invoice->user_id;
    }

    /**
     * Only the invoice owner can cancel it.
     */
    public function cancel(User $user, Invoice $invoice): bool
    {
        return $user->id === $invoice->user_id;
    }

    /**
     * Only the invoice owner can mark it as paid.
     */
    public function markAsPaid(User $user, Invoice $invoice): bool
    {
        return $user->id === $invoice->user_id;
    }
}
