<?php

declare(strict_types=1);

namespace App\Models\Ecommerce;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;

class ShippingMethod extends Model
{
    use Auditable, UsesTenantConnection;

    protected $table = 'ecom_shipping_methods';

    protected $fillable = ['name', 'rate', 'free_over', 'is_active', 'sort'];

    protected function casts(): array { return ['rate' => 'float', 'free_over' => 'float', 'is_active' => 'boolean']; }

    public function costFor(float $subtotal): float
    {
        if ($this->free_over !== null && $subtotal >= $this->free_over) {
            return 0.0;
        }

        return $this->rate;
    }
}
