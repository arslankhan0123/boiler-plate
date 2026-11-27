<?php

declare(strict_types=1);

namespace App\Models\Inventory;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    use Auditable;
    use UsesTenantConnection;

    public const TYPES = ['warehouse' => 'Warehouse', 'shop' => 'Shop / Counter', 'system' => 'Main (System)'];

    protected $table = 'inv_warehouses';

    protected $fillable = ['name', 'type', 'contact_person', 'mobile', 'phone', 'email', 'city', 'area', 'address', 'is_default', 'is_active'];

    protected function casts(): array
    {
        return ['is_default' => 'boolean', 'is_active' => 'boolean'];
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    /** The default warehouse; created on first use so every tenant always has one. */
    public static function default(): self
    {
        return self::query()->where('is_default', true)->first()
            ?? self::query()->where('is_active', true)->orderBy('id')->first()
            ?? self::create(['name' => 'Main Warehouse', 'type' => 'system', 'is_default' => true, 'is_active' => true]);
    }

    public static function options(): array
    {
        self::default();

        return self::query()->where('is_active', true)->orderByDesc('is_default')->orderBy('name')->pluck('name', 'id')->all();
    }
}
