<?php

declare(strict_types=1);

namespace App\Models\Inventory;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transfer extends Model
{
    use Auditable;
    use UsesTenantConnection;

    protected $table = 'inv_transfers';

    protected $fillable = ['reference_no', 'from_warehouse_id', 'to_warehouse_id', 'date', 'note', 'created_by'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransferItem::class);
    }

    public function from(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function to(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }
}
