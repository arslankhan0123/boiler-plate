<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use Auditable, UsesTenantConnection;

    protected $table = 'ecommerce_orders';

    protected $fillable = ['number', 'customer_name', 'customer_email', 'customer_phone', 'billing_address', 'shipping_address', 'status', 'payment_status', 'subtotal', 'discount_total', 'tax_total', 'shipping_total', 'grand_total', 'notes', 'placed_at', 'created_by'];

    protected function casts(): array
    {
        return ['billing_address' => 'array', 'shipping_address' => 'array', 'subtotal' => 'decimal:2', 'discount_total' => 'decimal:2', 'tax_total' => 'decimal:2', 'shipping_total' => 'decimal:2', 'grand_total' => 'decimal:2', 'placed_at' => 'datetime'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function tracking(): HasMany
    {
        return $this->hasMany(OrderTracking::class)->latest('occurred_at');
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }
}
