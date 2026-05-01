<?php

namespace App\Contracts\Product;

use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;

interface ProductServiceInterface
{
    /**
     * @param  array{search?: string, per_page?: int}  $filters
     */
    public function paginate(array $filters = []): LengthAwarePaginator;

    public function find(string $id): Product;

    /**
     * @param  array{product_name: string, description?: string|null, unit_price: string, stock_quantity?: int}  $data
     */
    public function create(array $data): Product;

    /**
     * @param  array{product_name?: string, description?: string|null, unit_price?: string, stock_quantity?: int}  $data
     */
    public function update(Product $product, array $data): Product;

    public function delete(Product $product): void;

    /**
     * @param  array{quantity: int, description?: string|null}  $data
     */
    public function restock(Product $product, array $data): Product;
}
