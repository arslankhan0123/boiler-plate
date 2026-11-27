<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Authorization\DefaultRoles;
use App\Support\ModuleAccess;
use App\Support\ModuleRegistry;
use Spatie\Permission\PermissionRegistrar;

/**
 * Builds the permission set INSIDE a tenant's own database from the module
 * registry — no seeder. Called when a tenant is created and whenever its
 * modules change. Safe to run repeatedly.
 *
 *  - creates permissions for core modules + the tenant's enabled modules
 *  - Tenant Admin gets all of them
 *  - permissions of disabled modules are removed (from every role)
 *  - API/resource permissions (countries.view, …) are never touched
 */
class TenantAccessService
{
    private const GUARD = 'api';

    /** Panel permissions from the previous (v1) layout, cleaned up once. */
    private const LEGACY_PREFIXES = ['accounting', 'admin', 'ecommerce', 'inventory', 'pos', 'purchase', 'sale', 'setup'];

    public function sync(Tenant $tenant, ?User $admin = null): void
    {
        $names = ModuleAccess::tenantPermissionNames($tenant);

        $tenant->run(function () use ($names, $admin): void {
            $registrar = app(PermissionRegistrar::class);
            $registrar->forgetCachedPermissions();

            // Direct queries → always this tenant's DB (never the cached registrar).
            $models = collect($names)->map(fn ($n) => Permission::query()->firstOrCreate(['name' => $n, 'guard_name' => self::GUARD]));

            $isPanelPerm = fn (string $p) => ModuleRegistry::moduleOfPermission($p) !== null || $this->isLegacy($p);

            $role = Role::query()->firstOrCreate(['name' => DefaultRoles::TENANT_ADMIN, 'guard_name' => self::GUARD]);
            $keep = $role->permissions()->get()->reject(fn ($p) => $isPanelPerm($p->name));
            $role->syncPermissions($keep->merge($models)->all());

            Permission::query()->where('guard_name', self::GUARD)->get()
                ->filter(fn ($p) => $isPanelPerm($p->name) && ! in_array($p->name, $names, true))
                ->each->delete();

            // Starter roles for enabled modules (e.g. HRM: Employee, HR Manager).
            // Only created/topped-up — a tenant's own edits to them are kept.
            foreach (config('hrm.default_roles', []) as $roleName => $perms) {
                $perms = array_values(array_intersect($perms, $names));
                if (! $perms) {
                    continue;
                }
                // Only ADD (never remove), so a tenant's own changes to these roles survive.
                $starter = Role::query()->firstOrCreate(['name' => $roleName, 'guard_name' => self::GUARD]);
                $starter->givePermissionTo($models->whereIn('name', $perms)->all());
            }

            if ($admin) {
                $admin->syncRoles([$role]);
            }

            $registrar->forgetCachedPermissions();
        });
    }

    private function isLegacy(string $permission): bool
    {
        [$prefix, $action] = array_pad(explode('.', $permission, 2), 2, '');

        return in_array($action, ['view', 'create', 'update', 'delete'], true)
            && in_array($prefix, self::LEGACY_PREFIXES, true)
            && ModuleRegistry::moduleOfPermission($permission) === null;
    }
}
