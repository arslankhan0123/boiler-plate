<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reads module definitions from config/modules.php and keeps the master DB
 * registry tables (modules, module_dependencies) in sync with it.
 */
class ModuleRegistry
{
    private static ?array $normalized = null;

    /** @return array<string, array> code => definition (with defaults) */
    public static function all(): array
    {
        if (self::$normalized !== null) {
            return self::$normalized;
        }

        $out = [];
        $i = 0;
        foreach (config('modules.modules', []) as $code => $m) {
            $out[$code] = array_merge([
                'code' => $code,
                'route' => null,
                'name' => ucfirst($code),
                'icon' => 'bi-grid',
                'scope' => 'tenant',
                'group' => 'operations',
                'description' => null,
                'requires' => [],
                'requires_any' => [],
                'permissions' => [],
                'features' => [],
                'view_permission' => "{$code}.view",
                'sort' => $i++,
            ], $m);
        }

        return self::$normalized = $out;
    }

    public static function get(string $code): ?array
    {
        return self::all()[$code] ?? null;
    }

    public static function exists(string $code): bool
    {
        return isset(self::all()[$code]);
    }

    /** @return array<string, array> */
    public static function byScope(string ...$scopes): array
    {
        return array_filter(self::all(), fn ($m) => in_array($m['scope'], $scopes, true));
    }

    /** Business modules the Master Admin can assign. */
    public static function assignable(): array
    {
        return self::byScope('tenant');
    }

    public static function groups(): array
    {
        return config('modules.groups', []);
    }

    /** Permission names of one module. */
    public static function permissionsOf(string $code): array
    {
        return array_keys(self::get($code)['permissions'] ?? []);
    }

    /** Which module owns a permission name (null if it is not a module permission). */
    public static function moduleOfPermission(string $permission): ?string
    {
        static $index = null;
        if ($index === null) {
            $index = [];
            foreach (self::all() as $code => $m) {
                foreach (array_keys($m['permissions']) as $p) {
                    $index[$p] = $code;
                }
            }
        }

        return $index[$permission] ?? null;
    }

    public static function viewPermission(string $code): string
    {
        return self::get($code)['view_permission'] ?? "{$code}.view";
    }

    /** URL of a module (its own screen, or the generic module page) or one of its features. */
    public static function url(string $code, ?string $feature = null): string
    {
        $route = self::get($code)['route'] ?? null;

        if ($feature !== null) {
            return route('app.feature', [$code, $feature]);
        }

        return $route ? route($route) : route('app.module', $code);
    }

    /** Is the current request inside this module (and feature)? */
    public static function isActive(string $code, ?string $feature = null): bool
    {
        $route = self::get($code)['route'] ?? null;
        if ($route && $feature === null) {
            return request()->routeIs(\Illuminate\Support\Str::before($route, '.').'.*');
        }

        if (request()->route('module') !== $code) {
            return false;
        }

        return $feature === null || request()->route('feature') === $feature;
    }

    /** Modules that directly require $code. */
    public static function dependents(string $code): array
    {
        return array_keys(array_filter(self::assignable(), fn ($m) => in_array($code, $m['requires'], true)));
    }

    /** All modules $code needs, recursively. */
    public static function requirementsOf(string $code, array $seen = []): array
    {
        foreach (self::get($code)['requires'] ?? [] as $req) {
            if (! in_array($req, $seen, true)) {
                $seen[] = $req;
                $seen = self::requirementsOf($req, $seen);
            }
        }

        return $seen;
    }

    /**
     * Validate a set of enabled tenant modules against the dependency matrix.
     *
     * @return list<string> human-readable errors (empty = valid)
     */
    public static function validateSelection(array $codes): array
    {
        $errors = [];
        foreach ($codes as $code) {
            $m = self::get($code);
            if (! $m || $m['scope'] !== 'tenant') {
                $errors[] = "Unknown module: {$code}.";

                continue;
            }
            foreach ($m['requires'] as $req) {
                if (! in_array($req, $codes, true)) {
                    $errors[] = $m['name'].' requires '.(self::get($req)['name'] ?? $req).'.';
                }
            }
            if ($m['requires_any'] && ! array_intersect($m['requires_any'], $codes)) {
                $names = implode(', ', array_map(fn ($c) => self::get($c)['name'] ?? $c, $m['requires_any']));
                $errors[] = "{$m['name']} needs at least one of: {$names}.";
            }
        }

        return array_values(array_unique($errors));
    }

    /** Keep master DB tables in step with config (cheap: only when config changes). */
    public static function sync(): void
    {
        $hash = md5(serialize(config('modules.modules')));
        if (Cache::get('module_registry_hash') === $hash || ! Schema::hasTable('modules')) {
            return;
        }

        $central = config('tenancy.database.central_connection');
        DB::connection($central)->transaction(function () use ($central) {
            $db = DB::connection($central);
            foreach (self::all() as $code => $m) {
                $db->table('modules')->updateOrInsert(['code' => $code], [
                    'name' => $m['name'], 'description' => $m['description'], 'icon' => $m['icon'],
                    'scope' => $m['scope'], 'group' => $m['group'], 'sort_order' => $m['sort'],
                    'updated_at' => now(), 'created_at' => now(),
                ]);
            }
            $db->table('modules')->whereNotIn('code', array_keys(self::all()))->delete();

            $db->table('module_dependencies')->delete();
            foreach (self::all() as $code => $m) {
                foreach ($m['requires'] as $req) {
                    $db->table('module_dependencies')->insert(['module_code' => $code, 'requires_code' => $req]);
                }
            }
        });

        Cache::forever('module_registry_hash', $hash);
    }
}
