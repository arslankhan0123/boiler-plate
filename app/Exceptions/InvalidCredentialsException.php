<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Facades\ApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Thrown when login credentials do not match a user. Renders as a 401 through
 * the standard response envelope so the failure mode stays consistent.
 */
class InvalidCredentialsException extends Exception
{
    public function __construct(string $message = 'Invalid credentials.')
    {
        parent::__construct($message);
    }

    public function render(Request $request): JsonResponse
    {
        return ApiResponse::unauthorizedErrorResponse($this->getMessage());
    }
}
