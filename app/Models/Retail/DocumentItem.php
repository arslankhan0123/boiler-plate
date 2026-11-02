<?php

declare(strict_types=1);

namespace App\Models\Retail;

use App\Models\Concerns\UsesTenantConnection;
use App\Models\Inventory\Item;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentItem extends Model
{
    use UsesTenantConnection;

    protected $table = 'rt_document_items';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return collect(['qty', 'unit_price', 'tax_rate', 'tax_amt', 'discount_input', 'discount_amt', 'unit_total', 'total', 'purchase_price', 'profit_margin', 'sales_price', 'seller_points'])
            ->mapWithKeys(fn ($c) => [$c => 'float'])->all();
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class)->withTrashed();
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class)->withTrashed();
    }
}
