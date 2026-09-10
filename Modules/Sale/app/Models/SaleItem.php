<?php

declare(strict_types=1);

namespace Modules\Sale\Models;

use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    use UsesTenantConnection;

    protected $table = 'sale_items';

    protected $fillable = ['sale_order_id', 'product_id', 'name', 'sku', 'quantity', 'unit_price', 'tax_rate', 'tax_total', 'discount_total', 'line_total'];
}
