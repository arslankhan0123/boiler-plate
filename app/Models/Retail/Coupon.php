<?php

declare(strict_types=1);

namespace App\Models\Retail;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    use Auditable;
    use UsesTenantConnection;

    protected $table = 'rt_coupons';

    protected $fillable = ['code', 'name', 'description', 'type', 'value', 'min_amount', 'expire_date', 'is_active'];

    protected function casts(): array
    {
        return ['value' => 'float', 'min_amount' => 'float', 'expire_date' => 'date', 'is_active' => 'boolean'];
    }

    public function customerCoupons(): HasMany
    {
        return $this->hasMany(CustomerCoupon::class);
    }

    public function isExpired(): bool
    {
        return $this->expire_date !== null && $this->expire_date->lt(today());
    }

    public function label(): string
    {
        return $this->type === 'percentage' ? rtrim(rtrim(number_format($this->value, 2), '0'), '.').'%' : number_format($this->value, 2);
    }
}
