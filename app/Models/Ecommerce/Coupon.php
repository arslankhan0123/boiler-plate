<?php

declare(strict_types=1);

namespace App\Models\Ecommerce;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use Auditable, UsesTenantConnection;

    protected $table = 'ecom_coupons';

    protected $fillable = ['code', 'type', 'value', 'min_order', 'starts_at', 'ends_at', 'usage_limit', 'used', 'is_active'];

    protected function casts(): array
    {
        return ['value' => 'float', 'min_order' => 'float', 'starts_at' => 'date', 'ends_at' => 'date', 'is_active' => 'boolean'];
    }

    public function isValid(float $subtotal): bool
    {
        if (! $this->is_active || $subtotal < $this->min_order) {
            return false;
        }
        if ($this->starts_at && today()->lt($this->starts_at)) {
            return false;
        }
        if ($this->ends_at && today()->gt($this->ends_at)) {
            return false;
        }
        if ($this->usage_limit !== null && $this->used >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    public function discountFor(float $subtotal): float
    {
        if (! $this->isValid($subtotal)) {
            return 0.0;
        }

        return $this->type === 'percent'
            ? round($subtotal * $this->value / 100, 2)
            : min($this->value, $subtotal);
    }
}
