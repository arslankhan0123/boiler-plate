<?php

declare(strict_types=1);

namespace Modules\Setup\Http\Controllers;

use App\Facades\ApiResponse;
use App\Support\Authorization\ColumnExposure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Modules\Setup\Http\Requests\DeleteAreasRequest;
use Modules\Setup\Http\Requests\ForceDeleteAreasRequest;
use Modules\Setup\Http\Requests\ImportAreasRequest;
use Modules\Setup\Http\Requests\IndexAreaRequest;
use Modules\Setup\Http\Requests\StoreAreaRequest;
use Modules\Setup\Http\Requests\UpdateAreasRequest;
use Modules\Setup\Models\Area;
use Modules\Setup\Services\AreaService;
use Modules\Setup\Transformers\AreaResource;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Tenant-defined areas/localities under a tenant city. Unlike the other Setup
 * resources, areas are NOT picked from the master — create takes a flat object of
 * custom fields; update/delete take arrays; import/export are CSV (matched on
 * city_id + name); force delete permanently removes soft-deleted rows.
 */
class AreaController extends Controller
{
    public function __construct(private readonly AreaService $areas) {}

    #[OA\Get(
        path: '/api/v1/areas',
        summary: 'List the tenant\'s areas (paginated, filterable)',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Area'],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'city_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'is_active', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20)),
        ],
    )]
    #[OA\Response(response: 200, description: 'Areas retrieved.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    public function index(IndexAreaRequest $request): JsonResponse
    {
        $this->authorize('view', Area::class);
        AreaResource::setColumnContext($request, ColumnExposure::Listing);

        $paginator = $this->areas->paginate($this->filters($request), (int) ($request->validated('per_page') ?? 20));

        return ApiResponse::successResponse(
            'Areas retrieved.',
            paginated($paginator, AreaResource::collection($paginator->items())),
        );
    }

    #[OA\Get(
        path: '/api/v1/areas/{id}',
        summary: 'Show a single area (full record)',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Area'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
    )]
    #[OA\Response(response: 200, description: 'Area retrieved.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    public function show(Request $request, string $id): JsonResponse
    {
        $this->authorize('viewFull', Area::class);
        AreaResource::setColumnContext($request, ColumnExposure::Detail);

        return ApiResponse::successResponse('Area retrieved.', new AreaResource($this->areas->find($id)));
    }

    #[OA\Post(
        path: '/api/v1/areas',
        summary: 'Create a tenant-defined area under a city',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Area'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['city_id', 'name'],
                properties: [
                    new OA\Property(property: 'city_id', type: 'integer', example: 1),
                    new OA\Property(property: 'name', type: 'string', example: 'Downtown'),
                    new OA\Property(property: 'address_line_1', type: 'string', example: '123 Main St'),
                    new OA\Property(property: 'address_line_2', type: 'string', example: 'Suite 100'),
                    new OA\Property(property: 'area_code', type: 'string', example: '90001'),
                    new OA\Property(property: 'phone', type: 'string', example: '+1-213-555-0100'),
                    new OA\Property(property: 'email', type: 'string', example: 'downtown@example.com'),
                    new OA\Property(property: 'description', type: 'string', example: 'Central business district'),
                    new OA\Property(property: 'latitude', type: 'number', format: 'float', example: 34.04),
                    new OA\Property(property: 'longitude', type: 'number', format: 'float', example: -118.246),
                    new OA\Property(property: 'data', type: 'object', example: ['delivery_zone' => 'A']),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                    new OA\Property(property: 'is_default', type: 'boolean', example: false),
                    new OA\Property(property: 'sort_order', type: 'integer', example: 0),
                ],
            ),
        ),
    )]
    #[OA\Response(response: 200, description: 'Area created.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function store(StoreAreaRequest $request): JsonResponse
    {
        $this->authorize('create', Area::class);
        AreaResource::setColumnContext($request, ColumnExposure::Detail);

        $area = $this->areas->create($request->validated());

        return ApiResponse::successResponse('Area created.', new AreaResource($area));
    }

    #[OA\Put(
        path: '/api/v1/areas',
        summary: 'Update areas (array of {id, ...editable fields})',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Area'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(type: 'array', items: new OA\Items(properties: [
                new OA\Property(property: 'id', type: 'integer', example: 1),
                new OA\Property(property: 'name', type: 'string', example: 'Downtown'),
                new OA\Property(property: 'area_code', type: 'string', example: '90001'),
                new OA\Property(property: 'is_active', type: 'boolean', example: true),
                new OA\Property(property: 'is_default', type: 'boolean', example: false),
                new OA\Property(property: 'sort_order', type: 'integer', example: 0),
            ])),
        ),
    )]
    #[OA\Response(response: 200, description: 'Areas updated.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function update(UpdateAreasRequest $request): JsonResponse
    {
        $this->authorize('update', Area::class);
        AreaResource::setColumnContext($request, ColumnExposure::Detail);

        $updated = $this->areas->updateMany($request->rows());

        return ApiResponse::successResponse('Areas updated.', AreaResource::collection($updated));
    }

    #[OA\Delete(
        path: '/api/v1/areas',
        summary: 'Soft-delete areas (array of ids)',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Area'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'array', items: new OA\Items(type: 'integer'), example: [1, 2, 3])),
    )]
    #[OA\Response(response: 200, description: 'Areas deleted.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function destroy(DeleteAreasRequest $request): JsonResponse
    {
        $this->authorize('delete', Area::class);

        $deleted = $this->areas->deleteMany($request->ids());

        return ApiResponse::successResponse('Areas deleted.', ['deleted' => $deleted]);
    }

    #[OA\Delete(
        path: '/api/v1/areas/force',
        summary: 'Permanently delete soft-deleted areas (array of ids)',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Area'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'array', items: new OA\Items(type: 'integer'), example: [1, 2, 3])),
    )]
    #[OA\Response(response: 200, description: 'Areas permanently deleted.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function forceDestroy(ForceDeleteAreasRequest $request): JsonResponse
    {
        $this->authorize('hardDelete', Area::class);

        $deleted = $this->areas->forceDeleteMany($request->ids());

        return ApiResponse::successResponse('Areas permanently deleted.', ['deleted' => $deleted]);
    }

    #[OA\Post(
        path: '/api/v1/areas/import',
        summary: 'Import areas from a CSV file (upsert, matched on city_id + name)',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Area'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(mediaType: 'multipart/form-data', schema: new OA\Schema(properties: [new OA\Property(property: 'file', type: 'string', format: 'binary')])),
        ),
    )]
    #[OA\Response(response: 200, description: 'Import completed.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function import(ImportAreasRequest $request): JsonResponse
    {
        $this->authorize('create', Area::class);

        /** @var UploadedFile $file */
        $file = $request->file('file');

        return ApiResponse::successResponse('Import completed.', $this->areas->importCsv($file));
    }

    #[OA\Get(
        path: '/api/v1/areas/import-sample',
        summary: 'Download a sample CSV template for the areas import',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Area'],
    )]
    #[OA\Response(response: 200, description: 'Sample CSV file download.', content: new OA\MediaType(mediaType: 'text/csv'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    public function importSample(): BinaryFileResponse
    {
        $this->authorize('create', Area::class);

        $path = base_path('Modules/Setup/resources/samples/areas-import-sample.csv');

        return response()->download($path, 'areas-import-sample.csv', ['Content-Type' => 'text/csv']);
    }

    #[OA\Get(
        path: '/api/v1/areas/export',
        summary: 'Export the tenant\'s areas as CSV',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Area'],
        parameters: [
            new OA\Parameter(name: 'city_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'is_active', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
        ],
    )]
    #[OA\Response(response: 200, description: 'CSV file download.', content: new OA\MediaType(mediaType: 'text/csv'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    public function export(IndexAreaRequest $request): StreamedResponse
    {
        $this->authorize('view', Area::class);

        $areas = $this->areas->forExport($this->filters($request));

        return response()->streamDownload(function () use ($areas): void {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['city_id', 'name', 'address_line_1', 'address_line_2', 'area_code', 'phone', 'email', 'description', 'latitude', 'longitude', 'is_active', 'is_default', 'sort_order']);

            foreach ($areas as $area) {
                fputcsv($out, [
                    $area->city_id, $area->name, $area->address_line_1, $area->address_line_2, $area->area_code,
                    $area->phone, $area->email, $area->description, $area->latitude, $area->longitude,
                    $area->is_active ? '1' : '0', $area->is_default ? '1' : '0', $area->sort_order,
                ]);
            }

            fclose($out);
        }, 'areas.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * Normalised listing/export filters (is_active read as a real boolean).
     *
     * @return array<string, mixed>
     */
    private function filters(IndexAreaRequest $request): array
    {
        $filters = [
            'search' => $request->validated('search'),
            'city_id' => $request->validated('city_id'),
        ];

        if ($request->has('is_active')) {
            $filters['is_active'] = $request->boolean('is_active');
        }

        return $filters;
    }
}
