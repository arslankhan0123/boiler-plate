<?php

declare(strict_types=1);

namespace App\Models\Inventory;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Item masters: department, category, subcategory, brand, unit, color, size, rack, bin, sub_bin, variant. */
class Attribute extends Model
{
    use Auditable;
    use UsesTenantConnection;

    /** type => [label, plural, parent type, icon] */
    public const TYPES = [
        'department' => ['Department', 'Departments', null, 'diagram-3'],
        'category' => ['Category', 'Categories', 'department', 'tags'],
        'subcategory' => ['Sub Category', 'Sub Categories', 'category', 'tag'],
        'brand' => ['Brand', 'Brands', null, 'award'],
        'unit' => ['Unit', 'Units', null, 'rulers'],
        'variant' => ['Variant', 'Variants', null, 'layers'],
        'color' => ['Color', 'Colors', null, 'palette'],
        'size' => ['Size', 'Sizes', null, 'aspect-ratio'],
        'rack' => ['Rack', 'Racks', null, 'bookshelf'],
        'bin' => ['Bin', 'Bins', null, 'inbox'],
        'sub_bin' => ['Sub Bin', 'Sub Bins', 'bin', 'inboxes'],
    ];

    protected $table = 'inv_attributes';

    protected $fillable = ['type', 'parent_id', 'code', 'name', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** [id => name] list for selects. */
    public static function options(string $type, bool $withParent = false): array
    {
        return self::query()->where('type', $type)->where('is_active', true)->orderBy('name')
            ->get(['id', 'name', 'parent_id'])
            ->mapWithKeys(fn ($a) => [$a->id => $a->name])->all();
    }
}
