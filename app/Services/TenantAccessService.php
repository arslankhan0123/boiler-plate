<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use App\Support\Authorization\DefaultRoles;
use App\Support\ModuleAccess;
use App\Models\Permission;
use App\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Sets up permissions INSIDE a tenant's own database, straight from the
 * admin panel (no seeder needed). Safe to call again any time.
 *
 * Only the modules ticked on the Tenants form get permissions in the tenant
 * DB, plus Users & Roles so the tenant admin can manage their own team.
 */
class TenantAccessService
{
    private const GUARD = 'api';

    public function sync(Tenant $tenant, ?User $admin = null): void
    {
        $enabled = array_merge(
            array_keys(array_filter(ModuleAccess::all(), fn ($m) => ! empty($m['core']))),
            (array) ($tenant->modules ?? []),
        );

        $tenant->run(function () use ($enabled, $admin): void {
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            // Direct queries (not the cached registrar) so we always hit THIS tenant's DB.
            $names = [];
            $models = collect();
            foreach ($enabled as $module) {
                foreach (config('module_permissions.actions') as $action) {
                    $names[] = "{$module}.{$action}";
                    $models->push(Permission::query()->firstOrCreate(['name' => "{$module}.{$action}", 'guard_name' => self::GUARD]));
                }
            }

            // Tenant Admin = full access to exactly the enabled modules.
            $role = Role::query()->firstOrCreate(['name' => DefaultRoles::TENANT_ADMIN, 'guard_name' => self::GUARD]);
            // Only "<module>.<view|create|update|delete>" are panel permissions;
            // anything else (API permissions) is left untouched.
            $isPanelPerm = function (string $p): bool {
                [$mod, $act] = array_pad(explode('.', $p, 2), 2, null);

                return array_key_exists($mod, ModuleAccess::all())
                    && in_array($act, config('module_permissions.actions'), true);
            };
            $keep = $role->permissions()->get()->reject(fn ($p) => $isPanelPerm($p->name));
            $role->syncPermissions($keep->merge($models)->all());

            // Disabled modules: remove their permissions from every custom role too.
            Permission::query()->where('guard_name', self::GUARD)->get()
                ->filter(fn ($p) => $isPanelPerm($p->name) && ! in_array($p->name, $names, true))
                ->each(fn ($p) => $p->delete());

            if ($admin) {
                $admin->syncRoles([$role]);
            }

            app(PermissionRegistrar::class)->forgetCachedPermissions();
        });
    }
}
