<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Tenant;
use App\Models\User;

/**
 * Single place that decides which modules exist for whom.
 *  - Central users (tenant_id = null): every module.
 *  - Tenant users: core modules + the modules enabled on their tenant.
 */
class ModuleAccess
{
    public static function all(): array
    {
        return config('module_permissions.modules', []);
    }

    /** Modules a tenant can be given (shown as checkboxes on the Tenants form). */
    public static function assignable(): array
    {
        return array_filter(self::all(), fn ($m) => empty($m['central']) && empty($m['core']));
    }

    /** Every permission name that may live inside a tenant database. */
    public static function tenantPermissionNames(): array
    {
        $names = [];
        foreach (self::all() as $key => $m) {
            if (! empty($m['central'])) {
                continue;
            }
            foreach (config('module_permissions.actions') as $action) {
                $names[] = "{$key}.{$action}";
            }
        }

        return $names;
    }

    /**
     * Is the module switched on for this user's context?
     * Returns null when $ability is not a module permission (leave it to Spatie).
     */
    public static function moduleEnabledFor(User $user, string $ability): ?bool
    {
        $module = explode('.', $ability, 2)[0];
        $config = self::all()[$module] ?? null;

        if ($config === null || ! str_contains($ability, '.')) {
            return null;
        }

        if ($user->tenant_id === null) {
            return true;
        }

        if (! empty($config['central'])) {
            return false;
        }

        if (! empty($config['core'])) {
            return true;
        }

        $tenant = tenant() ?? Tenant::query()->find($user->tenant_id);

        return in_array($module, (array) ($tenant?->modules ?? []), true);
    }
}
