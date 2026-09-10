<?php

declare(strict_types=1);

namespace Modules\Setup\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\TracksSoftDeleteUser;
use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Modules\Setup\Database\Factories\CountryFactory;

/**
 * A country a tenant has selected from the central `lookups` master. Lives in the
 * TENANT database; the snapshot fields mirror the master entry at selection time,
 * with `lookup_id` linking back to it.
 *
 * @property int $id
 * @property int $lookup_id
 * @property string $name
 * @property string|null $iso2
 * @property string|null $iso3
 * @property string|null $numeric_code
 * @property string|null $phone_code
 * @property string|null $capital
 * @property string|null $region
 * @property string|null $currency_code
 * @property string|null $currency_symbol
 * @property string|null $locale
 * @property string|null $timezone
 * @property string|null $flag_image
 * @property bool $is_active
 * @property bool $is_default
 * @property int $sort_order
 * @property int|null $created_by
 * @property int|null $deleted_by_user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class Country extends Model
{
    use Auditable;

    /** @use HasFactory<CountryFactory> */
    use HasFactory;

    use SoftDeletes;
    use TracksSoftDeleteUser;
    use UsesTenantConnection;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'lookup_id',
        'name',
        'iso2',
        'iso3',
        'numeric_code',
        'phone_code',
        'capital',
        'region',
        'currency_code',
        'currency_symbol',
        'locale',
        'timezone',
        'flag_image',
        'is_active',
        'is_default',
        'sort_order',
        'created_by',
    ];

    /**
     * The states the tenant has selected under this country.
     *
     * @return HasMany<State, $this>
     */
    public function states(): HasMany
    {
        return $this->hasMany(State::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'lookup_id' => 'integer',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'sort_order' => 'integer',
            'created_by' => 'integer',
            'deleted_by_user_id' => 'integer',
        ];
    }

    protected static function newFactory(): CountryFactory
    {
        return CountryFactory::new();
    }
}
