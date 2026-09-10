<?php

declare(strict_types=1);

namespace Modules\Admin\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Modules\Admin\Http\Requests\StoreTenantUserRequest;
use Modules\Admin\Services\TenantService;
use OpenApi\Attributes as OA;

class TenantUserController extends Controller
{
    public function __construct(private readonly TenantService $tenants) {}

    #[OA\Post(
        path: '/api/v1/admin/tenants/{tenant}/users',
        summary: 'Create a user for a tenant and assign roles (defaults to Tenant Admin)',
        security: [['bearerAuth' => []]],
        tags: ['Admin · Tenants'],
        parameters: [new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string', example: '001'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'password'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Store Admin'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@newstore.test'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password'),
                    new OA\Property(property: 'phone', type: 'string', nullable: true, example: '+10000000099'),
                    new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string'), example: ['Tenant Admin']),
                ],
            ),
        ),
    )]
    #[OA\Response(response: 200, description: 'Tenant user created.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function store(StoreTenantUserRequest $request, Tenant $tenant): JsonResponse
    {
        $result = $this->tenants->addUser(
            $tenant,
            $request->validated('name'),
            $request->validated('email'),
            $request->validated('password'),
            $request->validated('phone'),
            $request->validated('roles', []),
        );

        return ApiResponse::successResponse('Tenant user created.', [
            'user' => new UserResource($result['user']),
            'roles' => $result['roles'],
        ]);
    }
}
