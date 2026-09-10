<?php

declare(strict_types=1);

namespace Modules\Setup\Http\Controllers;

use App\Facades\ApiResponse;
use App\Support\Authorization\ColumnExposure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Modules\Setup\Http\Requests\DeleteCitiesRequest;
use Modules\Setup\Http\Requests\ForceDeleteCitiesRequest;
use Modules\Setup\Http\Requests\ImportCitiesRequest;
use Modules\Setup\Http\Requests\IndexCityRequest;
use Modules\Setup\Http\Requests\StoreCitiesRequest;
use Modules\Setup\Http\Requests\UpdateCitiesRequest;
use Modules\Setup\Models\City;
use Modules\Setup\Services\CityService;
use Modules\Setup\Transformers\CityResource;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The tenant's selected cities (snapshot of the central master). Create picks
 * master `lookup_id`s (type=city); update/delete take arrays; import/export are
 * CSV (matched on external_id); force delete permanently removes soft-deleted rows.
 */
class CityController extends Controller
{
    public function __construct(private readonly CityService $cities) {}

    #[OA\Get(
        path: '/api/v1/cities',
        summary: 'List the tenant\'s cities (paginated, filterable)',
        security: [['bearerAuth' => []]],
        tags: ['Setup / City'],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'state_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'country_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'is_active', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20)),
        ],
    )]
    #[OA\Response(response: 200, description: 'Cities retrieved.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    public function index(IndexCityRequest $request): JsonResponse
    {
        $this->authorize('view', City::class);
        CityResource::setColumnContext($request, ColumnExposure::Listing);

        $paginator = $this->cities->paginate($this->filters($request), (int) ($request->validated('per_page') ?? 20));

        return ApiResponse::successResponse(
            'Cities retrieved.',
            paginated($paginator, CityResource::collection($paginator->items())),
        );
    }

    #[OA\Get(
        path: '/api/v1/cities/{id}',
        summary: 'Show a single city (full record)',
        security: [['bearerAuth' => []]],
        tags: ['Setup / City'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
    )]
    #[OA\Response(response: 200, description: 'City retrieved.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    public function show(Request $request, string $id): JsonResponse
    {
        $this->authorize('viewFull', City::class);
        CityResource::setColumnContext($request, ColumnExposure::Detail);

        return ApiResponse::successResponse('City retrieved.', new CityResource($this->cities->find($id)));
    }

    #[OA\Post(
        path: '/api/v1/cities',
        summary: 'Add cities by picking master lookup ids',
        security: [['bearerAuth' => []]],
        tags: ['Setup / City'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(type: 'array', items: new OA\Items(properties: [new OA\Property(property: 'lookup_id', type: 'integer', example: 52)])),
        ),
    )]
    #[OA\Response(response: 200, description: 'Cities added.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function store(StoreCitiesRequest $request): JsonResponse
    {
        $this->authorize('create', City::class);
        CityResource::setColumnContext($request, ColumnExposure::Detail);

        $created = $this->cities->createFromLookups($request->lookupIds());

        return ApiResponse::successResponse('Cities added.', CityResource::collection($created));
    }

    #[OA\Put(
        path: '/api/v1/cities',
        summary: 'Update the tenant-owned fields of cities (array of {id, ...})',
        security: [['bearerAuth' => []]],
        tags: ['Setup / City'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(type: 'array', items: new OA\Items(properties: [
                new OA\Property(property: 'id', type: 'integer', example: 1),
                new OA\Property(property: 'is_active', type: 'boolean', example: true),
                new OA\Property(property: 'is_default', type: 'boolean', example: false),
                new OA\Property(property: 'sort_order', type: 'integer', example: 0),
            ])),
        ),
    )]
    #[OA\Response(response: 200, description: 'Cities updated.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function update(UpdateCitiesRequest $request): JsonResponse
    {
        $this->authorize('update', City::class);
        CityResource::setColumnContext($request, ColumnExposure::Detail);

        $updated = $this->cities->updateMany($request->rows());

        return ApiResponse::successResponse('Cities updated.', CityResource::collection($updated));
    }

    #[OA\Delete(
        path: '/api/v1/cities',
        summary: 'Soft-delete cities (array of ids)',
        security: [['bearerAuth' => []]],
        tags: ['Setup / City'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'array', items: new OA\Items(type: 'integer'), example: [1, 2, 3])),
    )]
    #[OA\Response(response: 200, description: 'Cities deleted.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function destroy(DeleteCitiesRequest $request): JsonResponse
    {
        $this->authorize('delete', City::class);

        $deleted = $this->cities->deleteMany($request->ids());

        return ApiResponse::successResponse('Cities deleted.', ['deleted' => $deleted]);
    }

    #[OA\Delete(
        path: '/api/v1/cities/force',
        summary: 'Permanently delete soft-deleted cities (array of ids)',
        security: [['bearerAuth' => []]],
        tags: ['Setup / City'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'array', items: new OA\Items(type: 'integer'), example: [1, 2, 3])),
    )]
    #[OA\Response(response: 200, description: 'Cities permanently deleted.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function forceDestroy(ForceDeleteCitiesRequest $request): JsonResponse
    {
        $this->authorize('hardDelete', City::class);

        $deleted = $this->cities->forceDeleteMany($request->ids());

        return ApiResponse::successResponse('Cities permanently deleted.', ['deleted' => $deleted]);
    }

    #[OA\Post(
        path: '/api/v1/cities/import',
        summary: 'Import cities from a CSV file (upsert, matched on external_id)',
        security: [['bearerAuth' => []]],
        tags: ['Setup / City'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(mediaType: 'multipart/form-data', schema: new OA\Schema(properties: [new OA\Property(property: 'file', type: 'string', format: 'binary')])),
        ),
    )]
    #[OA\Response(response: 200, description: 'Import completed.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function import(ImportCitiesRequest $request): JsonResponse
    {
        $this->authorize('create', City::class);

        /** @var UploadedFile $file */
        $file = $request->file('file');

        return ApiResponse::successResponse('Import completed.', $this->cities->importCsv($file));
    }

    #[OA\Get(
        path: '/api/v1/cities/import-sample',
        summary: 'Download a sample CSV template for the cities import',
        security: [['bearerAuth' => []]],
        tags: ['Setup / City'],
    )]
    #[OA\Response(response: 200, description: 'Sample CSV file download.', content: new OA\MediaType(mediaType: 'text/csv'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    public function importSample(): BinaryFileResponse
    {
        $this->authorize('create', City::class);

        $path = base_path('Modules/Setup/resources/samples/cities-import-sample.csv');

        return response()->download($path, 'cities-import-sample.csv', ['Content-Type' => 'text/csv']);
    }

    #[OA\Get(
        path: '/api/v1/cities/export',
        summary: 'Export the tenant\'s cities as CSV',
        security: [['bearerAuth' => []]],
        tags: ['Setup / City'],
        parameters: [new OA\Parameter(name: 'is_active', in: 'query', required: false, schema: new OA\Schema(type: 'boolean'))],
    )]
    #[OA\Response(response: 200, description: 'CSV file download.', content: new OA\MediaType(mediaType: 'text/csv'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    public function export(IndexCityRequest $request): StreamedResponse
    {
        $this->authorize('view', City::class);

        $cities = $this->cities->forExport($this->filters($request));

        return response()->streamDownload(function () use ($cities): void {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['external_id', 'name', 'latitude', 'longitude', 'is_active', 'is_default', 'sort_order']);

            foreach ($cities as $city) {
                fputcsv($out, [
                    $city->external_id, $city->name,
                    $city->latitude, $city->longitude, $city->is_active ? '1' : '0', $city->is_default ? '1' : '0', $city->sort_order,
                ]);
            }

            fclose($out);
        }, 'cities.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * Normalised listing/export filters (is_active read as a real boolean).
     *
     * @return array<string, mixed>
     */
    private function filters(IndexCityRequest $request): array
    {
        $filters = [
            'search' => $request->validated('search'),
            'state_id' => $request->validated('state_id'),
            'country_id' => $request->validated('country_id'),
        ];

        if ($request->has('is_active')) {
            $filters['is_active'] = $request->boolean('is_active');
        }

        return $filters;
    }
}
