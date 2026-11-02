<?php

declare(strict_types=1);

namespace App\Models\Retail;

use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerCoupon extends Model
{
    use UsesTenantConnection;

    protected $table = 'rt_customer_coupons';

    protected $fillable = ['coupon_id', 'customer_id', 'code', 'expire_date', 'used_document_id', 'used_at', 'is_active'];

    protected function casts(): array
    {
        return ['expire_date' => 'date', 'used_at' => 'datetime', 'is_active' => 'boolean'];
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }
}
