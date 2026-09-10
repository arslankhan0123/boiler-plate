<?php

declare(strict_types=1);

namespace App\Support\OpenApi;

use App\Services\JsonResponseService;
use OpenApi\Attributes as OA;

/**
 * Central, reusable OpenAPI components that mirror the response envelope built
 * by {@see JsonResponseService}. Endpoints reference these so the
 * Swagger docs always match the actual JSON contract.
 */
#[OA\Schema(
    schema: 'ApiSuccess',
    title: 'Success envelope',
    description: 'Standard success envelope.',
    properties: [
        new OA\Property(property: 'status', type: 'string', example: 'success'),
        new OA\Property(property: 'code', type: 'integer', example: 200),
        new OA\Property(property: 'message', type: 'string', example: 'Operation successful.'),
        new OA\Property(property: 'data', type: 'object', nullable: true),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'ApiError',
    title: 'Error envelope',
    description: 'Standard failed envelope. Detail messages live in data.errors (list of strings).',
    properties: [
        new OA\Property(property: 'status', type: 'string', example: 'failed'),
        new OA\Property(property: 'code', type: 'integer', example: 500),
        new OA\Property(property: 'message', type: 'string', example: 'Something went wrong.'),
        new OA\Property(
            property: 'data',
            properties: [
                new OA\Property(
                    property: 'errors',
                    type: 'array',
                    items: new OA\Items(type: 'string'),
                ),
            ],
            type: 'object',
            nullable: true,
        ),
    ],
    type: 'object',
)]
#[OA\Response(
    response: 'Unauthorized',
    description: 'Authentication required / token missing or invalid.',
    content: new OA\JsonContent(
        allOf: [new OA\Schema(ref: '#/components/schemas/ApiError')],
        example: ['status' => 'failed', 'code' => 401, 'message' => 'Please login first.', 'data' => ['errors' => []]],
    ),
)]
#[OA\Response(
    response: 'Forbidden',
    description: 'The authenticated user lacks permission for this action.',
    content: new OA\JsonContent(
        allOf: [new OA\Schema(ref: '#/components/schemas/ApiError')],
        example: ['status' => 'failed', 'code' => 403, 'message' => 'This action is unauthorized.', 'data' => ['errors' => []]],
    ),
)]
#[OA\Response(
    response: 'NotFound',
    description: 'The requested resource was not found.',
    content: new OA\JsonContent(
        allOf: [new OA\Schema(ref: '#/components/schemas/ApiError')],
        example: ['status' => 'failed', 'code' => 404, 'message' => 'Resource not found.', 'data' => ['errors' => []]],
    ),
)]
#[OA\Response(
    response: 'ValidationError',
    description: 'The given data was invalid.',
    content: new OA\JsonContent(
        allOf: [new OA\Schema(ref: '#/components/schemas/ApiError')],
        example: [
            'status' => 'failed',
            'code' => 422,
            'message' => 'The given data was invalid.',
            'data' => ['errors' => ['The email field is required.', 'The password field is required.']],
        ],
    ),
)]
#[OA\Response(
    response: 'TooManyRequests',
    description: 'Rate limit exceeded.',
    content: new OA\JsonContent(
        allOf: [new OA\Schema(ref: '#/components/schemas/ApiError')],
        example: ['status' => 'failed', 'code' => 429, 'message' => 'Too many requests.', 'data' => ['errors' => []]],
    ),
)]
#[OA\Response(
    response: 'ServerError',
    description: 'Unexpected server error.',
    content: new OA\JsonContent(
        allOf: [new OA\Schema(ref: '#/components/schemas/ApiError')],
        example: ['status' => 'failed', 'code' => 500, 'message' => 'Something went wrong.', 'data' => ['errors' => []]],
    ),
)]
final class OpenApiComponents {}
