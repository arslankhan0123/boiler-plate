<?php

declare(strict_types=1);

namespace App\Models\Ecommerce;

use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use UsesTenantConnection;

    protected $table = 'ecom_order_items';

    protected $fillable = ['order_id', 'item_id', 'product_id', 'name', 'qty', 'price', 'tax', 'line_total'];

    protected function casts(): array { return ['qty' => 'float', 'price' => 'float', 'tax' => 'float', 'line_total' => 'float']; }

    public function order(): BelongsTo { return $this->belongsTo(Order::class, 'order_id'); }
}
