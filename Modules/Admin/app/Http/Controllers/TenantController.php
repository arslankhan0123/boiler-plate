<?php

declare(strict_types=1);

namespace Modules\Admin\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Admin\Http\Requests\StoreTenantRequest;
use Modules\Admin\Services\TenantService;
use Modules\Admin\Transformers\TenantResource;
use OpenApi\Attributes as OA;

class TenantController extends Controller
{
    public function __construct(private readonly TenantService $tenants) {}

    #[OA\Get(
        path: '/api/v1/admin/tenants',
        summary: 'List all tenants (platform admin only)',
        security: [['bearerAuth' => []]],
        tags: ['Admin · Tenants'],
    )]
    #[OA\Response(response: 200, description: 'Tenants retrieved.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    public function index(): JsonResponse
    {
        return ApiResponse::successResponse('Tenants retrieved.', TenantResource::collection($this->tenants->list()));
    }

    #[OA\Post(
        path: '/api/v1/admin/tenants',
        summary: 'Create a tenant (provisions its database automatically)',
        security: [['bearerAuth' => []]],
        tags: ['Admin · Tenants'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'New Store'),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                ],
            ),
        ),
    )]
    #[OA\Response(response: 200, description: 'Tenant created.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function store(StoreTenantRequest $request): JsonResponse
    {
        $tenant = $this->tenants->createTenant(
            $request->validated('name'),
            (bool) $request->validated('is_active', true),
        );

        return ApiResponse::successResponse('Tenant created.', new TenantResource($tenant));
    }
}
