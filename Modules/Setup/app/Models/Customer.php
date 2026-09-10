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
use Modules\Setup\Database\Factories\CustomerFactory;

/**
 * A tenant-defined customer. Pure tenant-owned custom data — NOT picked from the
 * central `lookups` master. Optionally linked to the tenant's own `Country` /
 * `State` / `Currency`; those names are read through the relations, never
 * duplicated.
 *
 * @property int $id
 * @property string $company_name
 * @property string|null $code
 * @property string|null $vat_number
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $mobile
 * @property string|null $fax
 * @property string|null $whatsapp
 * @property string|null $website
 * @property string|null $short_name
 * @property string|null $vendor_code
 * @property string|null $group_name
 * @property int|null $currency_id
 * @property int|null $country_id
 * @property int|null $state_id
 * @property string|null $default_language
 * @property string|null $address
 * @property string|null $city
 * @property string|null $zip
 * @property string|null $location_url
 * @property float $opening_balance
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
class Customer extends Model
{
    use Auditable;

    /** @use HasFactory<CustomerFactory> */
    use HasFactory;

    use SoftDeletes;
    use TracksSoftDeleteUser;
    use UsesTenantConnection;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_name',
        'code',
        'vat_number',
        'email',
        'phone',
        'mobile',
        'fax',
        'whatsapp',
        'website',
        'short_name',
        'vendor_code',
        'group_name',
        'currency_id',
        'country_id',
        'state_id',
        'default_language',
        'address',
        'city',
        'zip',
        'location_url',
        'opening_balance',
        'data',
        'is_active',
        'is_default',
        'sort_order',
        'created_by',
    ];

    /**
     * The tenant country this customer is based in (optional).
     *
     * @return BelongsTo<Country, $this>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * The tenant state/province this customer is based in (optional).
     *
     * @return BelongsTo<State, $this>
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    /**
     * The tenant currency this customer transacts in (optional).
     *
     * @return BelongsTo<Currency, $this>
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'currency_id' => 'integer',
            'country_id' => 'integer',
            'state_id' => 'integer',
            'opening_balance' => 'float',
            'data' => 'array',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'sort_order' => 'integer',
            'created_by' => 'integer',
            'deleted_by_user_id' => 'integer',
        ];
    }

    protected static function newFactory(): CustomerFactory
    {
        return CustomerFactory::new();
    }
}
