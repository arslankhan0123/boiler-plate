<?php

declare(strict_types=1);

namespace Modules\RolesAndPermissions\Services;

use App\Models\AuditTrail;
use App\Models\Role;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Spatie\Permission\PermissionRegistrar;

/**
 * Manages a user's role assignments inside the current tenant database. The
 * target user must belong to the current tenant — users are central, so we
 * verify ownership and 404 otherwise (a tenant admin can only manage their own
 * tenant's users).
 */
class UserRoleService
{
    /**
     * @return Collection<int, Role>
     */
    public function rolesFor(string $userId): Collection
    {
        $user = $this->resolveTenantUser($userId);

        /** @var Collection<int, Role> $roles */
        $roles = $user->roles()->orderBy('name')->get();

        return $roles;
    }

    /**
     * @param  list<string>  $roleNames
     * @return Collection<int, Role>
     */
    public function sync(string $userId, array $roleNames): Collection
    {
        $user = $this->resolveTenantUser($userId);

        $before = $user->roles()->pluck('name')->sort()->values()->all();
        $user->syncRoles($roleNames);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        /** @var Collection<int, Role> $roles */
        $roles = $user->roles()->orderBy('name')->get();
        $after = $roles->pluck('name')->sort()->values()->all();

        // Spatie's pivot writes fire no Eloquent model events — audit explicitly.
        if ($before !== $after) {
            Audit::record(
                User::class,
                (int) $user->getKey(),
                AuditTrail::EVENT_UPDATED,
                ['roles' => $before],
                ['roles' => $after],
            );
        }

        return $roles;
    }

    /**
     * Resolve a user that belongs to the current tenant, or 404.
     */
    private function resolveTenantUser(string $userId): User
    {
        /** @var User $user */
        $user = User::query()->findOrFail($userId);

        $tenantKey = tenancy()->tenant?->getTenantKey();

        if ($tenantKey === null || (string) $user->tenant_id !== (string) $tenantKey) {
            throw new ModelNotFoundException;
        }

        return $user;
    }
}
