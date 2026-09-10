<?php

declare(strict_types=1);

namespace Modules\RolesAndPermissions\Http\Controllers;

use App\Facades\ApiResponse;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;
use Modules\RolesAndPermissions\Services\PermissionCatalogService;
use OpenApi\Attributes as OA;

class PermissionController extends Controller
{
    public function __construct(private readonly PermissionCatalogService $permissions) {}

    #[OA\Get(
        path: '/api/v1/permissions',
        summary: 'List the available permissions, grouped by resource (read-only catalog)',
        security: [['bearerAuth' => []]],
        tags: ['Roles & Permissions'],
    )]
    #[OA\Response(response: 200, description: 'Permissions retrieved.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    public function index(): JsonResponse
    {
        $this->authorize('view', Permission::class);

        return ApiResponse::successResponse('Permissions retrieved.', $this->permissions->grouped());
    }
}
