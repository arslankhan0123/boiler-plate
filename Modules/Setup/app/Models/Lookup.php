<?php

declare(strict_types=1);

namespace Modules\Setup\Models;

use App\Models\Concerns\TracksSoftDeleteUser;
use App\Models\Concerns\UsesCentralConnection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Modules\Setup\Database\Factories\LookupFactory;

/**
 * Central master reference entry. Generic: `type` distinguishes data sets
 * (country / continent / currency / …). The country dataset is the master list
 * every tenant searches and picks from.
 *
 * Pinned to the CENTRAL connection so it reads/writes the central DB even during
 * a tenant request.
 *
 * @property int $id
 * @property string $type
 * @property int|null $parent_id
 * @property string|null $code
 * @property string $name
 * @property string|null $image
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
 * @property string|null $external_id
 * @property float|null $latitude
 * @property float|null $longitude
 * @property int|null $decimal_digits
 * @property int $sort_order
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class Lookup extends Model
{
    /** @use HasFactory<LookupFactory> */
    use HasFactory;

    use SoftDeletes;
    use TracksSoftDeleteUser;
    use UsesCentralConnection;

    public const TYPE_COUNTRY = 'country';

    public const TYPE_STATE = 'state';

    public const TYPE_CITY = 'city';

    public const TYPE_CURRENCY = 'currency';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'type',
        'parent_id',
        'code',
        'name',
        'image',
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
        'external_id',
        'latitude',
        'longitude',
        'decimal_digits',
        'sort_order',
        'is_active',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'parent_id' => 'integer',
            'latitude' => 'float',
            'longitude' => 'float',
            'decimal_digits' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * The parent lookup in the hierarchy (city → state → country); null at the top.
     *
     * @return BelongsTo<Lookup, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    protected static function newFactory(): LookupFactory
    {
        return LookupFactory::new();
    }
}
