<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use Auditable, SoftDeletes, UsesTenantConnection;

    protected $table = 'ecommerce_services';

    protected $fillable = ['category_id', 'name', 'slug', 'sku', 'description', 'duration_minutes', 'cost_price', 'sale_price', 'tax_rate', 'is_active', 'created_by'];

    protected function casts(): array
    {
        return ['cost_price' => 'decimal:2', 'sale_price' => 'decimal:2', 'tax_rate' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
