<?php

declare(strict_types=1);

namespace Modules\Pos\Models;

use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosSale extends Model
{
    use UsesTenantConnection;

    protected $table = 'pos_sales';

    protected $fillable = ['shift_id', 'number', 'customer_name', 'customer_phone', 'status', 'subtotal', 'discount_total', 'tax_total', 'grand_total', 'created_by'];

    protected function casts(): array
    {
        return ['subtotal' => 'decimal:2', 'discount_total' => 'decimal:2', 'tax_total' => 'decimal:2', 'grand_total' => 'decimal:2'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(PosSaleItem::class, 'sale_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PosPayment::class, 'sale_id');
    }
}
