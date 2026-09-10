<?php

declare(strict_types=1);

namespace Modules\Setup\Http\Controllers;

use App\Facades\ApiResponse;
use App\Support\Authorization\ColumnExposure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Modules\Setup\Http\Requests\DeleteEmployeesRequest;
use Modules\Setup\Http\Requests\ForceDeleteEmployeesRequest;
use Modules\Setup\Http\Requests\ImportEmployeesRequest;
use Modules\Setup\Http\Requests\IndexEmployeeRequest;
use Modules\Setup\Http\Requests\StoreEmployeeRequest;
use Modules\Setup\Http\Requests\UpdateEmployeesRequest;
use Modules\Setup\Models\Employee;
use Modules\Setup\Services\EmployeeService;
use Modules\Setup\Transformers\EmployeeResource;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Tenant-defined employees (core HR profile + a documents child list). Create takes
 * a flat object (with an optional `documents` array, replace-synced); update/delete
 * take arrays; import/export are CSV (scalar fields only, matched on code); force
 * delete permanently removes soft-deleted rows (and their documents).
 */
class EmployeeController extends Controller
{
    /** Date-only columns formatted as Y-m-d in the CSV export. */
    private const CSV_DATE_COLUMNS = [
        'dob', 'iqama_no_expiry_date', 'passport_expiry_date', 'driving_license_expiry_date',
        'tuv_no_expiry_date', 'join_date',
    ];

    public function __construct(private readonly EmployeeService $employees) {}

