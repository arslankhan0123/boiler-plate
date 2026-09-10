<?php

declare(strict_types=1);

namespace Modules\Purchase\Models;

use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use UsesTenantConnection;

    protected $table = 'purchase_orders';

    protected $fillable = ['warehouse_id', 'number', 'supplier_name', 'supplier_invoice', 'status', 'subtotal', 'tax_total', 'discount_total', 'grand_total', 'received_at', 'notes', 'created_by'];

    protected function casts(): array
    {
        return ['received_at' => 'datetime', 'subtotal' => 'decimal:2', 'tax_total' => 'decimal:2', 'discount_total' => 'decimal:2', 'grand_total' => 'decimal:2'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class, 'purchase_order_id');
    }
}
