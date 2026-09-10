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
use Modules\Setup\Database\Factories\StateFactory;

/**
 * A state/province a tenant has selected from the central `lookups` master. Lives
 * in the TENANT database; the snapshot fields mirror the master entry (and its
 * parent country) at selection time, with `lookup_id` linking back to it.
 *
 * @property int $id
 * @property int $lookup_id
 * @property string|null $external_id
 * @property string $name
 * @property string|null $code
 * @property int $country_id
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
class State extends Model
{
    use Auditable;

    /** @use HasFactory<StateFactory> */
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
        'code',
        'country_id',
        'latitude',
        'longitude',
        'is_active',
        'is_default',
        'sort_order',
        'created_by',
    ];

    /**
     * The country this state belongs to.
     *
     * @return BelongsTo<Country, $this>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * The cities the tenant has selected under this state.
     *
     * @return HasMany<City, $this>
     */
    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'lookup_id' => 'integer',
            'country_id' => 'integer',
            'latitude' => 'float',
            'longitude' => 'float',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'sort_order' => 'integer',
            'created_by' => 'integer',
            'deleted_by_user_id' => 'integer',
        ];
    }

    protected static function newFactory(): StateFactory
    {
        return StateFactory::new();
    }
}
