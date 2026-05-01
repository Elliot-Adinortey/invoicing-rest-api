<?php

namespace App\Contracts\Invoice;

use App\Models\Invoice;
use Illuminate\Pagination\LengthAwarePaginator;

interface InvoiceServiceInterface
{
    /**
     * @param  array{status?: string, customer_id?: string, search?: string, issue_date_from?: string, issue_date_to?: string, due_date_from?: string, due_date_to?: string, per_page?: int}  $filters
     */
    public function paginate(array $filters = []): LengthAwarePaginator;

    public function find(string $id): Invoice;

    /**
     * @param  array{customer_id: string, user_id: string, issue_date: string, due_date: string, status?: string, items: array{product_id: string, description?: string, unit_price: string, quantity: string}[]}  $data
     */
    public function create(array $data): Invoice;

    /**
     * @param  array{customer_id?: string, issue_date?: string, due_date?: string, items?: array{product_id: string, description?: string, unit_price: string, quantity: string}[]}  $data
     */
    public function update(Invoice $invoice, array $data): Invoice;

    public function issue(Invoice $invoice): Invoice;

    public function cancel(Invoice $invoice): Invoice;

    public function destroy(Invoice $invoice): void;

    public function markAsPaid(Invoice $invoice): Invoice;
}
