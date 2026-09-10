<?php

declare(strict_types=1);

namespace Modules\RolesAndPermissions\Http\Controllers;

use App\Facades\ApiResponse;
use App\Models\ColumnVisibility;
use Illuminate\Http\JsonResponse;
use Modules\RolesAndPermissions\Http\Requests\UpdateColumnVisibilityRequest;
use Modules\RolesAndPermissions\Services\ColumnVisibilityMatrixService;
use OpenApi\Attributes as OA;

class ColumnVisibilityController extends Controller
{
    public function __construct(private readonly ColumnVisibilityMatrixService $matrix) {}

    #[OA\Get(
        path: '/api/v1/column-catalog',
        summary: 'List the catalogued columns a role\'s visibility can target',
        security: [['bearerAuth' => []]],
        tags: ['Roles & Permissions'],
    )]
    #[OA\Response(response: 200, description: 'Column catalog retrieved.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    public function catalog(): JsonResponse
    {
        $this->authorize('view', ColumnVisibility::class);

        return ApiResponse::successResponse('Column catalog retrieved.', $this->matrix->catalog());
    }

    #[OA\Get(
        path: '/api/v1/roles/{role}/column-visibility',
        summary: 'Show a role\'s column-visibility matrix (base, granted, effective)',
        security: [['bearerAuth' => []]],
        tags: ['Roles & Permissions'],
        parameters: [new OA\Parameter(name: 'role', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
    )]
    #[OA\Response(response: 200, description: 'Column visibility matrix retrieved.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    public function show(string $role): JsonResponse
    {
        $this->authorize('view', ColumnVisibility::class);

        return ApiResponse::successResponse('Column visibility matrix retrieved.', $this->matrix->matrixFor($role));
    }

    #[OA\Put(
        path: '/api/v1/roles/{role}/column-visibility',
        summary: 'Update a role\'s column grants (raise-only; equal-to-base clears)',
        security: [['bearerAuth' => []]],
        tags: ['Roles & Permissions'],
        parameters: [new OA\Parameter(name: 'role', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['grants'],
                properties: [
                    new OA\Property(
                        property: 'grants',
                        type: 'array',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'resource', type: 'string', example: 'users'),
                                new OA\Property(property: 'column', type: 'string', example: 'phone'),
                                new OA\Property(property: 'exposure', type: 'string', enum: ['listing', 'detail', 'hidden'], example: 'detail'),
                            ],
                            type: 'object',
                        ),
                    ),
                ],
            ),
        ),
    )]
    #[OA\Response(response: 200, description: 'Column visibility matrix updated.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function update(UpdateColumnVisibilityRequest $request, string $role): JsonResponse
    {
        $this->authorize('update', ColumnVisibility::class);

        $this->matrix->updateMatrix($role, $request->grants());

        return ApiResponse::successResponse('Column visibility matrix updated.', $this->matrix->matrixFor($role));
    }
}
