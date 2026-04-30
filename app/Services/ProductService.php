<?php

namespace App\Services;

use App\Contracts\Product\ProductServiceInterface;
use App\Models\Product;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductService implements ProductServiceInterface
{
    public function paginate(): LengthAwarePaginator
    {
        return Product::latest()->paginate(15);
    }

    /**
     * @throws ModelNotFoundException
     */
    public function find(string $id): Product
    {
        return Product::findOrFail($id);
    }

    /**
     * @param  array{product_name: string, description?: string|null, unit_price: string, stock_quantity?: int}  $data
     */
    public function create(array $data): Product
    {
        return Product::create($data);
    }

    /**
     * @param  array{product_name?: string, description?: string|null, unit_price?: string, stock_quantity?: int}  $data
     */
    public function update(Product $product, array $data): Product
    {
        $product->update($data);

        return $product->refresh();
    }

    public function delete(Product $product): void
    {
        $product->deleteOrFail();
    }

    /**
     * @param  array{quantity: int, description?: string|null}  $data
     */
    public function restock(Product $product, array $data): Product
    {
        $quantity = $data['quantity'];
        $stockBefore = $product->stock_quantity;
        $stockAfter = $stockBefore + $quantity;

        $product->increment('stock_quantity', $quantity);

        $product->stockMovements()->create([
            'type' => 'restock',
            'quantity' => $quantity,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'description' => $data['description'] ?? "Restock of {$quantity} units",
        ]);

        return $product->refresh();
    }
}
