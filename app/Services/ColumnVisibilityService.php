<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ColumnVisibility;
use App\Models\ResourceColumn;
use App\Models\User;
use App\Support\Authorization\ColumnExposure;
use App\Support\Authorization\ResourceRegistry;
use Illuminate\Database\Eloquent\Collection;

/**
 * Resolves which catalogued columns a user may see, from the CURRENT tenant DB.
 *
 * A column's effective exposure is the most-visible of its catalog base exposure
 * and any per-role grants the user holds (grants are additive). Results are
 * memoised for the lifetime of the request (the service is a singleton), keyed
 * by tenant + resource + user.
 *
 * Returns `null` to mean "no restriction — show everything": there is no tenant
 * context (e.g. a platform admin operating centrally), or the user is a platform
 * super-admin who bypasses all authorization.
 */
class ColumnVisibilityService
{
    /**
     * Request-scoped memo: "{tenant}|{resource}|{userId}" => effective exposures.
     *
     * @var array<string, array<string, ColumnExposure>>
     */
    private array $cache = [];

    /**
     * Drop the request-scoped memo. Call after writing to the visibility matrix
     * so a resource serialised later in the same request reflects the change.
     */
    public function flush(): void
    {
        $this->cache = [];
    }

    /**
     * Effective exposure for every catalogued column of the resource.
     *
     * @return array<string, ColumnExposure>|null
     */
    public function effectiveExposures(string $resource, User $user): ?array
    {
        if (! tenancy()->initialized || $user->is_platform_admin) {
            return null;
        }

        $tenantKey = (string) tenancy()->tenant?->getTenantKey();
        $cacheKey = $tenantKey.'|'.$resource.'|'.$user->getKey();

        if (array_key_exists($cacheKey, $this->cache)) {
            return $this->cache[$cacheKey];
        }

        /** @var Collection<int, ResourceColumn> $catalog */
        $catalog = ResourceColumn::query()->where('resource', $resource)->get();

        // Base exposure per column, straight from the catalog.
        $effective = [];
        foreach ($catalog as $resourceColumn) {
            $effective[$resourceColumn->column] = $resourceColumn->exposure;
        }

        // Raise exposure by the user's role grants (keep the most visible).
        $roleIds = $user->roles()->pluck('id');

        if ($catalog->isNotEmpty() && $roleIds->isNotEmpty()) {
            $grants = ColumnVisibility::query()
                ->whereIn('role_id', $roleIds)
                ->whereIn('resource_column_id', $catalog->pluck('id'))
                ->with('resourceColumn:id,column')
                ->get();

            foreach ($grants as $grant) {
                $column = $grant->resourceColumn?->column;
                if ($column === null) {
                    continue;
                }
                $effective[$column] = $effective[$column]->max($grant->exposure);
            }
        }

        return $this->cache[$cacheKey] = $effective;
    }

    /**
     * Catalogued columns that must be hidden from the user in the given context.
     * `null` means no restriction applies (caller should show everything).
     *
     * @return list<string>|null
     */
    public function hiddenColumns(string $resource, User $user, ColumnExposure $context): ?array
    {
        $effective = $this->effectiveExposures($resource, $user);

        if ($effective === null) {
            return null;
        }

        $hidden = [];
        foreach ($effective as $column => $exposure) {
            if (! $exposure->visibleIn($context)) {
                $hidden[] = $column;
            }
        }

        return $hidden;
    }

    /**
     * Columns of a resource the user MAY write: the resource's editable columns
     * (`editable !== false`) intersected with the columns the user can see at the
     * Detail context. A user who cannot even see a column may not write it.
     *
     * `null` means no column-level write restriction applies (caller may persist
     * the whole validated payload): either the resource has no column catalog, or
     * there is no tenant context / the user is a platform super-admin.
     *
     * @return list<string>|null
     */
    public function writableColumns(string $resource, User $user): ?array
    {
        $editable = ResourceRegistry::editableColumns($resource);

        if ($editable === null) {
            return null;
        }

        $effective = $this->effectiveExposures($resource, $user);

        if ($effective === null) {
            return $editable;
        }

        $writable = [];
        foreach ($editable as $column) {
            $exposure = $effective[$column] ?? null;

            if ($exposure === null || $exposure->visibleIn(ColumnExposure::Detail)) {
                $writable[] = $column;
            }
        }

        return $writable;
    }
}
