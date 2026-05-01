<?php

namespace App\Services;

use App\Contracts\Customer\CustomerServiceInterface;
use App\Models\Customer;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;

class CustomerService implements CustomerServiceInterface
{
    /**
     * @param  array{search?: string, per_page?: int}  $filters
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $perPage = isset($filters['per_page']) ? min((int) $filters['per_page'], 100) : 15;

        return Customer::when(
            $filters['search'] ?? null,
            fn ($q, $search) => $q->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
        )
            ->latest()
            ->paginate($perPage);
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
        $customer->deleteOrFail();
    }
}
