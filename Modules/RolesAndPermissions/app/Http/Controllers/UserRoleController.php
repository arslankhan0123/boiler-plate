<?php

declare(strict_types=1);

namespace Modules\RolesAndPermissions\Http\Controllers;

use App\Facades\ApiResponse;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Modules\RolesAndPermissions\Http\Requests\SyncUserRolesRequest;
use Modules\RolesAndPermissions\Services\UserRoleService;
use Modules\RolesAndPermissions\Transformers\RoleResource;
use OpenApi\Attributes as OA;

class UserRoleController extends Controller
{
    public function __construct(private readonly UserRoleService $userRoles) {}

    #[OA\Get(
        path: '/api/v1/users/{user}/roles',
        summary: 'List the roles assigned to a tenant user',
        security: [['bearerAuth' => []]],
        tags: ['Roles & Permissions'],
        parameters: [new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
    )]
    #[OA\Response(response: 200, description: 'User roles retrieved.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    public function index(string $user): JsonResponse
    {
        $this->authorize('view', Role::class);

        return ApiResponse::successResponse('User roles retrieved.', RoleResource::collection($this->userRoles->rolesFor($user)));
    }

    #[OA\Put(
        path: '/api/v1/users/{user}/roles',
        summary: 'Replace a tenant user\'s role assignments',
        security: [['bearerAuth' => []]],
        tags: ['Roles & Permissions'],
        parameters: [new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['roles'],
                properties: [
                    new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string'), example: ['Manager', 'Staff']),
                ],
            ),
        ),
    )]
    #[OA\Response(response: 200, description: 'User roles updated.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function sync(SyncUserRolesRequest $request, string $user): JsonResponse
    {
        $this->authorize('update', Role::class);

        $roles = $this->userRoles->sync($user, $request->validated('roles'));

        return ApiResponse::successResponse('User roles updated.', RoleResource::collection($roles));
    }
}
