<?php

declare(strict_types=1);

namespace App\Support\Authorization;

/**
 * Single source of truth for the per-tenant default roles: their names, the
 * canonical permission set each one holds, and their column-visibility grants.
 *
 * Consumed by both the TenantDatabaseSeeder (fresh tenants) and the
 * `roles:sync-defaults` command (backfill existing tenants) so seed and backfill
 * never drift. These three roles are PROTECTED — the management API blocks
 * deleting or renaming them.
 */
class DefaultRoles
{
    public const TENANT_ADMIN = 'Tenant Admin';

    public const MANAGER = 'Manager';

    public const STAFF = 'Staff';

    /**
     * Default role names that may not be deleted or renamed.
     *
     * @var list<string>
     */
    public const PROTECTED = [
        self::TENANT_ADMIN,
        self::MANAGER,
        self::STAFF,
    ];

    /**
     * The default roles in seed order.
     *
     * @return list<string>
     */
    public static function names(): array
    {
        return self::PROTECTED;
    }

    /**
     * Is the given role name a protected default?
     */
    public static function isProtected(string $role): bool
    {
        return in_array($role, self::PROTECTED, true);
    }

    /**
     * The canonical permission set for a default role. Tenant Admin always holds
     * every registered permission (so it auto-gains new ones as resources grow).
     *
     * @return list<string>
     */
    public static function permissionsFor(string $role): array
    {
        return match ($role) {
            self::TENANT_ADMIN => ResourceRegistry::permissions(),
            self::MANAGER => [
                'users.create',
                'users.view',
                'users.viewFull',
                'users.update',
                'lookups.view',
                'countries.view',
                'countries.viewFull',
                'countries.create',
                'countries.update',
                'countries.delete',
                'states.view',
                'states.viewFull',
                'states.create',
                'states.update',
                'states.delete',
                'cities.view',
                'cities.viewFull',
                'cities.create',
                'cities.update',
                'cities.delete',
                'areas.view',
                'areas.viewFull',
                'areas.create',
                'areas.update',
                'areas.delete',
                'currencies.view',
                'currencies.viewFull',
                'currencies.create',
                'currencies.update',
                'currencies.delete',
                'suppliers.view',
                'suppliers.viewFull',
                'suppliers.create',
                'suppliers.update',
                'suppliers.delete',
                'customers.view',
                'customers.viewFull',
                'customers.create',
                'customers.update',
                'customers.delete',
                'departments.view',
                'departments.viewFull',
                'departments.create',
                'departments.update',
                'departments.delete',
                'groups.view',
                'groups.viewFull',
                'groups.create',
                'groups.update',
                'groups.delete',
                'designations.view',
                'designations.viewFull',
                'designations.create',
                'designations.update',
                'designations.delete',
                'employees.view',
                'employees.viewFull',
                'employees.create',
                'employees.update',
                'employees.delete',
            ],
            self::STAFF => [
                'users.view',
                'lookups.view',
                'countries.view',
                'states.view',
                'cities.view',
                'areas.view',
                'currencies.view',
                'suppliers.view',
                'customers.view',
                'departments.view',
                'groups.view',
                'designations.view',
                'employees.view',
            ],
            default => [],
        };
    }

    /**
     * The canonical column-visibility grants for a default role, keyed
     * "{resource}.{column}". Grants are additive (they raise exposure above the
     * catalog base).
     *
     * @return array<string, ColumnExposure>
     */
    public static function columnGrantsFor(string $role): array
    {
        return match ($role) {
            self::TENANT_ADMIN, self::MANAGER => [
                'users.phone' => ColumnExposure::Detail,
            ],
            default => [],
        };
    }
}
