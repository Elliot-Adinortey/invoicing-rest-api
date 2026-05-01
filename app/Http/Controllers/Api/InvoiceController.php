<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Invoice\InvoiceServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
     * @queryParam status string Filter by status (issued, paid, cancelled). Example: issued
     * @queryParam customer_id string Filter by customer UUID. Example: uuid
     * @queryParam search string Search by invoice number. Example: INV-20260501
     * @queryParam issue_date_from date Filter invoices issued on or after this date. Example: 2026-01-01
     * @queryParam issue_date_to date Filter invoices issued on or before this date. Example: 2026-12-31
     * @queryParam due_date_from date Filter invoices due on or after this date. Example: 2026-01-01
     * @queryParam due_date_to date Filter invoices due on or before this date. Example: 2026-12-31
     * @queryParam per_page integer Items per page (max 100). Default: 15
     * @queryParam page integer Page number. Default: 1
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'status',
            'customer_id',
            'search',
            'issue_date_from',
            'issue_date_to',
            'due_date_from',
            'due_date_to',
            'per_page',
        ]);

        $invoices = $this->invoiceService->paginate($filters);

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
