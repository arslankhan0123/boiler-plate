<?php

declare(strict_types=1);

namespace Modules\Setup\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\TracksSoftDeleteUser;
use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Modules\Setup\Database\Factories\CityFactory;

/**
 * A city a tenant has selected from the central `lookups` master. Lives in the
 * TENANT database; the snapshot fields mirror the master entry (and its parent
 * state + country) at selection time, with `lookup_id` linking back to it.
 *
 * @property int $id
 * @property int $lookup_id
 * @property string|null $external_id
 * @property string $name
 * @property int $state_id
 * @property float|null $latitude
 * @property float|null $longitude
 * @property bool $is_active
 * @property bool $is_default
 * @property int $sort_order
 * @property int|null $created_by
 * @property int|null $deleted_by_user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class City extends Model
{
    use Auditable;

    /** @use HasFactory<CityFactory> */
    use HasFactory;

    use SoftDeletes;
    use TracksSoftDeleteUser;
    use UsesTenantConnection;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'lookup_id',
        'external_id',
        'name',
        'state_id',
        'latitude',
        'longitude',
        'is_active',
        'is_default',
        'sort_order',
        'created_by',
    ];

    /**
     * The state this city belongs to.
     *
     * @return BelongsTo<State, $this>
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    /**
     * The tenant-defined areas under this city.
     *
     * @return HasMany<Area, $this>
     */
    public function areas(): HasMany
    {
        return $this->hasMany(Area::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'lookup_id' => 'integer',
            'state_id' => 'integer',
            'latitude' => 'float',
            'longitude' => 'float',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'sort_order' => 'integer',
            'created_by' => 'integer',
            'deleted_by_user_id' => 'integer',
        ];
    }

    protected static function newFactory(): CityFactory
    {
        return CityFactory::new();
    }
}
