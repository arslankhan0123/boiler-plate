<?php

declare(strict_types=1);

namespace App\Models\Inventory;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Stock adjustment (+/-) or damaged-items entry (kind=damage). */
class Adjustment extends Model
{
    use Auditable;
    use UsesTenantConnection;

    protected $table = 'inv_adjustments';

    protected $fillable = ['kind', 'reference_no', 'warehouse_id', 'date', 'note', 'created_by'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(AdjustmentItem::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
