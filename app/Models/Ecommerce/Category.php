<?php

declare(strict_types=1);

namespace App\Models\Ecommerce;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use Auditable, UsesTenantConnection;

    protected $table = 'ecom_categories';

    protected $fillable = ['name', 'slug', 'sort', 'is_active'];

    protected function casts(): array { return ['is_active' => 'boolean']; }

    public function products(): HasMany { return $this->hasMany(Product::class, 'category_id'); }
}
