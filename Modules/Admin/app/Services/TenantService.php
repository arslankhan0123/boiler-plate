<?php

declare(strict_types=1);

namespace Modules\Admin\Services;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Authorization\DefaultRoles;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Platform-level tenant onboarding. Creating a Tenant fires the TenantCreated
 * job pipeline (CreateDatabase -> MigrateDatabase -> SeedDatabase), so the
 * tenant database is provisioned, migrated and seeded (roles/permissions) before
 * `createTenant()` returns. Tenant users are CENTRAL records linked by
 * `tenant_id`; their roles are assigned inside the tenant database.
 */
class TenantService
{
    /**
     * @return Collection<int, Tenant>
     */
    public function list(): Collection
    {
        /** @var Collection<int, Tenant> $tenants */
        $tenants = Tenant::query()->orderBy('id')->get();

        return $tenants;
    }

    /**
     * Create a tenant; its database is provisioned synchronously by the pipeline.
     */
    public function createTenant(string $name, bool $isActive): Tenant
    {
        return Tenant::create(['name' => $name, 'is_active' => $isActive]);
    }

    /**
     * Create a central user bound to the tenant and assign roles inside the
     * tenant database. Roles are validated against the tenant first so an invalid
     * role never leaves an orphaned user behind.
     *
     * @param  list<string>  $roles
     * @return array{user: User, roles: list<string>}
     */
    public function addUser(Tenant $tenant, string $name, string $email, string $password, ?string $phone, array $roles): array
    {
        $roles = $roles === [] ? [DefaultRoles::TENANT_ADMIN] : $roles;

        $tenant->run(function () use ($roles): void {
            $known = Role::query()->whereIn('name', $roles)->pluck('name')->all();
            $missing = array_values(array_diff($roles, $known));

            if ($missing !== []) {
                throw ValidationException::withMessages([
                    'roles' => ['Unknown role(s) for this tenant: '.implode(', ', $missing)],
                ]);
            }
        });

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password' => $password,
            'email_verified_at' => now(),
            'tenant_id' => $tenant->id,
            'is_platform_admin' => false,
        ]);

        $tenant->run(fn () => $user->syncRoles($roles));

        return ['user' => $user, 'roles' => $roles];
    }
}
