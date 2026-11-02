<?php

declare(strict_types=1);

namespace App\Models\Inventory;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\UsesTenantConnection;
use App\Models\Settings\TaxRate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Setup\Models\Supplier;

class Item extends Model
{
    use Auditable;
    use SoftDeletes;
    use UsesTenantConnection;

    public const ATTRS = ['department', 'category', 'subcategory', 'brand', 'unit', 'color', 'size', 'rack', 'bin', 'sub_bin', 'variant'];

    protected $table = 'inv_items';

    protected $guarded = ['id', 'stock', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'is_service' => 'boolean', 'is_active' => 'boolean', 'expire_date' => 'date',
            'price' => 'float', 'purchase_price' => 'float', 'sales_price' => 'float', 'mrp' => 'float', 'profit_margin' => 'float',
            'discount' => 'float', 'seller_points' => 'float', 'opening_stock' => 'float', 'stock' => 'float',
            'alert_qty' => 'float', 'min_qty' => 'float', 'max_qty' => 'float', 'reorder_qty' => 'float',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class, 'tax_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Attribute::class, 'category_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Attribute::class, 'brand_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Attribute::class, 'unit_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Attribute::class, 'department_id');
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Attribute::class, 'subcategory_id');
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(Attribute::class, 'color_id');
    }

    public function size(): BelongsTo
    {
        return $this->belongsTo(Attribute::class, 'size_id');
    }

    public function rack(): BelongsTo
    {
        return $this->belongsTo(Attribute::class, 'rack_id');
    }

    public function bin(): BelongsTo
    {
        return $this->belongsTo(Attribute::class, 'bin_id');
    }

    public function sub_bin(): BelongsTo
    {
        return $this->belongsTo(Attribute::class, 'sub_bin_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(Attribute::class, 'variant_id');
    }

    /** Sellable rows: single items and variant children (not the variant parent). */
    public function scopeSellable(Builder $q): Builder
    {
        return $q->where('is_active', true)->where(fn ($w) => $w->where('item_group', 'single')->orWhereNotNull('parent_id'));
    }

    public function displayName(): string
    {
        return $this->parent_id && $this->variant ? $this->name.' ('.$this->variant->name.')' : $this->name;
    }

    public function taxRate(): float
    {
        return (float) ($this->tax->rate ?? 0);
    }

    public function stockIn(?int $warehouseId): float
    {
        if (! $warehouseId) {
            return (float) $this->stock;
        }

        return (float) ($this->stocks->firstWhere('warehouse_id', $warehouseId)->qty ?? 0);
    }

    public function isLow(): bool
    {
        return ! $this->is_service && $this->alert_qty > 0 && $this->stock <= $this->alert_qty;
    }

    public function imageUrl(): ?string
    {
        return $this->image ? asset('storage/'.$this->image) : null;
    }
}
