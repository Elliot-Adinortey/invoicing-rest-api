<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Invoice\InvoiceServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class InvoiceController extends Controller
{
    public function __construct(public InvoiceServiceInterface $invoiceService) {}

    /**
     * Get all invoices.
     *
     * @group Invoices
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
     *             "invoice_number": "INV-001",
     *             "customer_id": "uuid",
     *             "issue_date": "2023-10-26",
     *             "due_date": "2023-11-26",
     *             "subtotal": "100.00",
     *             "tax": "10.00",
     *             "total": "110.00",
     *             "status": "pending",
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
     *     "message": "Invoices retrieved successfully.",
     *     "status": "success",
     *     "status_code": 200
     * }
     */
    public function index(): JsonResponse
    {
        $invoices = $this->invoiceService->paginate();

        return ApiResponse::success(
            InvoiceResource::collection($invoices)->response()->getData(true),
            'Invoices retrieved successfully.'
        );
    }

    /**
     * Store a newly created invoice in storage.
     *
     * @group Invoices
     *
     * @authenticated
     *
     * @bodyParam customer_id string required Example: uuid
     * @bodyParam issue_date date required Example: 2023-10-26
     * @bodyParam due_date date required Example: 2023-11-26
     * @bodyParam status string required Example: pending
     * @bodyParam items array required Example: [
     *     {
     *         "product_id": "uuid",
     *         "description": "Product Description",
     *         "unit_price": "100.00",
     *         "quantity": 1
     *     }
     * ]
     *
     * @response 201 {
     *     "data": {
     *         "id": "uuid",
     *         "invoice_number": "INV-001",
     *         "customer_id": "uuid",
     *         "issue_date": "2023-10-26",
     *         "due_date": "2023-11-26",
     *         "subtotal": "100.00",
     *         "tax": "10.00",
     *         "total": "110.00",
     *         "status": "pending",
     *         "created_at": "datetime",
     *         "updated_at": "datetime"
     *     },
     *     "message": "Invoice created successfully.",
     *     "status": "success",
     *     "status_code": 201
     * }
     */
    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        $data = array_merge($request->validated(), [
            'user_id' => $request->user()->id,
        ]);

        $invoice = $this->invoiceService->create($data);

        return ApiResponse::success(
            new InvoiceResource($invoice),
            'Invoice created successfully.',
            'success',
            201
        );
    }

    /**
     * Get a specific invoice.
     *
     * @group Invoices
     *
     * @authenticated
     *
     * @urlParam id int required Example: 1
     *
     * @response 200 {
     *     "data": {
     *         "id": "uuid",
     *         "invoice_number": "INV-001",
     *         "customer_id": "uuid",
     *         "issue_date": "2023-10-26",
     *         "due_date": "2023-11-26",
     *         "subtotal": "100.00",
     *         "tax": "10.00",
     *         "total": "110.00",
     *         "status": "pending",
     *         "created_at": "datetime",
     *         "updated_at": "datetime"
     *     },
     *     "message": "Invoice retrieved successfully.",
     *     "status": "success",
     *     "status_code": 200
     * }
     *
     * @response 404 {
     *     "message": "Invoice not found.",
     *     "status": "error",
     *     "status_code": 404,
     *     "errors": [],
     *     "code": "NOT_FOUND"
     * }
     */
    public function show(Invoice $invoice): JsonResponse
    {
        $invoice = $this->invoiceService->find($invoice->id);

        return ApiResponse::success(new InvoiceResource($invoice), 'Invoice retrieved successfully.');
    }

    /**
     * Update a specific invoice.
     *
     * @group Invoices
     *
     * @authenticated
     *
     * @urlParam id int required Example: 1
     *
     * @bodyParam customer_id string optional Example: uuid
     * @bodyParam issue_date date optional Example: 2023-10-26
     * @bodyParam due_date date optional Example: 2023-11-26
     * @bodyParam status string optional Example: pending
     * @bodyParam items array optional Example: [
     *     {
     *         "product_id": "uuid",
     *         "description": "Product Description",
     *         "unit_price": "100.00",
     *         "quantity": 1
     *     }
     * ]
     *
     * @response 200 {
     *     "data": {
     *         "id": "uuid",
     *         "invoice_number": "INV-001",
     *         "customer_id": "uuid",
     *         "issue_date": "2023-10-26",
     *         "due_date": "2023-11-26",
     *         "subtotal": "100.00",
     *         "tax": "10.00",
     *         "total": "110.00",
     *         "status": "pending",
     *         "created_at": "datetime",
     *         "updated_at": "datetime"
     *     },
     *     "message": "Invoice updated successfully.",
     *     "status": "success",
     *     "status_code": 200
     * }
     *
     * @response 404 {
     *     "message": "Invoice not found.",
     *     "status": "error",
     *     "status_code": 404,
     *     "errors": [],
     *     "code": "NOT_FOUND"
     * }
     */
    public function update(Invoice $invoice): JsonResponse
    {
        return ApiResponse::error(
            message: 'Invoice update is not supported.',
            errors: [],
            status: 405,
            code: 'METHOD_NOT_ALLOWED'
        );
    }
    
    /**
     * Remove a specific invoice.
     *
     * @group Invoices
     *
     * @authenticated
     *
     * @urlParam id int required Example: 1
     *
     * @response 200 {
     *     "data": null,
     *     "message": "Invoice deleted successfully.",
     *     "status": "success",
     *     "status_code": 200
     * }
     *
     * @response 404 {
     *     "message": "Invoice not found.",
     *     "status": "error",
     *     "status_code": 404,
     *     "errors": [],
     *     "code": "NOT_FOUND"
     * }
     */
    public function destroy(Invoice $invoice): JsonResponse
    {
        $this->invoiceService->destroy($invoice);

        return ApiResponse::success(null, 'Invoice deleted successfully.');
    }
    
    /**
     * Mark an invoice as paid.
     *
     * @group Invoices
     *
     * @authenticated
     *
     * @urlParam id int required Example: 1
     *
     * @response 200 {
     *     "data": {
     *         "id": "uuid",
     *         "invoice_number": "INV-001",
     *         "customer_id": "uuid",
     *         "issue_date": "2023-10-26",
     *         "due_date": "2023-11-26",
     *         "subtotal": "100.00",
     *         "tax": "10.00",
     *         "total": "110.00",
     *         "status": "paid",
     *         "created_at": "datetime",
     *         "updated_at": "datetime"
     *     },
     *     "message": "Invoice marked as paid.",
     *     "status": "success",
     *     "status_code": 200
     * }
     *
     * @response 404 {
     *     "message": "Invoice not found.",
     *     "status": "error",
     *     "status_code": 404,
     *     "errors": [],
     *     "code": "NOT_FOUND"
     * }
     */
    public function markAsPaid(Invoice $invoice): JsonResponse
    {
        $invoice = $this->invoiceService->markAsPaid($invoice);

        return ApiResponse::success(new InvoiceResource($invoice), 'Invoice marked as paid.');
    }
}
