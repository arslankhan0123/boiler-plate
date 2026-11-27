<?php

declare(strict_types=1);

namespace App\Models\Retail;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Retail view of the shared customers table. */
class Customer extends Model
{
    use Auditable;
    use SoftDeletes;
    use UsesTenantConnection;

    protected $table = 'customers';

    protected $fillable = [
        'company_name', 'contact_person', 'code', 'vat_number', 'email', 'phone', 'mobile', 'whatsapp', 'address', 'city', 'zip', 'location_url',
        'ship_address', 'ship_city', 'ship_zip', 'opening_balance', 'credit_limit', 'price_level_type', 'price_level', 'is_active', 'is_default', 'created_by',
    ];

    protected function casts(): array
    {
        return ['opening_balance' => 'float', 'credit_limit' => 'float', 'price_level' => 'float', 'is_active' => 'boolean', 'is_default' => 'boolean'];
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function getNameAttribute(): string
    {
        return (string) $this->company_name;
    }

    /** Walk-in customer used by POS when none is chosen. */
    public static function walkIn(): self
    {
        return self::query()->where('is_default', true)->first()
            ?? self::query()->firstOrCreate(['company_name' => 'Walk-in Customer'], ['is_active' => true, 'is_default' => true, 'code' => 'CU0001']);
    }

    /** Price for this customer after their price level (+/- %). */
    public function priceFor(float $price): float
    {
        if (! $this->price_level_type || ! $this->price_level) {
            return $price;
        }
        $delta = $price * $this->price_level / 100;

        return round($this->price_level_type === 'increase' ? $price + $delta : $price - $delta, 4);
    }
}
