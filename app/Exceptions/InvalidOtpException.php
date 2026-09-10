<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Facades\ApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Thrown when a password-reset OTP is missing, incorrect, or expired. Renders
 * as a 422 through the standard response envelope so the failure mode stays
 * consistent with the rest of the API.
 */
class InvalidOtpException extends Exception
{
    public function __construct(string $message = 'The OTP is invalid or has expired.')
    {
        parent::__construct($message);
    }

    public function render(Request $request): JsonResponse
    {
        return ApiResponse::validationErrorResponse($this->getMessage());
    }
}
