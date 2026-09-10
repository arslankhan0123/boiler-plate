<?php

declare(strict_types=1);

namespace Modules\Setup\Http\Controllers;

use App\Facades\ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\Setup\Http\Requests\IndexLookupRequest;
use Modules\Setup\Models\Lookup;
use Modules\Setup\Services\LookupService;
use Modules\Setup\Transformers\LookupResource;
use OpenApi\Attributes as OA;

/**
 * Read access to the central `lookups` master (e.g. the full country list) that
 * tenants search to pick the entries they want to add to their own data.
 */
class LookupController extends Controller
{
    public function __construct(private readonly LookupService $lookups) {}

    #[OA\Get(
        path: '/api/v1/lookups',
        summary: 'Search the central lookup master (defaults to countries)',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Country'],
        parameters: [
            new OA\Parameter(name: 'type', in: 'query', required: false, schema: new OA\Schema(type: 'string', default: 'country')),
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'is_active', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20)),
        ],
    )]
    #[OA\Response(response: 200, description: 'Lookups retrieved.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function index(IndexLookupRequest $request): JsonResponse
    {
        $this->authorize('view', Lookup::class);

        $type = (string) $request->validated('type', Lookup::TYPE_COUNTRY);
        $search = $request->validated('search');
        $isActive = $request->has('is_active') ? $request->boolean('is_active') : null;
        $perPage = (int) $request->validated('per_page', 20);

        $paginator = $this->lookups->search($type, $search, $isActive, $perPage);

        return ApiResponse::successResponse(
            'Lookups retrieved.',
            paginated($paginator, LookupResource::collection($paginator->items())),
        );
    }
}
