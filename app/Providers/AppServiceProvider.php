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
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        RateLimiter::for('auth.login', function (Request $request) {
             $key = Str::lower($request->input('email')).'|'.$request->ip();
            return Limit::perMinute(5)->by($key)
                ->response(function () {
                    return response()->json([
                        'status' => 'error',
                        'code' => 429,
                        'message' => 'Too many login attempts, please try again later.'
                    ], 429);
                });
        });

        RateLimiter::for('auth.register', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip())
                ->response(function () {
                    return response()->json([
                        'status' => 'error',
                        'code' => 429,
                        'message' => 'Too many registration attempts, please try again later.'
                    ], 429);
                });
        });
    }
}
