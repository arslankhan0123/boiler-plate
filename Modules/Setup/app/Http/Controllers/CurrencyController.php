<?php

declare(strict_types=1);

namespace Modules\Setup\Http\Controllers;

use App\Facades\ApiResponse;
use App\Support\Authorization\ColumnExposure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Modules\Setup\Http\Requests\DeleteCurrenciesRequest;
use Modules\Setup\Http\Requests\ForceDeleteCurrenciesRequest;
use Modules\Setup\Http\Requests\ImportCurrenciesRequest;
use Modules\Setup\Http\Requests\IndexCurrencyRequest;
use Modules\Setup\Http\Requests\StoreCurrenciesRequest;
use Modules\Setup\Http\Requests\UpdateCurrenciesRequest;
use Modules\Setup\Models\Currency;
use Modules\Setup\Services\CurrencyService;
use Modules\Setup\Transformers\CurrencyResource;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The tenant's selected currencies (snapshot of the central master). Create picks
 * master `lookup_id`s (type=currency); update/delete take arrays; import/export
 * are CSV (matched on code); force delete permanently removes soft-deleted rows.
 */
class CurrencyController extends Controller
{
    public function __construct(private readonly CurrencyService $currencies) {}

    #[OA\Get(
        path: '/api/v1/currencies',
        summary: 'List the tenant\'s currencies (paginated, filterable)',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Currency'],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'is_active', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20)),
        ],
    )]
    #[OA\Response(response: 200, description: 'Currencies retrieved.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    public function index(IndexCurrencyRequest $request): JsonResponse
    {
        $this->authorize('view', Currency::class);
        CurrencyResource::setColumnContext($request, ColumnExposure::Listing);

        $paginator = $this->currencies->paginate($this->filters($request), (int) ($request->validated('per_page') ?? 20));

        return ApiResponse::successResponse(
            'Currencies retrieved.',
            paginated($paginator, CurrencyResource::collection($paginator->items())),
        );
    }

    #[OA\Get(
        path: '/api/v1/currencies/{id}',
        summary: 'Show a single currency (full record)',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Currency'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
    )]
    #[OA\Response(response: 200, description: 'Currency retrieved.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    public function show(Request $request, string $id): JsonResponse
    {
        $this->authorize('viewFull', Currency::class);
        CurrencyResource::setColumnContext($request, ColumnExposure::Detail);

        return ApiResponse::successResponse('Currency retrieved.', new CurrencyResource($this->currencies->find($id)));
    }

    #[OA\Post(
        path: '/api/v1/currencies',
        summary: 'Add currencies by picking master lookup ids',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Currency'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(type: 'array', items: new OA\Items(properties: [new OA\Property(property: 'lookup_id', type: 'integer', example: 5)])),
        ),
    )]
    #[OA\Response(response: 200, description: 'Currencies added.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function store(StoreCurrenciesRequest $request): JsonResponse
    {
        $this->authorize('create', Currency::class);
        CurrencyResource::setColumnContext($request, ColumnExposure::Detail);

        $created = $this->currencies->createFromLookups($request->lookupIds());

        return ApiResponse::successResponse('Currencies added.', CurrencyResource::collection($created));
    }

    #[OA\Put(
        path: '/api/v1/currencies',
        summary: 'Update the tenant-owned fields of currencies (array of {id, ...})',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Currency'],
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
    #[OA\Response(response: 200, description: 'Currencies updated.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function update(UpdateCurrenciesRequest $request): JsonResponse
    {
        $this->authorize('update', Currency::class);
        CurrencyResource::setColumnContext($request, ColumnExposure::Detail);

        $updated = $this->currencies->updateMany($request->rows());

        return ApiResponse::successResponse('Currencies updated.', CurrencyResource::collection($updated));
    }

    #[OA\Delete(
        path: '/api/v1/currencies',
        summary: 'Soft-delete currencies (array of ids)',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Currency'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'array', items: new OA\Items(type: 'integer'), example: [1, 2, 3])),
    )]
    #[OA\Response(response: 200, description: 'Currencies deleted.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function destroy(DeleteCurrenciesRequest $request): JsonResponse
    {
        $this->authorize('delete', Currency::class);

        $deleted = $this->currencies->deleteMany($request->ids());

        return ApiResponse::successResponse('Currencies deleted.', ['deleted' => $deleted]);
    }

    #[OA\Delete(
        path: '/api/v1/currencies/force',
        summary: 'Permanently delete soft-deleted currencies (array of ids)',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Currency'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'array', items: new OA\Items(type: 'integer'), example: [1, 2, 3])),
    )]
    #[OA\Response(response: 200, description: 'Currencies permanently deleted.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function forceDestroy(ForceDeleteCurrenciesRequest $request): JsonResponse
    {
        $this->authorize('hardDelete', Currency::class);

        $deleted = $this->currencies->forceDeleteMany($request->ids());

        return ApiResponse::successResponse('Currencies permanently deleted.', ['deleted' => $deleted]);
    }

    #[OA\Post(
        path: '/api/v1/currencies/import',
        summary: 'Import currencies from a CSV file (upsert, matched on code)',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Currency'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(mediaType: 'multipart/form-data', schema: new OA\Schema(properties: [new OA\Property(property: 'file', type: 'string', format: 'binary')])),
        ),
    )]
    #[OA\Response(response: 200, description: 'Import completed.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function import(ImportCurrenciesRequest $request): JsonResponse
    {
        $this->authorize('create', Currency::class);

        /** @var UploadedFile $file */
        $file = $request->file('file');

        return ApiResponse::successResponse('Import completed.', $this->currencies->importCsv($file));
    }

    #[OA\Get(
        path: '/api/v1/currencies/import-sample',
        summary: 'Download a sample CSV template for the currencies import',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Currency'],
    )]
    #[OA\Response(response: 200, description: 'Sample CSV file download.', content: new OA\MediaType(mediaType: 'text/csv'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    public function importSample(): BinaryFileResponse
    {
        $this->authorize('create', Currency::class);

        $path = base_path('Modules/Setup/resources/samples/currencies-import-sample.csv');

        return response()->download($path, 'currencies-import-sample.csv', ['Content-Type' => 'text/csv']);
    }

    #[OA\Get(
        path: '/api/v1/currencies/export',
        summary: 'Export the tenant\'s currencies as CSV',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Currency'],
        parameters: [new OA\Parameter(name: 'is_active', in: 'query', required: false, schema: new OA\Schema(type: 'boolean'))],
    )]
    #[OA\Response(response: 200, description: 'CSV file download.', content: new OA\MediaType(mediaType: 'text/csv'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    public function export(IndexCurrencyRequest $request): StreamedResponse
    {
        $this->authorize('view', Currency::class);

        $currencies = $this->currencies->forExport($this->filters($request));

        return response()->streamDownload(function () use ($currencies): void {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['code', 'name', 'symbol', 'numeric_code', 'decimal_digits', 'is_active', 'is_default', 'sort_order']);

            foreach ($currencies as $currency) {
                fputcsv($out, [
                    $currency->code, $currency->name, $currency->symbol, $currency->numeric_code, $currency->decimal_digits,
                    $currency->is_active ? '1' : '0', $currency->is_default ? '1' : '0', $currency->sort_order,
                ]);
            }

            fclose($out);
        }, 'currencies.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * Normalised listing/export filters (is_active read as a real boolean).
     *
     * @return array<string, mixed>
     */
    private function filters(IndexCurrencyRequest $request): array
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
