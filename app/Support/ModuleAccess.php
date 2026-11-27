<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Tenant;
use App\Models\User;

/**
 * Check #1 of 2 — "Is this module enabled for the user's tenant?"
 * (Check #2, "does the user have the permission?", is Spatie / Gate.)
 *
 *  Central user (Master Admin, tenant_id = null): platform + core modules only.
 *  Tenant user: core modules + business modules enabled on the tenant
 *               (and the feature not switched off for that tenant).
 */
class ModuleAccess
{
    private static array $tenantCache = [];

    public static function tenantOf(User $user): ?Tenant
    {
        if ($user->tenant_id === null) {
            return null;
        }

        $current = function_exists('tenant') ? tenant() : null;
        if ($current && (string) $current->getTenantKey() === (string) $user->tenant_id) {
            return $current;
        }

        return self::$tenantCache[$user->tenant_id] ??= Tenant::query()->find($user->tenant_id);
    }

    /** Is the module switched on in this user's context? */
    public static function moduleAvailable(User $user, string $code): bool
    {
        $m = ModuleRegistry::get($code);
        if (! $m) {
            return false;
        }

        if ($user->tenant_id === null) {
            return in_array($m['scope'], ['platform', 'core'], true) && empty($m['tenant_only']);
        }

        return match ($m['scope']) {
            'core' => true,
            'tenant' => (bool) self::tenantOf($user)?->hasModule($code),
            default => false,
        };
    }

    /** Is a feature (submodule) available — module on, feature not switched off, its own module needs met? */
    public static function featureAvailable(User $user, string $module, string $feature): bool
    {
        $def = ModuleRegistry::get($module)['features'][$feature] ?? null;
        if (! $def || ! self::moduleAvailable($user, $module)) {
            return false;
        }

        $tenant = self::tenantOf($user);
        if ($tenant && ! $tenant->featureEnabled($module, $feature)) {
            return false;
        }

        if (! empty($def['employee_only']) && ! \App\Support\Hrm\Hrm::me()) {
            return false; // self-service needs a linked employee profile
        }
        if (! empty($def['team_only']) && ! \App\Support\Hrm\Hrm::team()->count()) {
            return false; // only managers (people with direct reports)
        }

        foreach ((array) ($def['needs_module'] ?? []) as $needed) {
            if (self::moduleAvailable($user, $needed)) {
                return true;
            }
        }

        return empty($def['needs_module']);
    }

    /** Both checks: module available AND user holds the feature's permission. */
    public static function canUseFeature(User $user, string $module, string $feature): bool
    {
        $perm = ModuleRegistry::get($module)['features'][$feature]['permission'] ?? null;

        return self::featureAvailable($user, $module, $feature) && $perm && $user->can($perm);
    }

    /**
     * For a permission like "pos.refund": false when its module is not
     * available to this user, null when it is not a module permission.
     */
    public static function moduleEnabledFor(User $user, string $ability): ?bool
    {
        $module = ModuleRegistry::moduleOfPermission($ability);

        return $module === null ? null : self::moduleAvailable($user, $module);
    }

    /**
     * Modules to show this user (sidebar / dashboard), each with the features
     * the user can actually open.
     *
     * @return array<string, array> code => definition + ['visible_features' => [...]]
     */
    public static function visibleModules(User $user): array
    {
        $out = [];
        foreach (ModuleRegistry::all() as $code => $m) {
            if (! self::moduleAvailable($user, $code)) {
                continue;
            }

            $features = array_filter(
                $m['features'],
                fn ($f, $key) => self::canUseFeature($user, $code, $key),
                ARRAY_FILTER_USE_BOTH,
            );

            $canView = $user->can($m['view_permission']);
            if (! $canView && ! $features) {
                continue;
            }

            $m['visible_features'] = $features;
            $out[$code] = $m;
        }

        return $out;
    }

    /** Permission names that may exist inside a tenant DB (core + enabled business modules). */
    public static function tenantPermissionNames(Tenant $tenant): array
    {
        $names = [];
        foreach (ModuleRegistry::all() as $code => $m) {
            if ($m['scope'] === 'core' || ($m['scope'] === 'tenant' && $tenant->hasModule($code))) {
                array_push($names, ...array_keys($m['permissions']));
            }
        }

        return $names;
    }
}
