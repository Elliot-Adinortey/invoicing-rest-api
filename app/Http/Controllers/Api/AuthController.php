<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Auth\AuthServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(public AuthServiceInterface $authService) {}

    /**
     * Register a new user.
     *
     * @group Authentication
     *
     * @unauthenticated
     *
     * @bodyParam name string required Example: John Doe
     * @bodyParam email string required Example: [EMAIL_ADDRESS]
     * @bodyParam password string required Example: password
     * @bodyParam password_confirmation string required Example: password
     *
     * @response 201 {
     *     "message": "User registered successfully.",
     *     "status": "success",
     *     "status_code": 201,
     *     "data": {}
     * }
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return ApiResponse::success($result, 'User registered successfully.', 'success', 201);
    }

    /**
     * Login with email and password.
     *
     * @group Authentication
     *
     * @unauthenticated
     *
     * @bodyParam email string required Example: [EMAIL_ADDRESS]
     * @bodyParam password string required Example: password
     *
     * @response 200 {
     *     "message": "Login successful.",
     *     "status": "success",
     *     "status_code": 200,
     *     "data": {
     *         "user": {
     *             "id": "uuid",
     *             "name": "John Doe",
     *             "email": "[EMAIL_ADDRESS]",
     *             "email_verified_at": "datetime",
     *             "created_at": "datetime",
     *             "updated_at": "datetime"
     *         },
     *         "token": "personal_access_token"
     *     }
     * }
     * @response 401 {
     *     "message": "These credentials do not match our records.",
     *     "status": "error",
     *     "status_code": 401,
     *     "errors": [],
     *     "code": "REQUEST_FAILED"
     * }
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        return ApiResponse::success($result, 'Login successful.');
    }

    /**
     * Logout user.
     *
     * @group Authentication
     *
     * @authenticated
     *
     * @response 200 {
     *     "message": "Logout successful.",
     *     "status": "success",
     *     "status_code": 200,
     *     "data": null
     * }
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request);

        return ApiResponse::success(null, 'Logout successful.');
    }
}
