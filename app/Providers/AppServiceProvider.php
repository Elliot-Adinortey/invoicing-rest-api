<?php

namespace App\Providers;

use App\Contracts\Auth\AuthServiceInterface;
use App\Contracts\Customer\CustomerServiceInterface;
use App\Contracts\Invoice\InvoiceServiceInterface;
use App\Contracts\Product\ProductServiceInterface;
use App\Services\AuthService;
use App\Services\CustomerService;
use App\Services\InvoiceService;
use App\Services\ProductService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AuthServiceInterface::class, AuthService::class);
        $this->app->bind(CustomerServiceInterface::class, CustomerService::class);
        $this->app->bind(InvoiceServiceInterface::class, InvoiceService::class);
        $this->app->bind(ProductServiceInterface::class, ProductService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