    #[OA\Get(
        path: '/api/v1/employees',
        summary: 'List the tenant\'s employees (paginated, filterable)',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Employee'],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'department_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'designation_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'is_active', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20)),
        ],
    )]
    #[OA\Response(response: 200, description: 'Employees retrieved.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    public function index(IndexEmployeeRequest $request): JsonResponse
    {
        $this->authorize('view', Employee::class);
        EmployeeResource::setColumnContext($request, ColumnExposure::Listing);

        $paginator = $this->employees->paginate($this->filters($request), (int) ($request->validated('per_page') ?? 20));

        return ApiResponse::successResponse(
            'Employees retrieved.',
            paginated($paginator, EmployeeResource::collection($paginator->items())),
        );
    }

    #[OA\Get(
        path: '/api/v1/employees/{id}',
        summary: 'Show a single employee (full record incl. documents)',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Employee'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
    )]
    #[OA\Response(response: 200, description: 'Employee retrieved.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    public function show(Request $request, string $id): JsonResponse
    {
        $this->authorize('viewFull', Employee::class);
        EmployeeResource::setColumnContext($request, ColumnExposure::Detail);

        return ApiResponse::successResponse('Employee retrieved.', new EmployeeResource($this->employees->find($id)));
    }

    #[OA\Post(
        path: '/api/v1/employees',
        summary: 'Create a tenant-defined employee (with optional documents)',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Employee'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'department_id', 'designation_id', 'join_date'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Asha Khan'),
                    new OA\Property(property: 'code', type: 'string', example: 'EMP-0001'),
                    new OA\Property(property: 'department_id', type: 'integer', example: 1),
                    new OA\Property(property: 'designation_id', type: 'integer', example: 1),
                    new OA\Property(property: 'email', type: 'string', example: 'asha@store.test'),
                    new OA\Property(property: 'phone', type: 'string', example: '+966-50-555-0100'),
                    new OA\Property(property: 'dob', type: 'string', format: 'date', example: '1995-04-12'),
                    new OA\Property(property: 'gender', type: 'string', enum: ['male', 'female', 'others'], example: 'female'),
                    new OA\Property(property: 'marital_status', type: 'string', enum: ['married', 'unmarried', 'divorced'], example: 'unmarried'),
                    new OA\Property(property: 'blood_group', type: 'string', example: 'O+'),
                    new OA\Property(property: 'country_id', type: 'integer', nullable: true, example: 1),
                    new OA\Property(property: 'state_id', type: 'integer', nullable: true, example: 1),
                    new OA\Property(property: 'iqama_no', type: 'string', example: 'IQ1234567'),
                    new OA\Property(property: 'iqama_no_expiry_date', type: 'string', format: 'date', example: '2027-01-31'),
                    new OA\Property(property: 'join_date', type: 'string', format: 'date', example: '2025-06-01'),
                    new OA\Property(property: 'employment_type', type: 'string', example: 'permanent'),
                    new OA\Property(property: 'basic_salary', type: 'number', format: 'float', example: 4500),
                    new OA\Property(property: 'data', type: 'object', example: ['shift' => 'A']),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                    new OA\Property(
                        property: 'documents',
                        type: 'array',
                        items: new OA\Items(properties: [
                            new OA\Property(property: 'name', type: 'string', example: 'Iqama Copy'),
                            new OA\Property(property: 'file', type: 'string', nullable: true, example: 'iqama-asha.pdf'),
                            new OA\Property(property: 'expiry_date', type: 'string', format: 'date', nullable: true, example: '2027-01-31'),
                        ]),
                    ),
                ],
            ),
        ),
    )]
    #[OA\Response(response: 200, description: 'Employee created.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        $this->authorize('create', Employee::class);
        EmployeeResource::setColumnContext($request, ColumnExposure::Detail);

        $employee = $this->employees->create($request->validated());

        return ApiResponse::successResponse('Employee created.', new EmployeeResource($employee));
    }

    #[OA\Put(
        path: '/api/v1/employees',
        summary: 'Update employees (array of {id, ...editable fields}; documents replace-synced)',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Employee'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(type: 'array', items: new OA\Items(properties: [
                new OA\Property(property: 'id', type: 'integer', example: 1),
                new OA\Property(property: 'name', type: 'string', example: 'Asha Khan'),
                new OA\Property(property: 'designation_id', type: 'integer', example: 2),
                new OA\Property(property: 'basic_salary', type: 'number', format: 'float', example: 5000),
                new OA\Property(property: 'is_active', type: 'boolean', example: true),
            ])),
        ),
    )]
    #[OA\Response(response: 200, description: 'Employees updated.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function update(UpdateEmployeesRequest $request): JsonResponse
    {
        $this->authorize('update', Employee::class);
        EmployeeResource::setColumnContext($request, ColumnExposure::Detail);

        $updated = $this->employees->updateMany($request->rows());

        return ApiResponse::successResponse('Employees updated.', EmployeeResource::collection($updated));
    }

    #[OA\Delete(
        path: '/api/v1/employees',
        summary: 'Soft-delete employees (array of ids)',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Employee'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'array', items: new OA\Items(type: 'integer'), example: [1, 2, 3])),
    )]
    #[OA\Response(response: 200, description: 'Employees deleted.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function destroy(DeleteEmployeesRequest $request): JsonResponse
    {
        $this->authorize('delete', Employee::class);

        $deleted = $this->employees->deleteMany($request->ids());

        return ApiResponse::successResponse('Employees deleted.', ['deleted' => $deleted]);
    }

    #[OA\Delete(
        path: '/api/v1/employees/force',
        summary: 'Permanently delete soft-deleted employees (array of ids)',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Employee'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'array', items: new OA\Items(type: 'integer'), example: [1, 2, 3])),
    )]
    #[OA\Response(response: 200, description: 'Employees permanently deleted.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function forceDestroy(ForceDeleteEmployeesRequest $request): JsonResponse
    {
        $this->authorize('hardDelete', Employee::class);

        $deleted = $this->employees->forceDeleteMany($request->ids());

        return ApiResponse::successResponse('Employees permanently deleted.', ['deleted' => $deleted]);
    }

    #[OA\Post(
        path: '/api/v1/employees/import',
        summary: 'Import employees from a CSV file (upsert, matched on code; scalar fields only)',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Employee'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(mediaType: 'multipart/form-data', schema: new OA\Schema(properties: [new OA\Property(property: 'file', type: 'string', format: 'binary')])),
        ),
    )]
    #[OA\Response(response: 200, description: 'Import completed.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function import(ImportEmployeesRequest $request): JsonResponse
    {
        $this->authorize('create', Employee::class);

        /** @var UploadedFile $file */
        $file = $request->file('file');

        return ApiResponse::successResponse('Import completed.', $this->employees->importCsv($file));
    }

    #[OA\Get(
        path: '/api/v1/employees/import-sample',
        summary: 'Download a sample CSV template for the employees import',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Employee'],
    )]
    #[OA\Response(response: 200, description: 'Sample CSV file download.', content: new OA\MediaType(mediaType: 'text/csv'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    public function importSample(): BinaryFileResponse
    {
        $this->authorize('create', Employee::class);

        $path = base_path('Modules/Setup/resources/samples/employees-import-sample.csv');

        return response()->download($path, 'employees-import-sample.csv', ['Content-Type' => 'text/csv']);
    }

    #[OA\Get(
        path: '/api/v1/employees/export',
        summary: 'Export the tenant\'s employees as CSV (scalar fields only)',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Employee'],
        parameters: [
            new OA\Parameter(name: 'department_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'designation_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'is_active', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
        ],
    )]
    #[OA\Response(response: 200, description: 'CSV file download.', content: new OA\MediaType(mediaType: 'text/csv'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    public function export(IndexEmployeeRequest $request): StreamedResponse
    {
        $this->authorize('view', Employee::class);

        $employees = $this->employees->forExport($this->filters($request));
        $columns = EmployeeService::csvColumns();

        return response()->streamDownload(function () use ($employees, $columns): void {
            $out = fopen('php://output', 'w');

            fputcsv($out, $columns);

            foreach ($employees as $employee) {
                $row = [];
                foreach ($columns as $col) {
                    $value = $employee->{$col};

                    if ($col === 'is_active' || $col === 'is_default') {
                        $value = $value ? '1' : '0';
                    } elseif (in_array($col, self::CSV_DATE_COLUMNS, true) && $value !== null) {
                        $value = $value->format('Y-m-d');
                    }

                    $row[] = $value;
                }

                fputcsv($out, $row);
            }

            fclose($out);
        }, 'employees.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * Normalised listing/export filters (is_active read as a real boolean).
     *
     * @return array<string, mixed>
     */
    private function filters(IndexEmployeeRequest $request): array
    {
        $filters = [
            'search' => $request->validated('search'),
            'department_id' => $request->validated('department_id'),
            'designation_id' => $request->validated('designation_id'),
        ];

        if ($request->has('is_active')) {
            $filters['is_active'] = $request->boolean('is_active');
        }

        return $filters;
    }
}
