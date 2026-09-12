<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ColumnVisibility;
use App\Models\ResourceColumn;
use App\Support\Authorization\ColumnExposure;
use App\Support\Authorization\DefaultRoles;
use App\Support\Authorization\ResourceRegistry;
use Illuminate\Database\Seeder;
use Spatie\Permission\Contracts\Role as RoleContract;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds a SINGLE tenant database. Runs inside the tenant context (invoked by
 * stancl's SeedDatabase job on tenant creation, or `tenants:seed`). Creates the
 * action permissions, default roles, the column catalog, and the per-role
 * column-visibility matrix for the tenant.
 */
class TenantDatabaseSeeder extends Seeder
{
    private const GUARD = 'api';

    public function run(): void
    {
        // 1. Permissions (the actions per registered resource).
        foreach (ResourceRegistry::permissions() as $name) {
            Permission::findOrCreate($name, self::GUARD);
        }

        // 2. Column catalog (base exposure per column) — must exist before grants.
        $this->seedColumnCatalog();

        // 3. Default roles, their permission sets, and column-visibility grants —
        //    all from the single source of truth (DefaultRoles), so seeding and
        //    the `roles:sync-defaults` backfill never drift.
        foreach (DefaultRoles::names() as $name) {
            $role = Role::findOrCreate($name, self::GUARD);
            $role->syncPermissions(DefaultRoles::permissionsFor($name));
            $this->grantColumns($role, DefaultRoles::columnGrantsFor($name));
        }

        $this->call(RoleSeeder::class);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Mirror the ResourceRegistry column catalog into `resource_columns`.
     */
    private function seedColumnCatalog(): void
    {
        foreach (array_keys(ResourceRegistry::resources()) as $resource) {
            foreach (ResourceRegistry::columns($resource) as $column => $meta) {
                ResourceColumn::query()->updateOrCreate(
                    ['resource' => $resource, 'column' => $column],
                    [
                        'exposure' => $meta['exposure'],
                        'is_sensitive' => $meta['sensitive'],
                        'sort_order' => $meta['sort'],
                    ],
                );
            }
        }
    }

    /**
     * Grant a role a higher exposure on specific columns.
     *
     * @param  array<string, ColumnExposure>  $grants  keyed "{resource}.{column}"
     */
    private function grantColumns(RoleContract $role, array $grants): void
    {
        foreach ($grants as $key => $exposure) {
            [$resource, $column] = explode('.', $key, 2);

            $resourceColumn = ResourceColumn::query()
                ->where('resource', $resource)
                ->where('column', $column)
                ->first();

            if ($resourceColumn === null) {
                continue;
            }

            ColumnVisibility::query()->updateOrCreate(
                ['role_id' => (int) $role->id, 'resource_column_id' => $resourceColumn->id],
                ['exposure' => $exposure],
            );
        }
    }
}
