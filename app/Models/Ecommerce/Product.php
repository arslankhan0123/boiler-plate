<?php

declare(strict_types=1);

namespace App\Models\Ecommerce;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\UsesTenantConnection;
use App\Models\Inventory\Item;
use App\Support\Inventory\StockService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use Auditable, UsesTenantConnection;

    protected $table = 'ecom_products';

    protected $fillable = ['item_id', 'category_id', 'name', 'slug', 'description', 'price', 'compare_at_price',
        'image_url', 'is_published', 'is_featured', 'sort', 'seo_title', 'seo_description', 'created_by'];

    protected function casts(): array
    {
        return ['price' => 'float', 'compare_at_price' => 'float', 'is_published' => 'boolean', 'is_featured' => 'boolean'];
    }

    public function item(): BelongsTo { return $this->belongsTo(Item::class, 'item_id'); }
    public function category(): BelongsTo { return $this->belongsTo(Category::class, 'category_id'); }
    public function reviews(): HasMany { return $this->hasMany(Review::class, 'product_id'); }

    public function stock(): float { return $this->item_id ? StockService::available($this->item_id) : 0; }
    public function onSale(): bool { return $this->compare_at_price && $this->compare_at_price > $this->price; }
    public function avgRating(): float { return round((float) $this->reviews()->where('is_approved', true)->avg('rating'), 1); }
}
