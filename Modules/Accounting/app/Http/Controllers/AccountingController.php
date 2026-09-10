<?php

declare(strict_types=1);

namespace Modules\Accounting\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class AccountingController extends Controller
{
    /**
     * Module entry point. Placeholder until module resources are built.
     */
    #[OA\Get(
        path: '/api/v1/accounting',
        summary: 'Accounting module entry point',
        security: [['bearerAuth' => []]],
        tags: ['Accounting'],
    )]
    #[OA\Response(
        response: 200,
        description: 'Accounting module is ready.',
        content: new OA\JsonContent(
            allOf: [new OA\Schema(ref: '#/components/schemas/ApiSuccess')],
            example: ['status' => 'success', 'code' => 200, 'message' => 'Accounting module is ready.', 'data' => null],
        ),
    )]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    public function index(): JsonResponse
    {
        return ApiResponse::successResponse('Accounting module is ready.');
    }
}
