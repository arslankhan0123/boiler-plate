<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class JsonResponseService
{
    /**
     * Generate a JSON response with a success status.
     */
    public static function successResponse(string $message, mixed $data = null): JsonResponse
    {
        return self::jsonResponse($message, $data, Response::HTTP_OK, 'success');
    }

    /**
     * Generate a JSON response with a validation error status.
     *
     * @param  array<array-key, mixed>  $errors
     */
    public static function validationErrorResponse(string $message, array $errors = []): JsonResponse
    {
        return self::failedResponse($message, $errors, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * Generate a JSON response with an unauthorized error status.
     *
     * @param  array<array-key, mixed>  $errors
     */
    public static function unauthorizedErrorResponse(string $message, array $errors = []): JsonResponse
    {
        return self::failedResponse($message, $errors, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Generate a JSON response with a forbidden error status.
     *
     * @param  array<array-key, mixed>  $errors
     */
    public static function forbiddenErrorResponse(string $message, array $errors = []): JsonResponse
    {
        return self::failedResponse($message, $errors, Response::HTTP_FORBIDDEN);
    }

    /**
     * Generate a JSON response with a not found error status.
     *
     * @param  array<array-key, mixed>  $errors
     */
    public static function notFoundErrorResponse(string $message, array $errors = []): JsonResponse
    {
        return self::failedResponse($message, $errors, Response::HTTP_NOT_FOUND);
    }

    /**
     * Generate a JSON response with a server error status.
     *
     * @param  array<array-key, mixed>  $errors
     */
    public static function errorResponse(string $message = 'Something went wrong.', array $errors = []): JsonResponse
    {
        return self::failedResponse($message, $errors, Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Build a failed envelope. Detail messages always live in `data.errors`
     * as a list of strings (empty list when there is no extra detail), so the
     * top-level `message` stays a stable, client-safe headline.
     *
     * @param  array<array-key, mixed>  $errors
     */
    public static function failedResponse(string $message, array $errors, int $code): JsonResponse
    {
        $errors = array_values(array_map(
            static fn (mixed $error): string => (string) $error,
            $errors,
        ));

        return self::jsonResponse($message, ['errors' => $errors], $code, 'failed');
    }

    /**
     * Helper function to generate a JSON response.
     */
    public static function jsonResponse(string $message, mixed $data, int $code, string $status): JsonResponse
    {
        if (is_array($data) && $data === []) {
            $data = null;
        }

        return response()->json([
            'status' => $status,
            'code' => $code,
            'message' => $message,
            'data' => $data,
        ], $code);
    }
}
