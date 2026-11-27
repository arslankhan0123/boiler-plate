<?php

declare(strict_types=1);

namespace App\Models\Ecommerce;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    use Auditable, UsesTenantConnection;

    protected $table = 'ecom_reviews';

    protected $fillable = ['product_id', 'customer_name', 'email', 'rating', 'title', 'body', 'is_approved'];

    protected function casts(): array { return ['is_approved' => 'boolean', 'rating' => 'integer']; }

    public function product(): BelongsTo { return $this->belongsTo(Product::class, 'product_id'); }
}
