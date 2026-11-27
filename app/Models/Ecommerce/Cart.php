<?php

declare(strict_types=1);

namespace App\Models\Ecommerce;

use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use UsesTenantConnection;

    protected $table = 'ecom_carts';

    protected $fillable = ['token', 'customer_email', 'items', 'subtotal', 'placed'];

    protected function casts(): array { return ['items' => 'array', 'subtotal' => 'float', 'placed' => 'boolean']; }

    public function itemCount(): int { return collect($this->items ?? [])->sum('qty'); }
}
