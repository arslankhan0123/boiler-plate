<?php

declare(strict_types=1);

namespace App\Models\Inventory;

use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdjustmentItem extends Model
{
    use UsesTenantConnection;

    protected $table = 'inv_adjustment_items';

    protected $fillable = ['adjustment_id', 'item_id', 'qty', 'description'];

    protected function casts(): array
    {
        return ['qty' => 'float'];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class)->withTrashed();
    }
}
