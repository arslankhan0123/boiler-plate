<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class PurchaseController extends Controller
{
    /**
     * Module entry point. Placeholder until module resources are built.
     */
    #[OA\Get(
        path: '/api/v1/purchase',
        summary: 'Purchase module entry point',
        security: [['bearerAuth' => []]],
        tags: ['Purchase'],
    )]
    #[OA\Response(
        response: 200,
        description: 'Purchase module is ready.',
        content: new OA\JsonContent(
            allOf: [new OA\Schema(ref: '#/components/schemas/ApiSuccess')],
            example: ['status' => 'success', 'code' => 200, 'message' => 'Purchase module is ready.', 'data' => null],
        ),
    )]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    public function index(): JsonResponse
    {
        return ApiResponse::successResponse('Purchase module is ready.');
    }
}
