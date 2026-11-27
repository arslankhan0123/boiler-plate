<?php

declare(strict_types=1);

namespace App\Models;

use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

/**
 * @property string $id
 * @property string $name
 * @property bool $is_active
 */
class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    /**
     * Columns stored as real DB columns (everything else overflows into `data`).
     *
     * @return array<int, string>
     */
    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'is_active',
        ];
    }

    public function users(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(User::class);
    }

    /** Per-request memo of enabled modules / disabled features. */
    private ?array $enabledModulesMemo = null;

    private ?array $disabledFeaturesMemo = null;

    /** Codes of business modules enabled for this tenant (tenant_modules). */
    public function enabledModules(): array
    {
        return $this->enabledModulesMemo ??= $this->getConnection()->table('tenant_modules')
            ->where('tenant_id', $this->getTenantKey())->pluck('module_code')->all();
    }

    public function hasModule(string $code): bool
    {
        return in_array($code, $this->enabledModules(), true);
    }

    /** @return array<string, list<string>> module => switched-off feature codes */
    public function disabledFeatures(): array
    {
        if ($this->disabledFeaturesMemo !== null) {
            return $this->disabledFeaturesMemo;
        }

        $out = [];
        $rows = $this->getConnection()->table('tenant_module_features')
            ->where('tenant_id', $this->getTenantKey())->where('is_enabled', false)->get(['module_code', 'feature_code']);
        foreach ($rows as $r) {
            $out[$r->module_code][] = $r->feature_code;
        }

        return $this->disabledFeaturesMemo = $out;
    }

    public function featureEnabled(string $module, string $feature): bool
    {
        return ! in_array($feature, $this->disabledFeatures()[$module] ?? [], true);
    }

    /**
     * Replace this tenant's module assignment.
     *
     * @param  list<string>  $modules  enabled module codes
     * @param  array<string, list<string>>  $disabledFeatures  module => feature codes switched off
     */
    public function syncModules(array $modules, array $disabledFeatures = []): void
    {
        $db = $this->getConnection();
        $id = $this->getTenantKey();

        $db->transaction(function () use ($db, $id, $modules, $disabledFeatures) {
            $db->table('tenant_modules')->where('tenant_id', $id)->whereNotIn('module_code', $modules)->delete();
            foreach ($modules as $code) {
                $db->table('tenant_modules')->insertOrIgnore(['tenant_id' => $id, 'module_code' => $code, 'enabled_at' => now()]);
            }

            $db->table('tenant_module_features')->where('tenant_id', $id)->delete();
            foreach ($disabledFeatures as $module => $features) {
                if (! in_array($module, $modules, true)) {
                    continue;
                }
                foreach ($features as $feature) {
                    $db->table('tenant_module_features')->insert([
                        'tenant_id' => $id, 'module_code' => $module, 'feature_code' => $feature, 'is_enabled' => false,
                    ]);
                }
            }
        });

        $this->enabledModulesMemo = $this->disabledFeaturesMemo = null;
    }

    /** Database name, e.g. tenant_001_database */
    public function databaseName(): string
    {
        return config('tenancy.database.prefix').$this->getTenantKey().config('tenancy.database.suffix');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
