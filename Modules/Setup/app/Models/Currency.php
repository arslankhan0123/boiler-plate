<?php

declare(strict_types=1);

namespace Modules\Setup\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\TracksSoftDeleteUser;
use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Modules\Setup\Database\Factories\CurrencyFactory;

/**
 * A currency a tenant has selected from the central `lookups` master. Lives in the
 * TENANT database; the snapshot fields mirror the master ISO-4217 entry at
 * selection time, with `lookup_id` linking back to it.
 *
 * @property int $id
 * @property int $lookup_id
 * @property string $code
 * @property string $name
 * @property string|null $symbol
 * @property string|null $numeric_code
 * @property int|null $decimal_digits
 * @property bool $is_active
 * @property bool $is_default
 * @property int $sort_order
 * @property int|null $created_by
 * @property int|null $deleted_by_user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class Currency extends Model
{
    use Auditable;

    /** @use HasFactory<CurrencyFactory> */
    use HasFactory;

    use SoftDeletes;
    use TracksSoftDeleteUser;
    use UsesTenantConnection;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'lookup_id',
        'code',
        'name',
        'symbol',
        'numeric_code',
        'decimal_digits',
        'is_active',
        'is_default',
        'sort_order',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'lookup_id' => 'integer',
            'decimal_digits' => 'integer',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'sort_order' => 'integer',
            'created_by' => 'integer',
            'deleted_by_user_id' => 'integer',
        ];
    }

    protected static function newFactory(): CurrencyFactory
    {
        return CurrencyFactory::new();
    }
}
