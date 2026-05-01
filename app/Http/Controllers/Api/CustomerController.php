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

    /**
     * Get all customers.
     *
     * @group Customers
     *
     * @authenticated
     *
     * @queryParam page integer Page number. Default: 1
     * @queryParam per_page integer Items per page. Default: 15
     *
     * @response 200 {
     *     "data": [
     *         {
     *             "id": "uuid",
     *             "customer_name": "Customer Name",
     *             "email": "[EMAIL_ADDRESS]",
     *             "phone": "+1234567890",
     *             "address": "123 Main St",
     *             "created_at": "datetime",
     *             "updated_at": "datetime"
     *         }
     *     ],
     *     "meta": {
     *         "current_page": 1,
     *         "from": 1,
     *         "last_page": 1,
     *         "per_page": 15,
     *         "to": 10,
     *         "total": 10
     *     },
     *     "message": "Customers retrieved successfully.",
     *     "status": "success",
     *     "status_code": 200
     * }
     */
    public function index(): JsonResponse
    {
        $customers = $this->customerService->paginate();

        return ApiResponse::success(
            CustomerResource::collection($customers)->response()->getData(true),
            'Customers retrieved successfully.'
        );
    }

    /**
     * Store a newly created customer in storage.
     *
     * @group Customers
     *
     * @authenticated
     *
     * @bodyParam customer_name string required Example: John Doe
     * @bodyParam email string optional Example: [EMAIL_ADDRESS]
     * @bodyParam phone string optional Example: +1234567890
     * @bodyParam address string optional Example: 123 Main St
     *
     * @response 201 {
     *     "data": {
     *         "id": "uuid",
     *         "customer_name": "John Doe",
     *         "email": "[EMAIL_ADDRESS]",
     *         "phone": "+1234567890",
     *         "address": "123 Main St",
     *         "created_at": "datetime",
     *         "updated_at": "datetime"
     *     },
     *     "message": "Customer created successfully.",
     *     "status": "success",
     *     "status_code": 201
     * }
     */
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

    /**
     * Get a specific customer.
     *
     * @group Customers
     *
     * @authenticated
     *
     * @urlParam id int required Example: 1
     *
     * @response 200 {
     *     "data": {
     *         "id": "uuid",
     *         "customer_name": "Customer Name",
     *         "email": "[EMAIL_ADDRESS]",
     *         "phone": "+1234567890",
     *         "address": "123 Main St",
     *         "created_at": "datetime",
     *         "updated_at": "datetime"
     *     },
     *     "message": "Customer retrieved successfully.",
     *     "status": "success",
     *     "status_code": 200
     * }
     * @response 404 {
     *     "message": "Customer not found.",
     *     "status": "error",
     *     "status_code": 404,
     *     "errors": [],
     *     "code": "NOT_FOUND"
     * }
     */
    public function show(Customer $customer): JsonResponse
    {
        return ApiResponse::success(new CustomerResource($customer), 'Customer retrieved successfully.');
    }

    /**
     * Update a specific customer.
     *
     * @group Customers
     *
     * @authenticated
     *
     * @urlParam id int required Example: 1
     *
     * @bodyParam customer_name string optional Example: John Doe
     * @bodyParam email string optional Example: [EMAIL_ADDRESS]
     * @bodyParam phone string optional Example: +1234567890
     * @bodyParam address string optional Example: 123 Main St
     *
     * @response 200 {
     *     "data": {
     *         "id": "uuid",
     *         "customer_name": "Updated Customer Name",
     *         "email": "[EMAIL_ADDRESS]",
     *         "phone": "+1234567890",
     *         "address": "123 Main St",
     *         "created_at": "datetime",
     *         "updated_at": "datetime"
     *     },
     *     "message": "Customer updated successfully.",
     *     "status": "success",
     *     "status_code": 200
     * }
     * @response 404 {
     *     "message": "Customer not found.",
     *     "status": "error",
     *     "status_code": 404,
     *     "errors": [],
     *     "code": "NOT_FOUND"
     * }
     */
    public function update(UpdateCustomerRequest $request, Customer $customer): JsonResponse
    {
        $customer = $this->customerService->update($customer, $request->validated());

        return ApiResponse::success(new CustomerResource($customer), 'Customer updated successfully.');
    }

    /**
     * Remove a specific customer.
     *
     * @group Customers
     *
     * @authenticated
     *
     * @urlParam id int required Example: 1
     *
     * @response 200 {
     *     "data": null,
     *     "message": "Customer deleted successfully.",
     *     "status": "success",
     *     "status_code": 200
     * }
     * @response 404 {
     *     "message": "Customer not found.",
     *     "status": "error",
     *     "status_code": 404,
     *     "errors": [],
     *     "code": "NOT_FOUND"
     * }
     */
    public function destroy(Customer $customer): JsonResponse
    {
        $this->customerService->delete($customer);

        return ApiResponse::success(null, 'Customer deleted successfully.');
    }
}
