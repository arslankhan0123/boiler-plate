<?php

declare(strict_types=1);

namespace App\Models\Ecommerce;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use Auditable, UsesTenantConnection;

    protected $table = 'ecom_categories';

    protected $fillable = ['parent_id', 'name', 'slug', 'sort', 'is_active'];

    protected function casts(): array { return ['is_active' => 'boolean']; }

    public function parent(): BelongsTo { return $this->belongsTo(self::class, 'parent_id'); }
    public function children(): HasMany { return $this->hasMany(self::class, 'parent_id'); }
    public function products(): HasMany { return $this->hasMany(Product::class, 'category_id'); }

    /** "Parent › Child" path. */
    public function path(): string
    {
        return $this->parent ? $this->parent->name.' › '.$this->name : $this->name;
    }

    /** IDs of this category and all its descendants (to prevent circular parenting). */
    public function descendantIds(): array
    {
        $ids = [$this->id];
        foreach ($this->children as $child) {
            $ids = array_merge($ids, $child->descendantIds());
        }

        return $ids;
    }
}
