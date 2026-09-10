<?php

declare(strict_types=1);

namespace Modules\Setup\Http\Controllers;

use App\Facades\ApiResponse;
use App\Support\Authorization\ColumnExposure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Modules\Setup\Http\Requests\DeleteGroupsRequest;
use Modules\Setup\Http\Requests\ForceDeleteGroupsRequest;
use Modules\Setup\Http\Requests\ImportGroupsRequest;
use Modules\Setup\Http\Requests\IndexGroupRequest;
use Modules\Setup\Http\Requests\StoreGroupRequest;
use Modules\Setup\Http\Requests\UpdateGroupsRequest;
use Modules\Setup\Models\Group;
use Modules\Setup\Services\GroupService;
use Modules\Setup\Transformers\GroupResource;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Tenant-defined groups — a hierarchical classification list (nestable via
 * parent_id). Create takes a flat object; update/delete take arrays; import/export
 * are CSV (matched on name); force delete permanently removes soft-deleted rows.
 */
class GroupController extends Controller
{
    public function __construct(private readonly GroupService $groups) {}

    #[OA\Get(
        path: '/api/v1/groups',
        summary: 'List the tenant\'s groups (paginated, filterable)',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Group'],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'parent_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'is_active', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20)),
        ],
    )]
    #[OA\Response(response: 200, description: 'Groups retrieved.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    public function index(IndexGroupRequest $request): JsonResponse
    {
        $this->authorize('view', Group::class);
        GroupResource::setColumnContext($request, ColumnExposure::Listing);

        $paginator = $this->groups->paginate($this->filters($request), (int) ($request->validated('per_page') ?? 20));

        return ApiResponse::successResponse(
            'Groups retrieved.',
            paginated($paginator, GroupResource::collection($paginator->items())),
        );
    }

    #[OA\Get(
        path: '/api/v1/groups/{id}',
        summary: 'Show a single group (full record)',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Group'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
    )]
    #[OA\Response(response: 200, description: 'Group retrieved.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    public function show(Request $request, string $id): JsonResponse
    {
        $this->authorize('viewFull', Group::class);
        GroupResource::setColumnContext($request, ColumnExposure::Detail);

        return ApiResponse::successResponse('Group retrieved.', new GroupResource($this->groups->find($id)));
    }

    #[OA\Post(
        path: '/api/v1/groups',
        summary: 'Create a tenant-defined group',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Group'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Electronics'),
                    new OA\Property(property: 'code', type: 'string', example: 'GRP-ELE'),
                    new OA\Property(property: 'description', type: 'string', example: 'Top-level electronics group'),
                    new OA\Property(property: 'color', type: 'string', example: '#1E88E5'),
                    new OA\Property(property: 'parent_id', type: 'integer', nullable: true, example: null),
                    new OA\Property(property: 'data', type: 'object', example: ['icon' => 'chip']),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                    new OA\Property(property: 'is_default', type: 'boolean', example: false),
                    new OA\Property(property: 'sort_order', type: 'integer', example: 0),
                ],
            ),
        ),
    )]
    #[OA\Response(response: 200, description: 'Group created.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function store(StoreGroupRequest $request): JsonResponse
    {
        $this->authorize('create', Group::class);
        GroupResource::setColumnContext($request, ColumnExposure::Detail);

        $group = $this->groups->create($request->validated());

        return ApiResponse::successResponse('Group created.', new GroupResource($group));
    }

    #[OA\Put(
        path: '/api/v1/groups',
        summary: 'Update groups (array of {id, ...editable fields})',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Group'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(type: 'array', items: new OA\Items(properties: [
                new OA\Property(property: 'id', type: 'integer', example: 1),
                new OA\Property(property: 'name', type: 'string', example: 'Electronics'),
                new OA\Property(property: 'parent_id', type: 'integer', nullable: true, example: 2),
                new OA\Property(property: 'is_active', type: 'boolean', example: true),
                new OA\Property(property: 'is_default', type: 'boolean', example: false),
                new OA\Property(property: 'sort_order', type: 'integer', example: 0),
            ])),
        ),
    )]
    #[OA\Response(response: 200, description: 'Groups updated.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function update(UpdateGroupsRequest $request): JsonResponse
    {
        $this->authorize('update', Group::class);
        GroupResource::setColumnContext($request, ColumnExposure::Detail);

        $updated = $this->groups->updateMany($request->rows());

        return ApiResponse::successResponse('Groups updated.', GroupResource::collection($updated));
    }

    #[OA\Delete(
        path: '/api/v1/groups',
        summary: 'Soft-delete groups (array of ids)',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Group'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'array', items: new OA\Items(type: 'integer'), example: [1, 2, 3])),
    )]
    #[OA\Response(response: 200, description: 'Groups deleted.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function destroy(DeleteGroupsRequest $request): JsonResponse
    {
        $this->authorize('delete', Group::class);

        $deleted = $this->groups->deleteMany($request->ids());

        return ApiResponse::successResponse('Groups deleted.', ['deleted' => $deleted]);
    }

    #[OA\Delete(
        path: '/api/v1/groups/force',
        summary: 'Permanently delete soft-deleted groups (array of ids)',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Group'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'array', items: new OA\Items(type: 'integer'), example: [1, 2, 3])),
    )]
    #[OA\Response(response: 200, description: 'Groups permanently deleted.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function forceDestroy(ForceDeleteGroupsRequest $request): JsonResponse
    {
        $this->authorize('hardDelete', Group::class);

        $deleted = $this->groups->forceDeleteMany($request->ids());

        return ApiResponse::successResponse('Groups permanently deleted.', ['deleted' => $deleted]);
    }

    #[OA\Post(
        path: '/api/v1/groups/import',
        summary: 'Import groups from a CSV file (upsert, matched on name)',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Group'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(mediaType: 'multipart/form-data', schema: new OA\Schema(properties: [new OA\Property(property: 'file', type: 'string', format: 'binary')])),
        ),
    )]
    #[OA\Response(response: 200, description: 'Import completed.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccess'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    public function import(ImportGroupsRequest $request): JsonResponse
    {
        $this->authorize('create', Group::class);

        /** @var UploadedFile $file */
        $file = $request->file('file');

        return ApiResponse::successResponse('Import completed.', $this->groups->importCsv($file));
    }

    #[OA\Get(
        path: '/api/v1/groups/import-sample',
        summary: 'Download a sample CSV template for the groups import',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Group'],
    )]
    #[OA\Response(response: 200, description: 'Sample CSV file download.', content: new OA\MediaType(mediaType: 'text/csv'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    public function importSample(): BinaryFileResponse
    {
        $this->authorize('create', Group::class);

        $path = base_path('Modules/Setup/resources/samples/groups-import-sample.csv');

        return response()->download($path, 'groups-import-sample.csv', ['Content-Type' => 'text/csv']);
    }

    #[OA\Get(
        path: '/api/v1/groups/export',
        summary: 'Export the tenant\'s groups as CSV',
        security: [['bearerAuth' => []]],
        tags: ['Setup / Group'],
        parameters: [
            new OA\Parameter(name: 'parent_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'is_active', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
        ],
    )]
    #[OA\Response(response: 200, description: 'CSV file download.', content: new OA\MediaType(mediaType: 'text/csv'))]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    public function export(IndexGroupRequest $request): StreamedResponse
    {
        $this->authorize('view', Group::class);

        $groups = $this->groups->forExport($this->filters($request));

        return response()->streamDownload(function () use ($groups): void {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['name', 'code', 'description', 'color', 'parent_id', 'is_active', 'is_default', 'sort_order']);

            foreach ($groups as $group) {
                fputcsv($out, [
                    $group->name, $group->code, $group->description, $group->color, $group->parent_id,
                    $group->is_active ? '1' : '0', $group->is_default ? '1' : '0', $group->sort_order,
                ]);
            }

            fclose($out);
        }, 'groups.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * Normalised listing/export filters (is_active read as a real boolean).
     *
     * @return array<string, mixed>
     */
    private function filters(IndexGroupRequest $request): array
    {
        $filters = [
            'search' => $request->validated('search'),
            'parent_id' => $request->validated('parent_id'),
        ];

        if ($request->has('is_active')) {
            $filters['is_active'] = $request->boolean('is_active');
        }

        return $filters;
    }
}
