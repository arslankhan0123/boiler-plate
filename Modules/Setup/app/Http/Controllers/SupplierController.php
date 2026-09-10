<?php

declare(strict_types=1);

namespace Modules\Setup\Http\Controllers;

use App\Facades\ApiResponse;
use App\Support\Authorization\ColumnExposure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Modules\Setup\Http\Requests\DeleteSuppliersRequest;
use Modules\Setup\Http\Requests\ForceDeleteSuppliersRequest;
use Modules\Setup\Http\Requests\ImportSuppliersRequest;
use Modules\Setup\Http\Requests\IndexSupplierRequest;
use Modules\Setup\Http\Requests\StoreSupplierRequest;
use Modules\Setup\Http\Requests\UpdateSuppliersRequest;
use Modules\Setup\Models\Supplier;
use Modules\Setup\Services\SupplierService;
use Modules\Setup\Transformers\SupplierResource;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Tenant-defined suppliers (vendors). Custom records, NOT picked from a master —
 * create takes a flat object of fields; update/delete take arrays; import/export
 * are CSV (matched on company_name); force delete permanently removes soft-deleted
 * rows.
 */
class SupplierController extends Controller
{
    public function __construct(private readonly SupplierService $suppliers) {}

    #[OA\Get(
        path: '/api/v1/suppliers',
        summary: 'List the tenant\'s suppliers (paginated, filterable)',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Supplier'],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'group_name', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'country_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'currency_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'is_active', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20)),
        ],
    )]
    #[OA\Response(response: 200, description: 'Suppliers retrieved.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    public function index(IndexSupplierRequest $request): JsonResponse
    {
        $this->authorize('view', Supplier::class);
        SupplierResource::setColumnContext($request, ColumnExposure::Listing);

        $paginator = $this->suppliers->paginate($this->filters($request), (int) ($request->validated('per_page') ?? 20));

        return ApiResponse::successResponse(
            'Suppliers retrieved.',
            paginated($paginator, SupplierResource::collection($paginator->items())),
        );
    }

    #[OA\Get(
        path: '/api/v1/suppliers/{id}',
        summary: 'Show a single supplier (full record)',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Supplier'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
    )]
    #[OA\Response(response: 200, description: 'Supplier retrieved.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    public function show(Request $request, string $id): JsonResponse
    {
        $this->authorize('viewFull', Supplier::class);
        SupplierResource::setColumnContext($request, ColumnExposure::Detail);

        return ApiResponse::successResponse('Supplier retrieved.', new SupplierResource($this->suppliers->find($id)));
    }

    #[OA\Post(
        path: '/api/v1/suppliers',
        summary: 'Create a tenant-defined supplier',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Supplier'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['company_name'],
                properties: [
                    new OA\Property(property: 'company_name', type: 'string', example: 'Globex Supplies Ltd'),
                    new OA\Property(property: 'vat_number', type: 'string', example: 'GB123456789'),
                    new OA\Property(property: 'contact_person', type: 'string', example: 'Jane Doe'),
                    new OA\Property(property: 'email', type: 'string', example: 'sales@globex.example'),
                    new OA\Property(property: 'phone', type: 'string', example: '+1-213-555-0100'),
                    new OA\Property(property: 'whatsapp', type: 'string', example: '+1-213-555-0101'),
                    new OA\Property(property: 'website', type: 'string', example: 'https://globex.example'),
                    new OA\Property(property: 'group_name', type: 'string', example: 'Wholesale'),
                    new OA\Property(property: 'currency_id', type: 'integer', example: 1),
                    new OA\Property(property: 'country_id', type: 'integer', example: 1),
                    new OA\Property(property: 'state_id', type: 'integer', example: 1),
                    new OA\Property(property: 'default_language', type: 'string', example: 'en'),
                    new OA\Property(property: 'street', type: 'string', example: '123 Trade Ave'),
                    new OA\Property(property: 'city', type: 'string', example: 'Los Angeles'),
                    new OA\Property(property: 'zip', type: 'string', example: '90001'),
                    new OA\Property(property: 'po_box', type: 'string', example: 'PO-4567'),
                    new OA\Property(property: 'mailing_address', type: 'string', example: 'Attn: Accounts'),
                    new OA\Property(property: 'opening_balance', type: 'number', format: 'float', example: 0),
                    new OA\Property(property: 'data', type: 'object', example: ['payment_terms' => 'NET30']),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                    new OA\Property(property: 'is_default', type: 'boolean', example: false),
                    new OA\Property(property: 'sort_order', type: 'integer', example: 0),
                ],
            ),
        ),
    )]
    #[OA\Response(response: 200, description: 'Supplier created.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function store(StoreSupplierRequest $request): JsonResponse
    {
        $this->authorize('create', Supplier::class);
        SupplierResource::setColumnContext($request, ColumnExposure::Detail);

        $supplier = $this->suppliers->create($request->validated());

        return ApiResponse::successResponse('Supplier created.', new SupplierResource($supplier));
    }

    #[OA\Put(
        path: '/api/v1/suppliers',
        summary: 'Update suppliers (array of {id, ...editable fields})',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Supplier'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(type: 'array', items: new OA\Items(properties: [
                new OA\Property(property: 'id', type: 'integer', example: 1),
                new OA\Property(property: 'company_name', type: 'string', example: 'Globex Supplies Ltd'),
                new OA\Property(property: 'group_name', type: 'string', example: 'Wholesale'),
                new OA\Property(property: 'is_active', type: 'boolean', example: true),
                new OA\Property(property: 'is_default', type: 'boolean', example: false),
                new OA\Property(property: 'sort_order', type: 'integer', example: 0),
            ])),
        ),
    )]
    #[OA\Response(response: 200, description: 'Suppliers updated.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function update(UpdateSuppliersRequest $request): JsonResponse
    {
        $this->authorize('update', Supplier::class);
        SupplierResource::setColumnContext($request, ColumnExposure::Detail);

        $updated = $this->suppliers->updateMany($request->rows());

        return ApiResponse::successResponse('Suppliers updated.', SupplierResource::collection($updated));
    }

    #[OA\Delete(
        path: '/api/v1/suppliers',
        summary: 'Soft-delete suppliers (array of ids)',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Supplier'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'array', items: new OA\Items(type: 'integer'), example: [1, 2, 3])),
    )]
    #[OA\Response(response: 200, description: 'Suppliers deleted.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function destroy(DeleteSuppliersRequest $request): JsonResponse
    {
        $this->authorize('delete', Supplier::class);

        $deleted = $this->suppliers->deleteMany($request->ids());

        return ApiResponse::successResponse('Suppliers deleted.', ['deleted' => $deleted]);
    }

    #[OA\Delete(
        path: '/api/v1/suppliers/force',
        summary: 'Permanently delete soft-deleted suppliers (array of ids)',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Supplier'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'array', items: new OA\Items(type: 'integer'), example: [1, 2, 3])),
    )]
    #[OA\Response(response: 200, description: 'Suppliers permanently deleted.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function forceDestroy(ForceDeleteSuppliersRequest $request): JsonResponse
    {
        $this->authorize('hardDelete', Supplier::class);

        $deleted = $this->suppliers->forceDeleteMany($request->ids());

        return ApiResponse::successResponse('Suppliers permanently deleted.', ['deleted' => $deleted]);
    }

    #[OA\Post(
        path: '/api/v1/suppliers/import',
        summary: 'Import suppliers from a CSV file (upsert, matched on company_name)',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Supplier'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(mediaType: 'multipart/form-data', schema: new OA\Schema(properties: [new OA\Property(property: 'file', type: 'string', format: 'binary')])),
        ),
    )]
    #[OA\Response(response: 200, description: 'Import completed.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function import(ImportSuppliersRequest $request): JsonResponse
    {
        $this->authorize('create', Supplier::class);

        /** @var UploadedFile $file */
        $file = $request->file('file');

        return ApiResponse::successResponse('Import completed.', $this->suppliers->importCsv($file));
    }

    #[OA\Get(
        path: '/api/v1/suppliers/import-sample',
        summary: 'Download a sample CSV template for the suppliers import',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Supplier'],
    )]
    #[OA\Response(response: 200, description: 'Sample CSV file download.', content: new OA\MediaType(mediaType: 'text/csv'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    public function importSample(): BinaryFileResponse
    {
        $this->authorize('create', Supplier::class);

        $path = base_path('Modules/Setup/resources/samples/suppliers-import-sample.csv');

        return response()->download($path, 'suppliers-import-sample.csv', ['Content-Type' => 'text/csv']);
    }

    #[OA\Get(
        path: '/api/v1/suppliers/export',
        summary: 'Export the tenant\'s suppliers as CSV',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Supplier'],
        parameters: [
            new OA\Parameter(name: 'country_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'currency_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'is_active', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
        ],
    )]
    #[OA\Response(response: 200, description: 'CSV file download.', content: new OA\MediaType(mediaType: 'text/csv'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    public function export(IndexSupplierRequest $request): StreamedResponse
    {
        $this->authorize('view', Supplier::class);

        $suppliers = $this->suppliers->forExport($this->filters($request));

        return response()->streamDownload(function () use ($suppliers): void {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['company_name', 'vat_number', 'contact_person', 'email', 'phone', 'whatsapp', 'website', 'group_name', 'currency_id', 'country_id', 'state_id', 'default_language', 'street', 'city', 'zip', 'po_box', 'mailing_address', 'opening_balance', 'is_active', 'is_default', 'sort_order']);

            foreach ($suppliers as $supplier) {
                fputcsv($out, [
                    $supplier->company_name, $supplier->vat_number, $supplier->contact_person, $supplier->email, $supplier->phone,
                    $supplier->whatsapp, $supplier->website, $supplier->group_name, $supplier->currency_id, $supplier->country_id,
                    $supplier->state_id, $supplier->default_language, $supplier->street, $supplier->city, $supplier->zip,
                    $supplier->po_box, $supplier->mailing_address, $supplier->opening_balance,
                    $supplier->is_active ? '1' : '0', $supplier->is_default ? '1' : '0', $supplier->sort_order,
                ]);
            }

            fclose($out);
        }, 'suppliers.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * Normalised listing/export filters (is_active read as a real boolean).
     *
     * @return array<string, mixed>
     */
    private function filters(IndexSupplierRequest $request): array
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
