<?php

declare(strict_types=1);

namespace Modules\RolesAndPermissions\Services;

use App\Models\AuditTrail;
use App\Models\Concerns\Auditable;
use App\Models\Role;
use App\Support\Audit;
use App\Support\Authorization\DefaultRoles;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\PermissionRegistrar;

/**
 * Orchestrates role management inside the current tenant database. Reuses
 * Spatie's role/permission primitives; every mutator flushes the permission
 * cache. The three default roles are protected from deletion and renaming.
 */
class RoleService
{
    private const GUARD = 'api';

    /**
     * @return Collection<int, Role>
     */
    public function list(): Collection
    {
        /** @var Collection<int, Role> $roles */
        $roles = Role::query()->with('permissions')->orderBy('name')->get();

        return $roles;
    }

    public function find(string $id): Role
    {
        /** @var Role $role */
        $role = Role::query()->with('permissions')->findOrFail($id);

        return $role;
    }

    /**
     * @param  list<string>  $permissions
     */
    public function create(string $name, array $permissions): Role
    {
        /** @var Role $role */
        $role = Role::query()->firstOrCreate(['name' => $name, 'guard_name' => self::GUARD]);
        $this->syncPermissionsAudited($role, $permissions);
        $this->flushCache();

        return $role->load('permissions');
    }

    /**
     * @param  list<string>|null  $permissions
     */
    public function update(string $id, ?string $name, ?array $permissions): Role
    {
        $role = $this->find($id);

        if ($name !== null && $name !== $role->name) {
            $this->assertNotProtected($role);
            $role->name = $name;
            $role->save();
        }

        if ($permissions !== null) {
            $this->syncPermissionsAudited($role, $permissions);
        }

        $this->flushCache();

        return $role->load('permissions');
    }

    public function delete(string $id): void
    {
        $role = $this->find($id);
        $this->assertNotProtected($role);

        $permissions = $role->permissions()->pluck('name')->sort()->values()->all();

        // Mass-delete (no model events): Spatie's `deleting` hook would detach
        // `$role->users()`, which resolves through the central-pinned User model
        // and so would query the pivot on the WRONG (central) connection. The
        // tenant-DB foreign keys (model_has_roles / role_has_permissions /
        // column_visibilities) cascade the pivots for us instead.
        Role::query()->whereKey($role->getKey())->delete();

        $this->flushCache();

        // The mass delete fires no model event — audit it explicitly.
        Audit::record(
            Role::class,
            (int) $role->getKey(),
            AuditTrail::EVENT_DELETED,
            ['name' => $role->name, 'permissions' => $permissions],
            null,
        );
    }

    /**
     * @param  list<string>  $permissions
     */
    public function syncPermissions(string $id, array $permissions): Role
    {
        $role = $this->find($id);
        $this->syncPermissionsAudited($role, $permissions);
        $this->flushCache();

        return $role->load('permissions');
    }

    /**
     * Sync a role's permissions and audit the before/after. Spatie's pivot writes
     * fire no Eloquent model events, so the {@see Auditable}
     * trait can't see them — record the change explicitly.
     *
     * @param  list<string>  $permissions
     */
    private function syncPermissionsAudited(Role $role, array $permissions): void
    {
        $before = $role->permissions()->pluck('name')->sort()->values()->all();
        $role->syncPermissions($permissions);
        $after = $role->permissions()->pluck('name')->sort()->values()->all();

        if ($before === $after) {
            return;
        }

        Audit::record(
            Role::class,
            (int) $role->getKey(),
            AuditTrail::EVENT_UPDATED,
            ['permissions' => $before],
            ['permissions' => $after],
        );
    }

    /**
     * Default roles (Tenant Admin / Manager / Staff) may not be deleted or renamed.
     *
     * @throws AuthorizationException
     */
    private function assertNotProtected(Role $role): void
    {
        if (DefaultRoles::isProtected($role->name)) {
            throw new AuthorizationException('Default roles cannot be renamed or deleted.');
        }
    }

    private function flushCache(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
