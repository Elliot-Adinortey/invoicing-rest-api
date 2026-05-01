<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function success(
        mixed $data = null,
        string $message = 'Request successful.',
        string $status = 'success',
        int $status_code = 200,
        array $meta = []
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
            'status' => $status,
            'status_code' => $status_code,
            'data' => $data,
        ];

        if (! empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $status_code);
    }

    public static function error(
        string $message = 'Request failed.',
        array $errors = [],
        int $status = 400,
        string $code = 'REQUEST_FAILED'
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'status' => 'error',
            'status_code' => $status,
            'errors' => $errors,
            'code' => $code,
        ], $status);
    }
}
