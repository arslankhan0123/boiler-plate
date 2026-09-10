<?php

declare(strict_types=1);

namespace Modules\Setup\Http\Controllers;

use App\Facades\ApiResponse;
use App\Support\Authorization\ColumnExposure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Modules\Setup\Http\Requests\DeleteDepartmentsRequest;
use Modules\Setup\Http\Requests\ForceDeleteDepartmentsRequest;
use Modules\Setup\Http\Requests\ImportDepartmentsRequest;
use Modules\Setup\Http\Requests\IndexDepartmentRequest;
use Modules\Setup\Http\Requests\StoreDepartmentRequest;
use Modules\Setup\Http\Requests\UpdateDepartmentsRequest;
use Modules\Setup\Models\Department;
use Modules\Setup\Services\DepartmentService;
use Modules\Setup\Transformers\DepartmentResource;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Tenant-defined departments (a flat list of custom records, NOT picked from a
 * master). Create takes a flat object; update/delete take arrays; import/export are
 * CSV (matched on name); force delete permanently removes soft-deleted rows.
 */
class DepartmentController extends Controller
{
    public function __construct(private readonly DepartmentService $departments) {}

    #[OA\Get(
        path: '/api/v1/departments',
        summary: 'List the tenant\'s departments (paginated, filterable)',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Department'],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'is_active', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20)),
        ],
    )]
    #[OA\Response(response: 200, description: 'Departments retrieved.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    public function index(IndexDepartmentRequest $request): JsonResponse
    {
        $this->authorize('view', Department::class);
        DepartmentResource::setColumnContext($request, ColumnExposure::Listing);

        $paginator = $this->departments->paginate($this->filters($request), (int) ($request->validated('per_page') ?? 20));

        return ApiResponse::successResponse(
            'Departments retrieved.',
            paginated($paginator, DepartmentResource::collection($paginator->items())),
        );
    }

    #[OA\Get(
        path: '/api/v1/departments/{id}',
        summary: 'Show a single department (full record)',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Department'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
    )]
    #[OA\Response(response: 200, description: 'Department retrieved.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    public function show(Request $request, string $id): JsonResponse
    {
        $this->authorize('viewFull', Department::class);
        DepartmentResource::setColumnContext($request, ColumnExposure::Detail);

        return ApiResponse::successResponse('Department retrieved.', new DepartmentResource($this->departments->find($id)));
    }

    #[OA\Post(
        path: '/api/v1/departments',
        summary: 'Create a tenant-defined department',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Department'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Sales'),
                    new OA\Property(property: 'code', type: 'string', example: 'SAL'),
                    new OA\Property(property: 'description', type: 'string', example: 'Front-of-house sales team'),
                    new OA\Property(property: 'data', type: 'object', example: ['cost_center' => 'CC-100']),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                    new OA\Property(property: 'is_default', type: 'boolean', example: false),
                    new OA\Property(property: 'sort_order', type: 'integer', example: 0),
                ],
            ),
        ),
    )]
    #[OA\Response(response: 200, description: 'Department created.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        $this->authorize('create', Department::class);
        DepartmentResource::setColumnContext($request, ColumnExposure::Detail);

        $department = $this->departments->create($request->validated());

        return ApiResponse::successResponse('Department created.', new DepartmentResource($department));
    }

    #[OA\Put(
        path: '/api/v1/departments',
        summary: 'Update departments (array of {id, ...editable fields})',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Department'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(type: 'array', items: new OA\Items(properties: [
                new OA\Property(property: 'id', type: 'integer', example: 1),
                new OA\Property(property: 'name', type: 'string', example: 'Sales'),
                new OA\Property(property: 'code', type: 'string', example: 'SAL'),
                new OA\Property(property: 'is_active', type: 'boolean', example: true),
                new OA\Property(property: 'is_default', type: 'boolean', example: false),
                new OA\Property(property: 'sort_order', type: 'integer', example: 0),
            ])),
        ),
    )]
    #[OA\Response(response: 200, description: 'Departments updated.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function update(UpdateDepartmentsRequest $request): JsonResponse
    {
        $this->authorize('update', Department::class);
        DepartmentResource::setColumnContext($request, ColumnExposure::Detail);

        $updated = $this->departments->updateMany($request->rows());

        return ApiResponse::successResponse('Departments updated.', DepartmentResource::collection($updated));
    }

    #[OA\Delete(
        path: '/api/v1/departments',
        summary: 'Soft-delete departments (array of ids)',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Department'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'array', items: new OA\Items(type: 'integer'), example: [1, 2, 3])),
    )]
    #[OA\Response(response: 200, description: 'Departments deleted.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function destroy(DeleteDepartmentsRequest $request): JsonResponse
    {
        $this->authorize('delete', Department::class);

        $deleted = $this->departments->deleteMany($request->ids());

        return ApiResponse::successResponse('Departments deleted.', ['deleted' => $deleted]);
    }

    #[OA\Delete(
        path: '/api/v1/departments/force',
        summary: 'Permanently delete soft-deleted departments (array of ids)',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Department'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'array', items: new OA\Items(type: 'integer'), example: [1, 2, 3])),
    )]
    #[OA\Response(response: 200, description: 'Departments permanently deleted.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function forceDestroy(ForceDeleteDepartmentsRequest $request): JsonResponse
    {
        $this->authorize('hardDelete', Department::class);

        $deleted = $this->departments->forceDeleteMany($request->ids());

        return ApiResponse::successResponse('Departments permanently deleted.', ['deleted' => $deleted]);
    }

    #[OA\Post(
        path: '/api/v1/departments/import',
        summary: 'Import departments from a CSV file (upsert, matched on name)',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Department'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(mediaType: 'multipart/form-data', schema: new OA\Schema(properties: [new OA\Property(property: 'file', type: 'string', format: 'binary')])),
        ),
    )]
    #[OA\Response(response: 200, description: 'Import completed.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function import(ImportDepartmentsRequest $request): JsonResponse
    {
        $this->authorize('create', Department::class);

        /** @var UploadedFile $file */
        $file = $request->file('file');

        return ApiResponse::successResponse('Import completed.', $this->departments->importCsv($file));
    }

    #[OA\Get(
        path: '/api/v1/departments/import-sample',
        summary: 'Download a sample CSV template for the departments import',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Department'],
    )]
    #[OA\Response(response: 200, description: 'Sample CSV file download.', content: new OA\MediaType(mediaType: 'text/csv'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    public function importSample(): BinaryFileResponse
    {
        $this->authorize('create', Department::class);

        $path = base_path('Modules/Setup/resources/samples/departments-import-sample.csv');

        return response()->download($path, 'departments-import-sample.csv', ['Content-Type' => 'text/csv']);
    }

    #[OA\Get(
        path: '/api/v1/departments/export',
        summary: 'Export the tenant\'s departments as CSV',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Department'],
        parameters: [new OA\Parameter(name: 'is_active', in: 'query', required: false, schema: new OA\Schema(type: 'boolean'))],
    )]
    #[OA\Response(response: 200, description: 'CSV file download.', content: new OA\MediaType(mediaType: 'text/csv'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    public function export(IndexDepartmentRequest $request): StreamedResponse
    {
        $this->authorize('view', Department::class);

        $departments = $this->departments->forExport($this->filters($request));

        return response()->streamDownload(function () use ($departments): void {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['name', 'code', 'description', 'is_active', 'is_default', 'sort_order']);

            foreach ($departments as $department) {
                fputcsv($out, [
                    $department->name, $department->code, $department->description,
                    $department->is_active ? '1' : '0', $department->is_default ? '1' : '0', $department->sort_order,
                ]);
            }

            fclose($out);
        }, 'departments.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * Normalised listing/export filters (is_active read as a real boolean).
     *
     * @return array<string, mixed>
     */
    private function filters(IndexDepartmentRequest $request): array
    {
        $filters = [
            'search' => $request->validated('search'),
        ];

        if ($request->has('is_active')) {
            $filters['is_active'] = $request->boolean('is_active');
        }

        return $filters;
    }
}
