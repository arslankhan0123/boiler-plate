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
use Modules\Setup\Database\Factories\SupplierFactory;

/**
 * A tenant-defined supplier (vendor). Pure tenant-owned custom data — NOT picked
 * from the central `lookups` master. Optionally linked to the tenant's own
 * `Country` / `State` / `Currency`; those names are read through the relations,
 * never duplicated.
 *
 * @property int $id
 * @property string $company_name
 * @property string|null $vat_number
 * @property string|null $contact_person
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $whatsapp
 * @property string|null $website
 * @property string|null $group_name
 * @property int|null $currency_id
 * @property int|null $country_id
 * @property int|null $state_id
 * @property string|null $default_language
 * @property string|null $street
 * @property string|null $city
 * @property string|null $zip
 * @property string|null $po_box
 * @property string|null $mailing_address
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
class Supplier extends Model
{
    use Auditable;

    /** @use HasFactory<SupplierFactory> */
    use HasFactory;

    use SoftDeletes;
    use TracksSoftDeleteUser;
    use UsesTenantConnection;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_name',
        'vat_number',
        'contact_person',
        'email',
        'phone',
        'whatsapp',
        'website',
        'group_name',
        'currency_id',
        'country_id',
        'state_id',
        'default_language',
        'street',
        'city',
        'zip',
        'po_box',
        'mailing_address',
        'opening_balance',
        'data',
        'is_active',
        'is_default',
        'sort_order',
        'created_by',
    ];

    /**
     * The tenant country this supplier is based in (optional).
     *
     * @return BelongsTo<Country, $this>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * The tenant state/province this supplier is based in (optional).
     *
     * @return BelongsTo<State, $this>
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    /**
     * The tenant currency this supplier transacts in (optional).
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

    protected static function newFactory(): SupplierFactory
    {
        return SupplierFactory::new();
    }
}
