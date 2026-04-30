<?php

namespace App\Contracts\Invoice;

use App\Models\Invoice;
use Illuminate\Pagination\LengthAwarePaginator;

interface InvoiceServiceInterface
{
    public function paginate(): LengthAwarePaginator;

    public function find(string $id): Invoice;

    /**
     * @param  array{invoice_number?: string, customer_id?: string, user_id?: string, issue_date?: string, due_date?: string, subtotal?: string, total?: string, status?: string}  $data
     */
    public function update(Invoice $invoice, array $data): Invoice;

    /**
     * @param  array{invoice_number: string, customer_id: string, user_id: string, issue_date: string, due_date: string, subtotal: string, total: string, status: string, items: array{product_id: string, description: string, unit_price: string, quantity: string, amount: string}[]}  $data
     */
    public function create(array $data): Invoice;

    public function destroy(Invoice $invoice): void;
}
