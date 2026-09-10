<?php

declare(strict_types=1);

namespace App\Http\Resources\Concerns;

use App\Models\User;
use App\Services\ColumnVisibilityService;
use App\Support\Authorization\ColumnExposure;
use Illuminate\Http\Request;

/**
 * Drops columns the authenticated user is not allowed to see from a Resource's
 * output, based on the current tenant's column-visibility matrix.
 *
 * Columns not in the catalog (e.g. `id`) always pass through. The context
 * (listing vs detail) is read from the request attribute `column_context`,
 * which a controller sets before returning the resource; it defaults to
 * `detail`, the more permissive of the two read contexts.
 */
trait FiltersVisibleColumns
{
    /**
     * Request attribute a controller sets (to a ColumnExposure) to choose the
     * read context — Listing for index endpoints, Detail for show endpoints.
     */
    public const CONTEXT_ATTRIBUTE = 'column_context';

    /**
     * The catalog resource key this Resource maps to (e.g. 'users').
     */
    abstract protected function visibilityResource(): string;

    /**
     * Set the read context (listing vs detail) on the request, to be honoured by
     * this resource's column filtering. Call from a controller before returning
     * the resource — e.g. `CountryResource::setColumnContext($request, ColumnExposure::Listing)`.
     */
    public static function setColumnContext(Request $request, ColumnExposure $context): void
    {
        $request->attributes->set(self::CONTEXT_ATTRIBUTE, $context);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function filterVisibleColumns(array $data, Request $request): array
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return $data;
        }

        $hidden = app(ColumnVisibilityService::class)->hiddenColumns(
            $this->visibilityResource(),
            $user,
            self::columnContext($request),
        );

        if ($hidden === null || $hidden === []) {
            return $data;
        }

        return array_diff_key($data, array_flip($hidden));
    }

    protected static function columnContext(Request $request): ColumnExposure
    {
        $context = $request->attributes->get(self::CONTEXT_ATTRIBUTE);

        return $context instanceof ColumnExposure ? $context : ColumnExposure::Detail;
    }
}
