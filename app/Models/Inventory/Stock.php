<?php

declare(strict_types=1);

namespace App\Models\Inventory;

use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Stock extends Model
{
    use UsesTenantConnection;

    protected $table = 'inv_stocks';

    protected $fillable = ['warehouse_id', 'item_id', 'qty'];

    protected function casts(): array
    {
        return ['qty' => 'float'];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
