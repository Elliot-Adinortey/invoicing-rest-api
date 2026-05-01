<?php

namespace App\Services;

use App\Contracts\Invoice\InvoiceServiceInterface;
use App\Models\Invoice;
use App\Models\Product;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class InvoiceService implements InvoiceServiceInterface
{
    /**
     * @param  array{status?: string, customer_id?: string, search?: string, issue_date_from?: string, issue_date_to?: string, due_date_from?: string, due_date_to?: string, per_page?: int}  $filters
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $perPage = isset($filters['per_page']) ? min((int) $filters['per_page'], 100) : 15;

        return Invoice::with(['customer', 'user', 'items.product'])
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['customer_id'] ?? null, fn ($q, $id) => $q->where('customer_id', $id))
            ->when($filters['search'] ?? null, fn ($q, $search) => $q->where('invoice_number', 'like', "%{$search}%"))
            ->when($filters['issue_date_from'] ?? null, fn ($q, $date) => $q->whereDate('issue_date', '>=', $date))
            ->when($filters['issue_date_to'] ?? null, fn ($q, $date) => $q->whereDate('issue_date', '<=', $date))
            ->when($filters['due_date_from'] ?? null, fn ($q, $date) => $q->whereDate('due_date', '>=', $date))
            ->when($filters['due_date_to'] ?? null, fn ($q, $date) => $q->whereDate('due_date', '<=', $date))
            ->latest()
            ->paginate($perPage);
    }

    /**
     * @throws ModelNotFoundException
     */
    public function find(string $id): Invoice
    {
        return Invoice::with(['customer', 'user', 'items.product'])
            ->findOrFail($id);
    }

    /**
     * @param  array{customer_id: string, user_id: string, issue_date: string, due_date: string, status?: string, items: array{product_id: string, description?: string, unit_price: string, quantity: string}[]}  $data
     */
    public function create(array $data): Invoice
    {
        return DB::transaction(function () use ($data) {
            $items = $data['items'];
            unset($data['items']);

            $status = $data['status'] ?? 'issued';
            unset($data['status']);

            $subtotal = collect($items)->sum(fn (array $item) => $item['unit_price'] * $item['quantity']);

            $invoice = Invoice::create(array_merge([
                'status' => $status,
                'invoice_number' => $this->generateInvoiceNumber(),
            ], $data, [
                'subtotal' => $subtotal,
                'total' => $subtotal,
            ]));

            foreach ($items as $item) {
                $invoice->items()->create([
                    'product_id' => $item['product_id'],
                    'description' => $item['description'] ?? null,
                    'unit_price' => $item['unit_price'],
                    'quantity' => (int) $item['quantity'],
                    'amount' => $item['unit_price'] * $item['quantity'],
                ]);
            }

            // Only deduct stock when the invoice is immediately issued
            if ($status === 'issued') {
                $this->deductStock($invoice);
            }

            return $invoice->load(['customer', 'user', 'items.product']);
        });
    }

    /**
     * Deduct stock for all items on an invoice and record stock movements.
     * Must be called inside a transaction.
     */
    private function deductStock(Invoice $invoice): void
    {
        foreach ($invoice->items as $item) {
            /** @var Product $product */
            $product = Product::lockForUpdate()->findOrFail($item->product_id);

            $quantity = $item->quantity;

            if ($product->stock_quantity < $quantity) {
                throw ValidationException::withMessages([
                    'items' => ["Insufficient stock for product '{$product->product_name}'. Available: {$product->stock_quantity}, requested: {$quantity}."],
                ]);
            }

            $stockBefore = $product->stock_quantity;
            $stockAfter = $stockBefore - $quantity;

            $product->decrement('stock_quantity', $quantity);

            $invoice->stockMovements()->create([
                'product_id' => $item->product_id,
                'type' => 'sale',
                'quantity' => $quantity,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'description' => "Sale via invoice {$invoice->invoice_number}",
            ]);
        }
    }

    private function generateInvoiceNumber(): string
    {
        if (DB::getDriverName() === 'pgsql') {
            $sequence = DB::selectOne("SELECT nextval('invoice_number_seq') AS value")->value;
        } else {
            // SQLite fallback for testing — not used in production
            $sequence = Invoice::withTrashed()->lockForUpdate()->count() + 1;
        }

        return 'INV-'.now()->format('Ymd').'-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Update a draft invoice's fields and/or replace its line items.
     *
     * @param  array{customer_id?: string, issue_date?: string, due_date?: string, items?: array{product_id: string, description?: string, unit_price: string, quantity: string}[]}  $data
     */
    public function update(Invoice $invoice, array $data): Invoice
    {
        if ($invoice->status !== 'draft') {
            throw new HttpException(422, 'Only draft invoices can be updated.');
        }

        return DB::transaction(function () use ($invoice, $data) {
            if (isset($data['items'])) {
                $items = $data['items'];
                unset($data['items']);

                $subtotal = collect($items)->sum(fn (array $item) => $item['unit_price'] * $item['quantity']);
                $data['subtotal'] = $subtotal;
                $data['total'] = $subtotal;

                // Replace all line items
                $invoice->items()->delete();

                foreach ($items as $item) {
                    $invoice->items()->create([
                        'product_id' => $item['product_id'],
                        'description' => $item['description'] ?? null,
                        'unit_price' => $item['unit_price'],
                        'quantity' => (int) $item['quantity'],
                        'amount' => $item['unit_price'] * $item['quantity'],
                    ]);
                }
            }

            $invoice->update($data);

            return $invoice->refresh()->load(['customer', 'user', 'items.product']);
        });
    }

    /**
     * Transition a draft invoice to issued, deducting stock.
     */
    public function issue(Invoice $invoice): Invoice
    {
        if ($invoice->status !== 'draft') {
            throw new HttpException(422, 'Only draft invoices can be issued.');
        }

        return DB::transaction(function () use ($invoice) {
            $invoice->load('items');
            $this->deductStock($invoice);
            $invoice->update(['status' => 'issued']);

            return $invoice->refresh()->load(['customer', 'user', 'items.product']);
        });
    }

    /**
     * Cancel an invoice. Only draft or issued invoices can be cancelled.
     * Stock is restored when cancelling an issued invoice.
     */
    public function cancel(Invoice $invoice): Invoice
    {
        if ($invoice->status === 'paid') {
            throw new HttpException(422, 'Paid invoices cannot be cancelled.');
        }

        if ($invoice->status === 'cancelled') {
            throw new HttpException(422, 'Invoice is already cancelled.');
        }

        return DB::transaction(function () use ($invoice) {
            if ($invoice->status === 'issued') {
                $this->restoreStock($invoice);
            }

            $invoice->update(['status' => 'cancelled']);

            return $invoice->refresh()->load(['customer', 'user', 'items.product']);
        });
    }

    /**
     * Restore stock for all items on an issued invoice and record stock movements.
     * Must be called inside a transaction.
     */
    private function restoreStock(Invoice $invoice): void
    {
        $invoice->loadMissing('items');

        foreach ($invoice->items as $item) {
            /** @var Product $product */
            $product = Product::lockForUpdate()->findOrFail($item->product_id);

            $quantity = $item->quantity;
            $stockBefore = $product->stock_quantity;
            $stockAfter = $stockBefore + $quantity;

            $product->increment('stock_quantity', $quantity);

            $invoice->stockMovements()->create([
                'product_id' => $item->product_id,
                'type' => 'cancellation',
                'quantity' => $quantity,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'description' => "Stock restored via cancellation of invoice {$invoice->invoice_number}",
            ]);
        }
    }

    public function destroy(Invoice $invoice): void
    {
        if ($invoice->status === 'paid') {
            throw new HttpException(422, 'Paid invoices cannot be deleted.');
        }

        $invoice->deleteOrFail();
    }

    public function markAsPaid(Invoice $invoice): Invoice
    {
        if ($invoice->status === 'draft') {
            throw new HttpException(422, 'Draft invoices cannot be marked as paid. Issue the invoice first.');
        }

        if ($invoice->status === 'paid') {
            throw new HttpException(422, 'Invoice is already paid.');
        }

        if ($invoice->status === 'cancelled') {
            throw new HttpException(422, 'Cancelled invoices cannot be marked as paid.');
        }

        $invoice->update(['status' => 'paid']);

        return $invoice->refresh()->load(['customer', 'user', 'items.product']);
    }
}
