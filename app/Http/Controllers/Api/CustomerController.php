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
use Illuminate\Http\Request;

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
     * @queryParam search string Search by name or email. Example: acme
     * @queryParam per_page integer Items per page (max 100). Default: 15
     * @queryParam page integer Page number. Default: 1
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'per_page']);

        $customers = $this->customerService->paginate($filters);

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
