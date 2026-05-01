<?php

use App\Support\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->expectsJson()) {
                return ApiResponse::error(
                    message: 'Validation failed.',
                    errors: $e->errors(),
                    status: 422,
                    code: 'VALIDATION_ERROR'
                );
            }
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->expectsJson()) {
                return ApiResponse::error(
                    message: 'Unauthenticated.',
                    status: 401,
                    code: 'UNAUTHENTICATED'
                );
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->expectsJson()) {
                return ApiResponse::error(
                    message: 'Resource not found.',
                    status: 404,
                    code: 'RESOURCE_NOT_FOUND'
                );
            }
        });

        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            if ($e instanceof HttpExceptionInterface) {
                return ApiResponse::error(
                    message: $e->getMessage() ?: 'Request failed.',
                    status: $e->getStatusCode(),
                    code: 'HTTP_ERROR'
                );
            }

            report($e);

            return ApiResponse::error(
                message: app()->isProduction()
                    ? 'Something went wrong.'
                    : $e->getMessage(),
                status: 500,
                code: 'SERVER_ERROR'
            );
        });
    })->create();
