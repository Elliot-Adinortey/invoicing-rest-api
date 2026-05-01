<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:auth.register');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:auth.login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::apiResource('customers', CustomerController::class);
        Route::apiResource('products', ProductController::class);
        Route::post('/products/{product}/restock', [ProductController::class, 'restock']);
        Route::apiResource('invoices', InvoiceController::class);
        Route::post('/invoices/{invoice}/issue', [InvoiceController::class, 'issue']);
        Route::post('/invoices/{invoice}/cancel', [InvoiceController::class, 'cancel']);
        Route::post('/invoices/{invoice}/mark-paid', [InvoiceController::class, 'markAsPaid']);
    });
});
