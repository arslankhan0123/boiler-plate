<?php

declare(strict_types=1);

namespace App\Models\Inventory;

use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockCountItem extends Model
{
    use UsesTenantConnection;

    protected $table = 'inv_count_items';

    protected $fillable = ['count_id', 'item_id', 'system_qty', 'counted_qty', 'note'];

    protected function casts(): array
    {
        return ['system_qty' => 'float', 'counted_qty' => 'float'];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class)->withTrashed();
    }

    public function diff(): ?float
    {
        return $this->counted_qty === null ? null : round($this->counted_qty - $this->system_qty, 3);
    }
}
