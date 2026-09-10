<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class InventoryController extends Controller
{
    /**
     * Module entry point. Placeholder until module resources are built.
     */
    #[OA\Get(
        path: '/api/v1/inventory',
        summary: 'Inventory module entry point',
        security: [['bearerAuth' => []]],
        tags: ['Inventory'],
    )]
    #[OA\Response(
        response: 200,
        description: 'Inventory module is ready.',
        content: new OA\JsonContent(
            allOf: [new OA\Schema(ref: '#/components/schemas/ApiSuccess')],
            example: ['status' => 'success', 'code' => 200, 'message' => 'Inventory module is ready.', 'data' => null],
        ),
    )]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    public function index(): JsonResponse
    {
        return ApiResponse::successResponse('Inventory module is ready.');
    }
}
