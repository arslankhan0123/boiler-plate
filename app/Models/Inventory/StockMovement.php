<?php

declare(strict_types=1);

namespace App\Models\Inventory;

use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    use UsesTenantConnection;

    public const TYPES = [
        'opening' => 'Opening stock', 'purchase' => 'Purchase', 'purchase_return' => 'Purchase return',
        'sale' => 'Sale', 'sale_return' => 'Sales return', 'adjustment' => 'Adjustment', 'damage' => 'Damaged',
        'transfer_in' => 'Transfer in', 'transfer_out' => 'Transfer out',
    ];

    protected $table = 'inv_stock_movements';

    protected $fillable = ['warehouse_id', 'item_id', 'type', 'qty', 'reference_type', 'reference_id', 'date', 'note', 'created_by'];

    protected function casts(): array
    {
        return ['qty' => 'float', 'date' => 'date'];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class)->withTrashed();
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
