<?php

namespace App\Services;

use App\Contracts\Customer\CustomerServiceInterface;
use App\Models\Customer;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;

class CustomerService implements CustomerServiceInterface
{
    public function paginate(): LengthAwarePaginator
    {
        return Customer::latest()->paginate(15);
    }

    /**
     * @param  array{customer_name: string, email?: string|null, phone?: string|null, address?: string|null}  $data
     */
    public function create(array $data): Customer
    {
        return Customer::create($data);
    }

    /**
     * @throws ModelNotFoundException
     */
    public function find(string $id): Customer
    {
        return Customer::findOrFail($id);
    }

    /**
     * @param  array{customer_name?: string, email?: string|null, phone?: string|null, address?: string|null}  $data
     */
    public function update(Customer $customer, array $data): Customer
    {
        $customer->update($data);

        return $customer->refresh();
    }

    public function delete(Customer $customer): void
    {
        $customer->delete();
    }
}
