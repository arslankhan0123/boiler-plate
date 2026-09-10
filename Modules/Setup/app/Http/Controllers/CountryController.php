<?php

declare(strict_types=1);

namespace Modules\Setup\Http\Controllers;

use App\Facades\ApiResponse;
use App\Support\Authorization\ColumnExposure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Modules\Setup\Http\Requests\DeleteCountriesRequest;
use Modules\Setup\Http\Requests\ForceDeleteCountriesRequest;
use Modules\Setup\Http\Requests\ImportCountriesRequest;
use Modules\Setup\Http\Requests\IndexCountryRequest;
use Modules\Setup\Http\Requests\StoreCountriesRequest;
use Modules\Setup\Http\Requests\UpdateCountriesRequest;
use Modules\Setup\Models\Country;
use Modules\Setup\Services\CountryService;
use Modules\Setup\Transformers\CountryResource;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The tenant's selected countries (snapshot of the central master). Create picks
 * master `lookup_id`s; update/delete take arrays; import/export are CSV; force
 * delete permanently removes already soft-deleted rows.
 */
class CountryController extends Controller
{
    public function __construct(private readonly CountryService $countries) {}

    #[OA\Get(
        path: '/api/v1/countries',
        summary: 'List the tenant\'s countries (paginated, filterable)',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Country'],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'region', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'is_active', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20)),
        ],
    )]
    #[OA\Response(response: 200, description: 'Countries retrieved.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    public function index(IndexCountryRequest $request): JsonResponse
    {
        $this->authorize('view', Country::class);
        CountryResource::setColumnContext($request, ColumnExposure::Listing);

        $paginator = $this->countries->paginate($this->filters($request), (int) ($request->validated('per_page') ?? 20));

        return ApiResponse::successResponse(
            'Countries retrieved.',
            paginated($paginator, CountryResource::collection($paginator->items())),
        );
    }

    #[OA\Get(
        path: '/api/v1/countries/{id}',
        summary: 'Show a single country (full record)',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Country'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
    )]
    #[OA\Response(response: 200, description: 'Country retrieved.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    public function show(Request $request, string $id): JsonResponse
    {
        $this->authorize('viewFull', Country::class);
        CountryResource::setColumnContext($request, ColumnExposure::Detail);

        return ApiResponse::successResponse('Country retrieved.', new CountryResource($this->countries->find($id)));
    }

    #[OA\Post(
        path: '/api/v1/countries',
        summary: 'Add countries by picking master lookup ids',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Country'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'array',
                items: new OA\Items(properties: [new OA\Property(property: 'lookup_id', type: 'integer', example: 5)]),
            ),
        ),
    )]
    #[OA\Response(response: 200, description: 'Countries added.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function store(StoreCountriesRequest $request): JsonResponse
    {
        $this->authorize('create', Country::class);
        CountryResource::setColumnContext($request, ColumnExposure::Detail);

        $created = $this->countries->createFromLookups($request->lookupIds());

        return ApiResponse::successResponse('Countries added.', CountryResource::collection($created));
    }

    #[OA\Put(
        path: '/api/v1/countries',
        summary: 'Update the tenant-owned fields of countries (array of {id, ...})',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Country'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'array',
                items: new OA\Items(properties: [
                    new OA\Property(property: 'id', type: 'integer', example: 1),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                    new OA\Property(property: 'is_default', type: 'boolean', example: false),
                    new OA\Property(property: 'sort_order', type: 'integer', example: 0),
                ]),
            ),
        ),
    )]
    #[OA\Response(response: 200, description: 'Countries updated.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function update(UpdateCountriesRequest $request): JsonResponse
    {
        $this->authorize('update', Country::class);
        CountryResource::setColumnContext($request, ColumnExposure::Detail);

        $updated = $this->countries->updateMany($request->rows());

        return ApiResponse::successResponse('Countries updated.', CountryResource::collection($updated));
    }

    #[OA\Delete(
        path: '/api/v1/countries',
        summary: 'Soft-delete countries (array of ids)',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Country'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'array', items: new OA\Items(type: 'integer'), example: [1, 2, 3])),
    )]
    #[OA\Response(response: 200, description: 'Countries deleted.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function destroy(DeleteCountriesRequest $request): JsonResponse
    {
        $this->authorize('delete', Country::class);

        $deleted = $this->countries->deleteMany($request->ids());

        return ApiResponse::successResponse('Countries deleted.', ['deleted' => $deleted]);
    }

    #[OA\Delete(
        path: '/api/v1/countries/force',
        summary: 'Permanently delete soft-deleted countries (array of ids)',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Country'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'array', items: new OA\Items(type: 'integer'), example: [1, 2, 3])),
    )]
    #[OA\Response(response: 200, description: 'Countries permanently deleted.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function forceDestroy(ForceDeleteCountriesRequest $request): JsonResponse
    {
        $this->authorize('hardDelete', Country::class);

        $deleted = $this->countries->forceDeleteMany($request->ids());

        return ApiResponse::successResponse('Countries permanently deleted.', ['deleted' => $deleted]);
    }

    #[OA\Post(
        path: '/api/v1/countries/import',
        summary: 'Import countries from a CSV file (upsert, matched on iso2)',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Country'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(properties: [new OA\Property(property: 'file', type: 'string', format: 'binary')]),
            ),
        ),
    )]
    #[OA\Response(response: 200, description: 'Import completed.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function import(ImportCountriesRequest $request): JsonResponse
    {
        $this->authorize('create', Country::class);

        /** @var UploadedFile $file */
        $file = $request->file('file');

        return ApiResponse::successResponse('Import completed.', $this->countries->importCsv($file));
    }

    #[OA\Get(
        path: '/api/v1/countries/import-sample',
        summary: 'Download a sample CSV template for the countries import',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Country'],
    )]
    #[OA\Response(response: 200, description: 'Sample CSV file download.', content: new OA\MediaType(mediaType: 'text/csv'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    public function importSample(): BinaryFileResponse
    {
        $this->authorize('create', Country::class);

        $path = base_path('Modules/Setup/resources/samples/countries-import-sample.csv');

        return response()->download($path, 'countries-import-sample.csv', ['Content-Type' => 'text/csv']);
    }

    #[OA\Get(
        path: '/api/v1/countries/export',
        summary: 'Export the tenant\'s countries as CSV',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Country'],
        parameters: [new OA\Parameter(name: 'is_active', in: 'query', required: false, schema: new OA\Schema(type: 'boolean'))],
    )]
    #[OA\Response(response: 200, description: 'CSV file download.', content: new OA\MediaType(mediaType: 'text/csv'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    public function export(IndexCountryRequest $request): StreamedResponse
    {
        $this->authorize('view', Country::class);

        $countries = $this->countries->forExport($this->filters($request));

        return response()->streamDownload(function () use ($countries): void {
            $out = fopen('php://output', 'w');

            fputcsv($out, [
                'name', 'iso2', 'iso3', 'numeric_code', 'phone_code', 'capital', 'region',
                'currency_code', 'currency_symbol', 'locale', 'timezone', 'is_active', 'is_default', 'sort_order',
            ]);

            foreach ($countries as $country) {
                fputcsv($out, [
                    $country->name, $country->iso2, $country->iso3, $country->numeric_code, $country->phone_code,
                    $country->capital, $country->region, $country->currency_code, $country->currency_symbol,
                    $country->locale, $country->timezone, $country->is_active ? '1' : '0',
                    $country->is_default ? '1' : '0', $country->sort_order,
                ]);
            }

            fclose($out);
        }, 'countries.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * Normalised listing/export filters (is_active read as a real boolean).
     *
     * @return array<string, mixed>
     */
    private function filters(IndexCountryRequest $request): array
    {
        $filters = [
            'search' => $request->validated('search'),
            'region' => $request->validated('region'),
        ];

        if ($request->has('is_active')) {
            $filters['is_active'] = $request->boolean('is_active');
        }

        return $filters;
    }
}
