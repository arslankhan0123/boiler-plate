<?php

declare(strict_types=1);

namespace Modules\Setup\Http\Controllers;

use App\Facades\ApiResponse;
use App\Support\Authorization\ColumnExposure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Modules\Setup\Http\Requests\DeleteStatesRequest;
use Modules\Setup\Http\Requests\ForceDeleteStatesRequest;
use Modules\Setup\Http\Requests\ImportStatesRequest;
use Modules\Setup\Http\Requests\IndexStateRequest;
use Modules\Setup\Http\Requests\StoreStatesRequest;
use Modules\Setup\Http\Requests\UpdateStatesRequest;
use Modules\Setup\Models\State;
use Modules\Setup\Services\StateService;
use Modules\Setup\Transformers\StateResource;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The tenant's selected states (snapshot of the central master). Create picks
 * master `lookup_id`s (type=state); update/delete take arrays; import/export are
 * CSV (matched on external_id); force delete permanently removes soft-deleted rows.
 */
class StateController extends Controller
{
    public function __construct(private readonly StateService $states) {}

    #[OA\Get(
        path: '/api/v1/states',
        summary: 'List the tenant\'s states (paginated, filterable)',
        security: [['bearerAuth' => []]],
        tags: ['Setup / State'],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'country_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'is_active', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20)),
        ],
    )]
    #[OA\Response(response: 200, description: 'States retrieved.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    public function index(IndexStateRequest $request): JsonResponse
    {
        $this->authorize('view', State::class);
        StateResource::setColumnContext($request, ColumnExposure::Listing);

        $paginator = $this->states->paginate($this->filters($request), (int) ($request->validated('per_page') ?? 20));

        return ApiResponse::successResponse(
            'States retrieved.',
            paginated($paginator, StateResource::collection($paginator->items())),
        );
    }

    #[OA\Get(
        path: '/api/v1/states/{id}',
        summary: 'Show a single state (full record)',
        security: [['bearerAuth' => []]],
        tags: ['Setup / State'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
    )]
    #[OA\Response(response: 200, description: 'State retrieved.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    public function show(Request $request, string $id): JsonResponse
    {
        $this->authorize('viewFull', State::class);
        StateResource::setColumnContext($request, ColumnExposure::Detail);

        return ApiResponse::successResponse('State retrieved.', new StateResource($this->states->find($id)));
    }

    #[OA\Post(
        path: '/api/v1/states',
        summary: 'Add states by picking master lookup ids',
        security: [['bearerAuth' => []]],
        tags: ['Setup / State'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(type: 'array', items: new OA\Items(properties: [new OA\Property(property: 'lookup_id', type: 'integer', example: 3901)])),
        ),
    )]
    #[OA\Response(response: 200, description: 'States added.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function store(StoreStatesRequest $request): JsonResponse
    {
        $this->authorize('create', State::class);
        StateResource::setColumnContext($request, ColumnExposure::Detail);

        $created = $this->states->createFromLookups($request->lookupIds());

        return ApiResponse::successResponse('States added.', StateResource::collection($created));
    }

    #[OA\Put(
        path: '/api/v1/states',
        summary: 'Update the tenant-owned fields of states (array of {id, ...})',
        security: [['bearerAuth' => []]],
        tags: ['Setup / State'],
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
    #[OA\Response(response: 200, description: 'States updated.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function update(UpdateStatesRequest $request): JsonResponse
    {
        $this->authorize('update', State::class);
        StateResource::setColumnContext($request, ColumnExposure::Detail);

        $updated = $this->states->updateMany($request->rows());

        return ApiResponse::successResponse('States updated.', StateResource::collection($updated));
    }

    #[OA\Delete(
        path: '/api/v1/states',
        summary: 'Soft-delete states (array of ids)',
        security: [['bearerAuth' => []]],
        tags: ['Setup / State'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'array', items: new OA\Items(type: 'integer'), example: [1, 2, 3])),
    )]
    #[OA\Response(response: 200, description: 'States deleted.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function destroy(DeleteStatesRequest $request): JsonResponse
    {
        $this->authorize('delete', State::class);

        $deleted = $this->states->deleteMany($request->ids());

        return ApiResponse::successResponse('States deleted.', ['deleted' => $deleted]);
    }

    #[OA\Delete(
        path: '/api/v1/states/force',
        summary: 'Permanently delete soft-deleted states (array of ids)',
        security: [['bearerAuth' => []]],
        tags: ['Setup / State'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'array', items: new OA\Items(type: 'integer'), example: [1, 2, 3])),
    )]
    #[OA\Response(response: 200, description: 'States permanently deleted.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function forceDestroy(ForceDeleteStatesRequest $request): JsonResponse
    {
        $this->authorize('hardDelete', State::class);

        $deleted = $this->states->forceDeleteMany($request->ids());

        return ApiResponse::successResponse('States permanently deleted.', ['deleted' => $deleted]);
    }

    #[OA\Post(
        path: '/api/v1/states/import',
        summary: 'Import states from a CSV file (upsert, matched on external_id)',
        security: [['bearerAuth' => []]],
        tags: ['Setup / State'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(mediaType: 'multipart/form-data', schema: new OA\Schema(properties: [new OA\Property(property: 'file', type: 'string', format: 'binary')])),
        ),
    )]
    #[OA\Response(response: 200, description: 'Import completed.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function import(ImportStatesRequest $request): JsonResponse
    {
        $this->authorize('create', State::class);

        /** @var UploadedFile $file */
        $file = $request->file('file');

        return ApiResponse::successResponse('Import completed.', $this->states->importCsv($file));
    }

    #[OA\Get(
        path: '/api/v1/states/import-sample',
        summary: 'Download a sample CSV template for the states import',
        security: [['bearerAuth' => []]],
        tags: ['Setup / State'],
    )]
    #[OA\Response(response: 200, description: 'Sample CSV file download.', content: new OA\MediaType(mediaType: 'text/csv'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    public function importSample(): BinaryFileResponse
    {
        $this->authorize('create', State::class);

        $path = base_path('Modules/Setup/resources/samples/states-import-sample.csv');

        return response()->download($path, 'states-import-sample.csv', ['Content-Type' => 'text/csv']);
    }

    #[OA\Get(
        path: '/api/v1/states/export',
        summary: 'Export the tenant\'s states as CSV',
        security: [['bearerAuth' => []]],
        tags: ['Setup / State'],
        parameters: [new OA\Parameter(name: 'is_active', in: 'query', required: false, schema: new OA\Schema(type: 'boolean'))],
    )]
    #[OA\Response(response: 200, description: 'CSV file download.', content: new OA\MediaType(mediaType: 'text/csv'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    public function export(IndexStateRequest $request): StreamedResponse
    {
        $this->authorize('view', State::class);

        $states = $this->states->forExport($this->filters($request));

        return response()->streamDownload(function () use ($states): void {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['external_id', 'name', 'code', 'latitude', 'longitude', 'is_active', 'is_default', 'sort_order']);

            foreach ($states as $state) {
                fputcsv($out, [
                    $state->external_id, $state->name, $state->code,
                    $state->latitude, $state->longitude, $state->is_active ? '1' : '0', $state->is_default ? '1' : '0', $state->sort_order,
                ]);
            }

            fclose($out);
        }, 'states.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * Normalised listing/export filters (is_active read as a real boolean).
     *
     * @return array<string, mixed>
     */
    private function filters(IndexStateRequest $request): array
    {
        $filters = [
            'search' => $request->validated('search'),
            'country_id' => $request->validated('country_id'),
        ];

        if ($request->has('is_active')) {
            $filters['is_active'] = $request->boolean('is_active');
        }

        return $filters;
    }
}
