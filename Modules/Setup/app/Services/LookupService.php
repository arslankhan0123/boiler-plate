<?php

declare(strict_types=1);

namespace Modules\Setup\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Setup\Models\Lookup;

/**
 * Read access to the CENTRAL `lookups` master. Tenants search this list to pick
 * the entries (countries, …) they want to add to their own data.
 */
class LookupService
{
    /**
     * Paginated, filterable search within a single lookup `type`.
     *
     * @return LengthAwarePaginator<int, Lookup>
     */
    public function search(string $type, ?string $search, ?bool $isActive, int $perPage): LengthAwarePaginator
    {
        return Lookup::query()
            ->where('type', $type)
            // Eager-load the parent chain (state→country) so the resource can render
            // "City, State, Country" with no N+1 — 2 extra queries total per page.
            ->with('parent.parent')
            ->when($isActive !== null, fn ($query) => $query->where('is_active', $isActive))
            ->when(
                $search !== null && $search !== '',
                fn ($query) => $query->where(function ($query) use ($search): void {
                    // Case-insensitive (Postgres LIKE is case-sensitive); portable LOWER().
                    $term = '%'.mb_strtolower((string) $search).'%';
                    $query->whereRaw('LOWER(name) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(iso2) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(iso3) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(code) LIKE ?', [$term]);
                }),
            )
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage);
    }
}
