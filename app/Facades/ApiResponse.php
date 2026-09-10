<?php

declare(strict_types=1);

namespace App\Facades;

use App\Services\JsonResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Facade;

/**
 * Static-style proxy to the bound {@see JsonResponseService}.
 *
 * @method static JsonResponse successResponse(string $message, mixed $data = null)
 * @method static JsonResponse validationErrorResponse(string $message, array<array-key, mixed> $errors = [])
 * @method static JsonResponse unauthorizedErrorResponse(string $message, array<array-key, mixed> $errors = [])
 * @method static JsonResponse forbiddenErrorResponse(string $message, array<array-key, mixed> $errors = [])
 * @method static JsonResponse notFoundErrorResponse(string $message, array<array-key, mixed> $errors = [])
 * @method static JsonResponse errorResponse(string $message = 'Something went wrong.', array<array-key, mixed> $errors = [])
 * @method static JsonResponse failedResponse(string $message, array<array-key, mixed> $errors, int $code)
 * @method static JsonResponse jsonResponse(string $message, mixed $data, int $code, string $status)
 *
 * @see JsonResponseService
 */
class ApiResponse extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'api-response';
    }
}
