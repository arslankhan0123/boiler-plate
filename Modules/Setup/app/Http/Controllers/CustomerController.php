<?php

declare(strict_types=1);

namespace Modules\Setup\Http\Controllers;

use App\Facades\ApiResponse;
use App\Support\Authorization\ColumnExposure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Modules\Setup\Http\Requests\DeleteCustomersRequest;
use Modules\Setup\Http\Requests\ForceDeleteCustomersRequest;
use Modules\Setup\Http\Requests\ImportCustomersRequest;
use Modules\Setup\Http\Requests\IndexCustomerRequest;
use Modules\Setup\Http\Requests\StoreCustomerRequest;
use Modules\Setup\Http\Requests\UpdateCustomersRequest;
use Modules\Setup\Models\Customer;
use Modules\Setup\Services\CustomerService;
use Modules\Setup\Transformers\CustomerResource;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Tenant-defined customers. Custom records, NOT picked from a master — create takes
 * a flat object of fields; update/delete take arrays; import/export are CSV (matched
 * on company_name); force delete permanently removes soft-deleted rows.
 */
class CustomerController extends Controller
{
    public function __construct(private readonly CustomerService $customers) {}

    #[OA\Get(
        path: '/api/v1/customers',
        summary: 'List the tenant\'s customers (paginated, filterable)',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Customer'],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'group_name', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'country_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'currency_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'is_active', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20)),
        ],
    )]
    #[OA\Response(response: 200, description: 'Customers retrieved.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    public function index(IndexCustomerRequest $request): JsonResponse
    {
        $this->authorize('view', Customer::class);
        CustomerResource::setColumnContext($request, ColumnExposure::Listing);

        $paginator = $this->customers->paginate($this->filters($request), (int) ($request->validated('per_page') ?? 20));

        return ApiResponse::successResponse(
            'Customers retrieved.',
            paginated($paginator, CustomerResource::collection($paginator->items())),
        );
    }

    #[OA\Get(
        path: '/api/v1/customers/{id}',
        summary: 'Show a single customer (full record)',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Customer'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
    )]
    #[OA\Response(response: 200, description: 'Customer retrieved.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    public function show(Request $request, string $id): JsonResponse
    {
        $this->authorize('viewFull', Customer::class);
        CustomerResource::setColumnContext($request, ColumnExposure::Detail);

        return ApiResponse::successResponse('Customer retrieved.', new CustomerResource($this->customers->find($id)));
    }

    #[OA\Post(
        path: '/api/v1/customers',
        summary: 'Create a tenant-defined customer',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Customer'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['company_name'],
                properties: [
                    new OA\Property(property: 'company_name', type: 'string', example: 'Wayne Enterprises'),
                    new OA\Property(property: 'code', type: 'string', example: 'CUST-0001'),
                    new OA\Property(property: 'vat_number', type: 'string', example: 'US123456789'),
                    new OA\Property(property: 'email', type: 'string', example: 'ap@wayne.example'),
                    new OA\Property(property: 'phone', type: 'string', example: '+1-212-555-0100'),
                    new OA\Property(property: 'mobile', type: 'string', example: '+1-212-555-0188'),
                    new OA\Property(property: 'fax', type: 'string', example: '+1-212-555-0190'),
                    new OA\Property(property: 'whatsapp', type: 'string', example: '+1-212-555-0101'),
                    new OA\Property(property: 'website', type: 'string', example: 'https://wayne.example'),
                    new OA\Property(property: 'short_name', type: 'string', example: 'Wayne'),
                    new OA\Property(property: 'vendor_code', type: 'string', example: 'V-9988'),
                    new OA\Property(property: 'group_name', type: 'string', example: 'Key Accounts'),
                    new OA\Property(property: 'currency_id', type: 'integer', example: 1),
                    new OA\Property(property: 'country_id', type: 'integer', example: 1),
                    new OA\Property(property: 'state_id', type: 'integer', example: 1),
                    new OA\Property(property: 'default_language', type: 'string', example: 'en'),
                    new OA\Property(property: 'address', type: 'string', example: '1007 Mountain Drive'),
                    new OA\Property(property: 'city', type: 'string', example: 'Gotham'),
                    new OA\Property(property: 'zip', type: 'string', example: '10001'),
                    new OA\Property(property: 'location_url', type: 'string', example: 'https://maps.example/wayne'),
                    new OA\Property(property: 'opening_balance', type: 'number', format: 'float', example: 0),
                    new OA\Property(property: 'data', type: 'object', example: ['credit_limit' => 50000]),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                    new OA\Property(property: 'is_default', type: 'boolean', example: false),
                    new OA\Property(property: 'sort_order', type: 'integer', example: 0),
                ],
            ),
        ),
    )]
    #[OA\Response(response: 200, description: 'Customer created.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $this->authorize('create', Customer::class);
        CustomerResource::setColumnContext($request, ColumnExposure::Detail);

        $customer = $this->customers->create($request->validated());

        return ApiResponse::successResponse('Customer created.', new CustomerResource($customer));
    }

    #[OA\Put(
        path: '/api/v1/customers',
        summary: 'Update customers (array of {id, ...editable fields})',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Customer'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(type: 'array', items: new OA\Items(properties: [
                new OA\Property(property: 'id', type: 'integer', example: 1),
                new OA\Property(property: 'company_name', type: 'string', example: 'Wayne Enterprises'),
                new OA\Property(property: 'group_name', type: 'string', example: 'Key Accounts'),
                new OA\Property(property: 'is_active', type: 'boolean', example: true),
                new OA\Property(property: 'is_default', type: 'boolean', example: false),
                new OA\Property(property: 'sort_order', type: 'integer', example: 0),
            ])),
        ),
    )]
    #[OA\Response(response: 200, description: 'Customers updated.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function update(UpdateCustomersRequest $request): JsonResponse
    {
        $this->authorize('update', Customer::class);
        CustomerResource::setColumnContext($request, ColumnExposure::Detail);

        $updated = $this->customers->updateMany($request->rows());

        return ApiResponse::successResponse('Customers updated.', CustomerResource::collection($updated));
    }

    #[OA\Delete(
        path: '/api/v1/customers',
        summary: 'Soft-delete customers (array of ids)',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Customer'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'array', items: new OA\Items(type: 'integer'), example: [1, 2, 3])),
    )]
    #[OA\Response(response: 200, description: 'Customers deleted.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function destroy(DeleteCustomersRequest $request): JsonResponse
    {
        $this->authorize('delete', Customer::class);

        $deleted = $this->customers->deleteMany($request->ids());

        return ApiResponse::successResponse('Customers deleted.', ['deleted' => $deleted]);
    }

    #[OA\Delete(
        path: '/api/v1/customers/force',
        summary: 'Permanently delete soft-deleted customers (array of ids)',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Customer'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'array', items: new OA\Items(type: 'integer'), example: [1, 2, 3])),
    )]
    #[OA\Response(response: 200, description: 'Customers permanently deleted.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function forceDestroy(ForceDeleteCustomersRequest $request): JsonResponse
    {
        $this->authorize('hardDelete', Customer::class);

        $deleted = $this->customers->forceDeleteMany($request->ids());

        return ApiResponse::successResponse('Customers permanently deleted.', ['deleted' => $deleted]);
    }

    #[OA\Post(
        path: '/api/v1/customers/import',
        summary: 'Import customers from a CSV file (upsert, matched on company_name)',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Customer'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(mediaType: 'multipart/form-data', schema: new OA\Schema(properties: [new OA\Property(property: 'file', type: 'string', format: 'binary')])),
        ),
    )]
    #[OA\Response(response: 200, description: 'Import completed.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function import(ImportCustomersRequest $request): JsonResponse
    {
        $this->authorize('create', Customer::class);

        /** @var UploadedFile $file */
        $file = $request->file('file');

        return ApiResponse::successResponse('Import completed.', $this->customers->importCsv($file));
    }

    #[OA\Get(
        path: '/api/v1/customers/import-sample',
        summary: 'Download a sample CSV template for the customers import',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Customer'],
    )]
    #[OA\Response(response: 200, description: 'Sample CSV file download.', content: new OA\MediaType(mediaType: 'text/csv'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    public function importSample(): BinaryFileResponse
    {
        $this->authorize('create', Customer::class);

        $path = base_path('Modules/Setup/resources/samples/customers-import-sample.csv');

        return response()->download($path, 'customers-import-sample.csv', ['Content-Type' => 'text/csv']);
    }

    #[OA\Get(
        path: '/api/v1/customers/export',
        summary: 'Export the tenant\'s customers as CSV',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Customer'],
        parameters: [
            new OA\Parameter(name: 'country_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'currency_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'is_active', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
        ],
    )]
    #[OA\Response(response: 200, description: 'CSV file download.', content: new OA\MediaType(mediaType: 'text/csv'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    public function export(IndexCustomerRequest $request): StreamedResponse
    {
        $this->authorize('view', Customer::class);

        $customers = $this->customers->forExport($this->filters($request));

        return response()->streamDownload(function () use ($customers): void {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['company_name', 'code', 'vat_number', 'email', 'phone', 'mobile', 'fax', 'whatsapp', 'website', 'short_name', 'vendor_code', 'group_name', 'currency_id', 'country_id', 'state_id', 'default_language', 'address', 'city', 'zip', 'location_url', 'opening_balance', 'is_active', 'is_default', 'sort_order']);

            foreach ($customers as $customer) {
                fputcsv($out, [
                    $customer->company_name, $customer->code, $customer->vat_number, $customer->email, $customer->phone,
                    $customer->mobile, $customer->fax, $customer->whatsapp, $customer->website, $customer->short_name,
                    $customer->vendor_code, $customer->group_name, $customer->currency_id, $customer->country_id, $customer->state_id,
                    $customer->default_language, $customer->address, $customer->city, $customer->zip, $customer->location_url,
                    $customer->opening_balance, $customer->is_active ? '1' : '0', $customer->is_default ? '1' : '0', $customer->sort_order,
                ]);
            }

            fclose($out);
        }, 'customers.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * Normalised listing/export filters (is_active read as a real boolean).
     *
     * @return array<string, mixed>
     */
    private function filters(IndexCustomerRequest $request): array
    {
        $filters = [
            'search' => $request->validated('search'),
            'group_name' => $request->validated('group_name'),
            'country_id' => $request->validated('country_id'),
            'currency_id' => $request->validated('currency_id'),
        ];

        if ($request->has('is_active')) {
            $filters['is_active'] = $request->boolean('is_active');
        }

        return $filters;
    }
}
