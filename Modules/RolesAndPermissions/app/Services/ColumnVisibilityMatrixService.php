<?php

declare(strict_types=1);

namespace Modules\RolesAndPermissions\Services;

use App\Models\ColumnVisibility;
use App\Models\ResourceColumn;
use App\Models\Role;
use App\Services\ColumnVisibilityService;
use App\Support\Authorization\ColumnExposure;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\PermissionRegistrar;

/**
 * Reads and edits the per-role column-visibility matrix inside the current
 * tenant database. Grants are additive (raise-only): a grant may only raise a
 * column above its catalog base, and a grant equal to the base is removed
 * (kept minimal). After a write the central ColumnVisibilityService memo is
 * flushed so any resource serialised later in the request reflects the change.
 */
class ColumnVisibilityMatrixService
{
    public function __construct(private readonly ColumnVisibilityService $visibility) {}

    /**
     * The full column catalog (the columns a role's grants can target).
     *
     * @return list<array{resource: string, column: string, label: string|null, base_exposure: string, is_sensitive: bool, sort_order: int}>
     */
    public function catalog(): array
    {
        $catalog = [];

        foreach ($this->catalogColumns() as $column) {
            $catalog[] = [
                'resource' => $column->resource,
                'column' => $column->column,
                'label' => $column->label,
                'base_exposure' => $column->exposure->value,
                'is_sensitive' => $column->is_sensitive,
                'sort_order' => $column->sort_order,
            ];
        }

        return $catalog;
    }

    /**
     * The matrix for one role: every catalogued column with its base exposure,
     * the role's granted exposure (if any), and the resulting effective exposure.
     *
     * @return list<array{resource: string, column: string, base_exposure: string, granted_exposure: string|null, effective_exposure: string, is_sensitive: bool, sort_order: int}>
     */
    public function matrixFor(string $roleId): array
    {
        $role = $this->resolveRole($roleId);

        $grants = ColumnVisibility::query()
            ->where('role_id', $role->getKey())
            ->get()
            ->keyBy('resource_column_id');

        $matrix = [];

        foreach ($this->catalogColumns() as $column) {
            /** @var ColumnVisibility|null $grant */
            $grant = $grants->get($column->id);
            $granted = $grant?->exposure;
            $effective = $granted !== null ? $column->exposure->max($granted) : $column->exposure;

            $matrix[] = [
                'resource' => $column->resource,
                'column' => $column->column,
                'base_exposure' => $column->exposure->value,
                'granted_exposure' => $granted?->value,
                'effective_exposure' => $effective->value,
                'is_sensitive' => $column->is_sensitive,
                'sort_order' => $column->sort_order,
            ];
        }

        return $matrix;
    }

    /**
     * Apply a set of grants to a role. Each grant raises a column's exposure;
     * a grant equal to the catalog base removes the row. (Raise-only and column
     * existence are enforced by the form request.)
     *
     * @param  list<array{resource: string, column: string, exposure: ColumnExposure}>  $grants
     */
    public function updateMatrix(string $roleId, array $grants): void
    {
        $role = $this->resolveRole($roleId);

        foreach ($grants as $grant) {
            $column = ResourceColumn::query()
                ->where('resource', $grant['resource'])
                ->where('column', $grant['column'])
                ->first();

            if ($column === null) {
                continue;
            }

            // A grant equal to the base exposure is a no-op grant — drop the row.
            if ($grant['exposure'] === $column->exposure) {
                // Instance delete (not a mass delete) so the `deleted` model event
                // fires and the removal is audited.
                ColumnVisibility::query()
                    ->where('role_id', $role->getKey())
                    ->where('resource_column_id', $column->id)
                    ->first()
                    ?->delete();

                continue;
            }

            ColumnVisibility::query()->updateOrCreate(
                ['role_id' => $role->getKey(), 'resource_column_id' => $column->id],
                ['exposure' => $grant['exposure']],
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->visibility->flush();
    }

    /**
     * @return Collection<int, ResourceColumn>
     */
    private function catalogColumns(): Collection
    {
        return ResourceColumn::query()
            ->orderBy('resource')
            ->orderBy('sort_order')
            ->get();
    }

    private function resolveRole(string $roleId): Role
    {
        /** @var Role $role */
        $role = Role::query()->findOrFail($roleId);

        return $role;
    }
}
