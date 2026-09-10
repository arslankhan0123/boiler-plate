<?php

declare(strict_types=1);

namespace Modules\Setup\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\TracksSoftDeleteUser;
use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Modules\Setup\Database\Factories\AreaFactory;

/**
 * A tenant-defined area/locality under one of the tenant's cities. Unlike the
 * other Setup resources, an Area is NOT picked from the central `lookups` master —
 * it is pure tenant-owned custom data (address, post code, contact, …). It belongs
 * to a tenant `City` (`city_id`) and denormalises the city/state/country names at
 * create time so listings/exports never join.
 *
 * @property int $id
 * @property int $city_id
 * @property string $name
 * @property string|null $address_line_1
 * @property string|null $address_line_2
 * @property string|null $area_code
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $description
 * @property float|null $latitude
 * @property float|null $longitude
 * @property array<string, mixed>|null $data
 * @property bool $is_active
 * @property bool $is_default
 * @property int $sort_order
 * @property int|null $created_by
 * @property int|null $deleted_by_user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class Area extends Model
{
    use Auditable;

    /** @use HasFactory<AreaFactory> */
    use HasFactory;

    use SoftDeletes;
    use TracksSoftDeleteUser;
    use UsesTenantConnection;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'city_id',
        'name',
        'address_line_1',
        'address_line_2',
        'area_code',
        'phone',
        'email',
        'description',
        'latitude',
        'longitude',
        'data',
        'is_active',
        'is_default',
        'sort_order',
        'created_by',
    ];

    /**
     * The tenant city this area belongs to.
     *
     * @return BelongsTo<City, $this>
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'city_id' => 'integer',
            'latitude' => 'float',
            'longitude' => 'float',
            'data' => 'array',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'sort_order' => 'integer',
            'created_by' => 'integer',
            'deleted_by_user_id' => 'integer',
        ];
    }

    protected static function newFactory(): AreaFactory
    {
        return AreaFactory::new();
    }
}
