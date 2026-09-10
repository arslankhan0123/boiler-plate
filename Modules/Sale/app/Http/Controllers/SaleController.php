<?php

declare(strict_types=1);

namespace Modules\Sale\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class SaleController extends Controller
{
    /**
     * Module entry point. Placeholder until module resources are built.
     */
    #[OA\Get(
        path: '/api/v1/sale',
        summary: 'Sale module entry point',
        security: [['bearerAuth' => []]],
        tags: ['Sale'],
    )]
    #[OA\Response(
        response: 200,
        description: 'Sale module is ready.',
        content: new OA\JsonContent(
            allOf: [new OA\Schema(ref: '#/components/schemas/ApiSuccess')],
            example: ['status' => 'success', 'code' => 200, 'message' => 'Sale module is ready.', 'data' => null],
        ),
    )]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    public function index(): JsonResponse
    {
        return ApiResponse::successResponse('Sale module is ready.');
    }
}
