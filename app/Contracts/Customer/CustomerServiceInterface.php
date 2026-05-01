<?php

namespace App\Contracts\Customer;

use App\Models\Customer;
use Illuminate\Pagination\LengthAwarePaginator;

interface CustomerServiceInterface
{
    /**
     * @param  array{search?: string, per_page?: int}  $filters
     */
    public function paginate(array $filters = []): LengthAwarePaginator;

    /**
     * @param  array{customer_name: string, email?: string|null, phone?: string|null, address?: string|null}  $data
     */
    public function create(array $data): Customer;

    public function find(string $id): Customer;

    /**
     * @param  array{customer_name?: string, email?: string|null, phone?: string|null, address?: string|null}  $data
     */
    public function update(Customer $customer, array $data): Customer;

    public function delete(Customer $customer): void;
}
