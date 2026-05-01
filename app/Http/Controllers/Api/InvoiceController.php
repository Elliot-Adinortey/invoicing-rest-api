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

    public function index(): JsonResponse
    {
        $invoices = $this->invoiceService->paginate();

        return ApiResponse::success(
            InvoiceResource::collection($invoices)->response()->getData(true),
            'Invoices retrieved successfully.'
        );
    }

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

    public function show(Invoice $invoice): JsonResponse
    {
        $invoice = $this->invoiceService->find($invoice->id);

        return ApiResponse::success(new InvoiceResource($invoice), 'Invoice retrieved successfully.');
    }

    public function update(Invoice $invoice): JsonResponse
    {
        return ApiResponse::error(
            message: 'Invoice update is not supported.',
            errors: [],
            status: 405,
            code: 'METHOD_NOT_ALLOWED'
        );
    }


    public function destroy(Invoice $invoice): JsonResponse
    {
        $this->invoiceService->destroy($invoice);

        return ApiResponse::success(null, 'Invoice deleted successfully.');
    }

    public function markAsPaid(Invoice $invoice): JsonResponse
    {
        $invoice = $this->invoiceService->markAsPaid($invoice);

        return ApiResponse::success(new InvoiceResource($invoice), 'Invoice marked as paid.');
    }
}
