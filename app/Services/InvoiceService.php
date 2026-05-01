<?php

namespace App\Services;

use App\Contracts\Invoice\InvoiceServiceInterface;
use App\Models\Invoice;
use App\Models\Product;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceService implements InvoiceServiceInterface
{
    public function paginate(?string $status = null): LengthAwarePaginator
    {
        return Invoice::with(['customer', 'user', 'items.product'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(15);
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
     * @param  array{customer_id: string, user_id: string, issue_date: string, due_date: string, items: array{product_id: string, description?: string, unit_price: string, quantity: string}[]}  $data
     */
    public function create(array $data): Invoice
    {
        return DB::transaction(function () use ($data) {
            $items = $data['items'];
            unset($data['items']);

            $subtotal = collect($items)->sum(fn (array $item) => $item['unit_price'] * $item['quantity']);

            $invoice = Invoice::create(array_merge([
                'status' => 'issued',
                'invoice_number' => $this->generateInvoiceNumber(),
            ], $data, [
                'subtotal' => $subtotal,
                'total' => $subtotal,
            ]));

            foreach ($items as $item) {
                /** @var Product $product */
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);

                $quantity = (int) $item['quantity'];

                if ($product->stock_quantity < $quantity) {
                    throw ValidationException::withMessages([
                        'items' => ["Insufficient stock for product '{$product->product_name}'. Available: {$product->stock_quantity}, requested: {$quantity}."],
                    ]);
                }

                $stockBefore = $product->stock_quantity;
                $stockAfter = $stockBefore - $quantity;

                $invoice->items()->create([
                    'product_id' => $item['product_id'],
                    'description' => $item['description'] ?? $product->description,
                    'unit_price' => $item['unit_price'],
                    'quantity' => $quantity,
                    'amount' => $item['unit_price'] * $quantity,
                ]);

                $product->decrement('stock_quantity', $quantity);

                $invoice->stockMovements()->create([
                    'product_id' => $item['product_id'],
                    'type' => 'sale',
                    'quantity' => $quantity,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'description' => "Sale via invoice {$invoice->invoice_number}",
                ]);
            }

            return $invoice->load(['customer', 'user', 'items.product']);
        });
    }

    private function generateInvoiceNumber(): string
    {
        $prefix = 'INV-'.now()->format('Ymd').'-';
        $latest = Invoice::withTrashed()
            ->where('invoice_number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->count();

        return $prefix.str_pad($latest + 1, 4, '0', STR_PAD_LEFT);
    }

    /**
     * @param  array{invoice_number?: string, customer_id?: string, user_id?: string, issue_date?: string, due_date?: string, subtotal?: string, total?: string, status?: string}  $data
     */
    public function update(Invoice $invoice, array $data): Invoice
    {
        $invoice->update($data);

        return $invoice->refresh()->load(['customer', 'user', 'items.product']);
    }

    public function destroy(Invoice $invoice): void
    {
        $invoice->deleteOrFail();
    }

    public function markAsPaid(Invoice $invoice): Invoice
    {
        $invoice->update(['status' => 'paid']);

        return $invoice->refresh()->load(['customer', 'user', 'items.product']);
    }
}
