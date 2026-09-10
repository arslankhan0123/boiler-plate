<?php

declare(strict_types=1);

namespace Modules\Setup\Http\Controllers;

use App\Facades\ApiResponse;
use App\Support\Authorization\ColumnExposure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Modules\Setup\Http\Requests\DeleteDesignationsRequest;
use Modules\Setup\Http\Requests\ForceDeleteDesignationsRequest;
use Modules\Setup\Http\Requests\ImportDesignationsRequest;
use Modules\Setup\Http\Requests\IndexDesignationRequest;
use Modules\Setup\Http\Requests\StoreDesignationRequest;
use Modules\Setup\Http\Requests\UpdateDesignationsRequest;
use Modules\Setup\Models\Designation;
use Modules\Setup\Services\DesignationService;
use Modules\Setup\Transformers\DesignationResource;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Tenant-defined designations (job titles) under a tenant department. Create takes
 * a flat object; update/delete take arrays; import/export are CSV (matched on
 * name); force delete permanently removes soft-deleted rows.
 */
class DesignationController extends Controller
{
    public function __construct(private readonly DesignationService $designations) {}

    #[OA\Get(
        path: '/api/v1/designations',
        summary: 'List the tenant\'s designations (paginated, filterable)',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Designation'],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'department_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'is_active', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20)),
        ],
    )]
    #[OA\Response(response: 200, description: 'Designations retrieved.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    public function index(IndexDesignationRequest $request): JsonResponse
    {
        $this->authorize('view', Designation::class);
        DesignationResource::setColumnContext($request, ColumnExposure::Listing);

        $paginator = $this->designations->paginate($this->filters($request), (int) ($request->validated('per_page') ?? 20));

        return ApiResponse::successResponse(
            'Designations retrieved.',
            paginated($paginator, DesignationResource::collection($paginator->items())),
        );
    }

    #[OA\Get(
        path: '/api/v1/designations/{id}',
        summary: 'Show a single designation (full record)',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Designation'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
    )]
    #[OA\Response(response: 200, description: 'Designation retrieved.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    public function show(Request $request, string $id): JsonResponse
    {
        $this->authorize('viewFull', Designation::class);
        DesignationResource::setColumnContext($request, ColumnExposure::Detail);

        return ApiResponse::successResponse('Designation retrieved.', new DesignationResource($this->designations->find($id)));
    }

    #[OA\Post(
        path: '/api/v1/designations',
        summary: 'Create a tenant-defined designation',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Designation'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'department_id'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Store Manager'),
                    new OA\Property(property: 'code', type: 'string', example: 'DSG-SM'),
                    new OA\Property(property: 'description', type: 'string', example: 'Runs a retail store'),
                    new OA\Property(property: 'department_id', type: 'integer', example: 1),
                    new OA\Property(property: 'data', type: 'object', example: ['grade' => 'M2']),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                    new OA\Property(property: 'is_default', type: 'boolean', example: false),
                    new OA\Property(property: 'sort_order', type: 'integer', example: 0),
                ],
            ),
        ),
    )]
    #[OA\Response(response: 200, description: 'Designation created.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function store(StoreDesignationRequest $request): JsonResponse
    {
        $this->authorize('create', Designation::class);
        DesignationResource::setColumnContext($request, ColumnExposure::Detail);

        $designation = $this->designations->create($request->validated());

        return ApiResponse::successResponse('Designation created.', new DesignationResource($designation));
    }

    #[OA\Put(
        path: '/api/v1/designations',
        summary: 'Update designations (array of {id, ...editable fields})',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Designation'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(type: 'array', items: new OA\Items(properties: [
                new OA\Property(property: 'id', type: 'integer', example: 1),
                new OA\Property(property: 'name', type: 'string', example: 'Store Manager'),
                new OA\Property(property: 'department_id', type: 'integer', example: 1),
                new OA\Property(property: 'is_active', type: 'boolean', example: true),
                new OA\Property(property: 'is_default', type: 'boolean', example: false),
                new OA\Property(property: 'sort_order', type: 'integer', example: 0),
            ])),
        ),
    )]
    #[OA\Response(response: 200, description: 'Designations updated.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function update(UpdateDesignationsRequest $request): JsonResponse
    {
        $this->authorize('update', Designation::class);
        DesignationResource::setColumnContext($request, ColumnExposure::Detail);

        $updated = $this->designations->updateMany($request->rows());

        return ApiResponse::successResponse('Designations updated.', DesignationResource::collection($updated));
    }

    #[OA\Delete(
        path: '/api/v1/designations',
        summary: 'Soft-delete designations (array of ids)',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Designation'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'array', items: new OA\Items(type: 'integer'), example: [1, 2, 3])),
    )]
    #[OA\Response(response: 200, description: 'Designations deleted.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function destroy(DeleteDesignationsRequest $request): JsonResponse
    {
        $this->authorize('delete', Designation::class);

        $deleted = $this->designations->deleteMany($request->ids());

        return ApiResponse::successResponse('Designations deleted.', ['deleted' => $deleted]);
    }

    #[OA\Delete(
        path: '/api/v1/designations/force',
        summary: 'Permanently delete soft-deleted designations (array of ids)',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Designation'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'array', items: new OA\Items(type: 'integer'), example: [1, 2, 3])),
    )]
    #[OA\Response(response: 200, description: 'Designations permanently deleted.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function forceDestroy(ForceDeleteDesignationsRequest $request): JsonResponse
    {
        $this->authorize('hardDelete', Designation::class);

        $deleted = $this->designations->forceDeleteMany($request->ids());

        return ApiResponse::successResponse('Designations permanently deleted.', ['deleted' => $deleted]);
    }

    #[OA\Post(
        path: '/api/v1/designations/import',
        summary: 'Import designations from a CSV file (upsert, matched on name)',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Designation'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(mediaType: 'multipart/form-data', schema: new OA\Schema(properties: [new OA\Property(property: 'file', type: 'string', format: 'binary')])),
        ),
    )]
    #[OA\Response(response: 200, description: 'Import completed.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function import(ImportDesignationsRequest $request): JsonResponse
    {
        $this->authorize('create', Designation::class);

        /** @var UploadedFile $file */
        $file = $request->file('file');

        return ApiResponse::successResponse('Import completed.', $this->designations->importCsv($file));
    }

    #[OA\Get(
        path: '/api/v1/designations/import-sample',
        summary: 'Download a sample CSV template for the designations import',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Designation'],
    )]
    #[OA\Response(response: 200, description: 'Sample CSV file download.', content: new OA\MediaType(mediaType: 'text/csv'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    public function importSample(): BinaryFileResponse
    {
        $this->authorize('create', Designation::class);

        $path = base_path('Modules/Setup/resources/samples/designations-import-sample.csv');

        return response()->download($path, 'designations-import-sample.csv', ['Content-Type' => 'text/csv']);
    }

    #[OA\Get(
        path: '/api/v1/designations/export',
        summary: 'Export the tenant\'s designations as CSV',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Designation'],
        parameters: [
            new OA\Parameter(name: 'department_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'is_active', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
        ],
    )]
    #[OA\Response(response: 200, description: 'CSV file download.', content: new OA\MediaType(mediaType: 'text/csv'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    public function export(IndexDesignationRequest $request): StreamedResponse
    {
        $this->authorize('view', Designation::class);

        $designations = $this->designations->forExport($this->filters($request));

        return response()->streamDownload(function () use ($designations): void {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['name', 'code', 'description', 'department_id', 'is_active', 'is_default', 'sort_order']);

            foreach ($designations as $designation) {
                fputcsv($out, [
                    $designation->name, $designation->code, $designation->description, $designation->department_id,
                    $designation->is_active ? '1' : '0', $designation->is_default ? '1' : '0', $designation->sort_order,
                ]);
            }

            fclose($out);
        }, 'designations.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * Normalised listing/export filters (is_active read as a real boolean).
     *
     * @return array<string, mixed>
     */
    private function filters(IndexDesignationRequest $request): array
    {
        $filters = [
            'search' => $request->validated('search'),
            'department_id' => $request->validated('department_id'),
        ];

        if ($request->has('is_active')) {
            $filters['is_active'] = $request->boolean('is_active');
        }

        return $filters;
    }
}
