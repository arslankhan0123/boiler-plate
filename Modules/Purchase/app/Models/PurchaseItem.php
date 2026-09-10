<?php

declare(strict_types=1);

namespace Modules\Purchase\Models;

use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    use UsesTenantConnection;

    protected $table = 'purchase_items';

    protected $fillable = ['purchase_order_id', 'product_id', 'name', 'sku', 'quantity', 'unit_cost', 'tax_rate', 'tax_total', 'discount_total', 'line_total'];
}
