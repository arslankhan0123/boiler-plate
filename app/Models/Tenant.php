<?php

declare(strict_types=1);

namespace App\Models;

use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

/**
 * @property string $id
 * @property string $name
 * @property bool $is_active
 */
class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase;

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
