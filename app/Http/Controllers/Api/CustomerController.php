<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Customer\CustomerServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class CustomerController extends Controller
{
    public function __construct(public CustomerServiceInterface $customerService) {}

    public function index(): JsonResponse
    {
        $customers = $this->customerService->paginate();

        return ApiResponse::success(
            CustomerResource::collection($customers)->response()->getData(true),
            'Customers retrieved successfully.'
        );
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $customer = $this->customerService->create($request->validated());

        return ApiResponse::success(
            new CustomerResource($customer),
            'Customer created successfully.',
            'success',
            201
        );
    }

    public function show(string $id): JsonResponse
    {
        $customer = $this->customerService->find($id);

        return ApiResponse::success(new CustomerResource($customer), 'Customer retrieved successfully.');
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): JsonResponse
    {
        $customer = $this->customerService->update($customer, $request->validated());

        return ApiResponse::success(new CustomerResource($customer), 'Customer updated successfully.');
    }

    public function destroy(Customer $customer): JsonResponse
    {
        $this->customerService->delete($customer);

        return ApiResponse::success(null, 'Customer deleted successfully.');
    }
}
