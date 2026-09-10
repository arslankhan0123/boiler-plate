<?php

declare(strict_types=1);

namespace Modules\RolesAndPermissions\Http\Controllers;

use App\Facades\ApiResponse;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Modules\RolesAndPermissions\Http\Requests\StoreRoleRequest;
use Modules\RolesAndPermissions\Http\Requests\SyncRolePermissionsRequest;
use Modules\RolesAndPermissions\Http\Requests\UpdateRoleRequest;
use Modules\RolesAndPermissions\Services\RoleService;
use Modules\RolesAndPermissions\Transformers\RoleResource;
use OpenApi\Attributes as OA;

class RoleController extends Controller
{
    public function __construct(private readonly RoleService $roles) {}

    #[OA\Get(
        path: '/api/v1/roles',
        summary: 'List the tenant\'s roles with their permissions',
        security: [['bearerAuth' => []]],
        tags: ['Roles & Permissions'],
    )]
    #[OA\Response(response: 200, description: 'Roles retrieved.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    public function index(): JsonResponse
    {
        $this->authorize('view', Role::class);

        return ApiResponse::successResponse('Roles retrieved.', RoleResource::collection($this->roles->list()));
    }

    #[OA\Post(
        path: '/api/v1/roles',
        summary: 'Create a role (optionally with a permission set)',
        security: [['bearerAuth' => []]],
        tags: ['Roles & Permissions'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Auditor'),
                    new OA\Property(property: 'permissions', type: 'array', items: new OA\Items(type: 'string'), example: ['users.view', 'users.viewFull']),
                ],
            ),
        ),
    )]
    #[OA\Response(response: 200, description: 'Role created.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function store(StoreRoleRequest $request): JsonResponse
    {
        $this->authorize('create', Role::class);

        $role = $this->roles->create(
            $request->validated('name'),
            $request->validated('permissions', []),
        );

        return ApiResponse::successResponse('Role created.', new RoleResource($role));
    }

    #[OA\Get(
        path: '/api/v1/roles/{role}',
        summary: 'Show a role with its permissions',
        security: [['bearerAuth' => []]],
        tags: ['Roles & Permissions'],
        parameters: [new OA\Parameter(name: 'role', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
    )]
    #[OA\Response(response: 200, description: 'Role retrieved.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    public function show(string $role): JsonResponse
    {
        $this->authorize('view', Role::class);

        return ApiResponse::successResponse('Role retrieved.', new RoleResource($this->roles->find($role)));
    }

    #[OA\Put(
        path: '/api/v1/roles/{role}',
        summary: 'Update a role\'s name and/or permissions (defaults cannot be renamed)',
        security: [['bearerAuth' => []]],
        tags: ['Roles & Permissions'],
        parameters: [new OA\Parameter(name: 'role', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Senior Auditor'),
                    new OA\Property(property: 'permissions', type: 'array', items: new OA\Items(type: 'string'), example: ['users.view']),
                ],
            ),
        ),
    )]
    #[OA\Response(response: 200, description: 'Role updated.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function update(UpdateRoleRequest $request, string $role): JsonResponse
    {
        $this->authorize('update', Role::class);

        $updated = $this->roles->update(
            $role,
            $request->validated('name'),
            $request->validated('permissions'),
        );

        return ApiResponse::successResponse('Role updated.', new RoleResource($updated));
    }

    #[OA\Delete(
        path: '/api/v1/roles/{role}',
        summary: 'Delete a role (default roles are protected)',
        security: [['bearerAuth' => []]],
        tags: ['Roles & Permissions'],
        parameters: [new OA\Parameter(name: 'role', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
    )]
    #[OA\Response(response: 200, description: 'Role deleted.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    public function destroy(string $role): JsonResponse
    {
        $this->authorize('delete', Role::class);

        $this->roles->delete($role);

        return ApiResponse::successResponse('Role deleted.');
    }

    #[OA\Put(
        path: '/api/v1/roles/{role}/permissions',
        summary: 'Replace a role\'s permission set',
        security: [['bearerAuth' => []]],
        tags: ['Roles & Permissions'],
        parameters: [new OA\Parameter(name: 'role', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['permissions'],
                properties: [
                    new OA\Property(property: 'permissions', type: 'array', items: new OA\Items(type: 'string'), example: ['users.view', 'users.create']),
                ],
            ),
        ),
    )]
    #[OA\Response(response: 200, description: 'Role permissions updated.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function syncPermissions(SyncRolePermissionsRequest $request, string $role): JsonResponse
    {
        $this->authorize('update', Role::class);

        $updated = $this->roles->syncPermissions($role, $request->validated('permissions'));

        return ApiResponse::successResponse('Role permissions updated.', new RoleResource($updated));
    }
}
