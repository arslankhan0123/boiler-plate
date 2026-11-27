<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use UsesTenantConnection;

    protected $table = 'inventory_stocks';

    protected $fillable = ['warehouse_id', 'product_id', 'quantity', 'reserved_quantity', 'reorder_level'];

    protected function casts(): array
    {
        return ['quantity' => 'integer', 'reserved_quantity' => 'integer', 'reorder_level' => 'integer'];
    }
}
