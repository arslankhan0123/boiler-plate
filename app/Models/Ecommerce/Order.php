<?php

declare(strict_types=1);

namespace App\Models\Ecommerce;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\UsesTenantConnection;
use App\Models\Retail\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use Auditable, UsesTenantConnection;

    protected $table = 'ecom_orders';

    public const STATUSES = ['pending' => 'Pending', 'confirmed' => 'Confirmed', 'processing' => 'Processing', 'shipped' => 'Shipped', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled'];
    public const TONES = ['pending' => 'muted', 'confirmed' => 'info', 'processing' => 'primary', 'shipped' => 'warning', 'delivered' => 'success', 'cancelled' => 'danger'];
    public const PAY_TONES = ['unpaid' => 'danger', 'paid' => 'success', 'refunded' => 'muted'];

    protected $fillable = ['order_no', 'customer_id', 'customer_name', 'customer_email', 'customer_phone', 'shipping_address',
        'status', 'payment_status', 'payment_method', 'shipping_method_id', 'subtotal', 'shipping_cost', 'discount', 'tax',
        'grand_total', 'coupon_code', 'notes', 'sale_document_id', 'placed_at', 'created_by'];

    protected function casts(): array
    {
        return ['subtotal' => 'float', 'shipping_cost' => 'float', 'discount' => 'float', 'tax' => 'float',
            'grand_total' => 'float', 'placed_at' => 'datetime'];
    }

    public function items(): HasMany { return $this->hasMany(OrderItem::class, 'order_id'); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class, 'customer_id'); }

    public function statusLabel(): string { return self::STATUSES[$this->status] ?? $this->status; }
    public function statusTone(): string { return self::TONES[$this->status] ?? 'muted'; }
    public function payTone(): string { return self::PAY_TONES[$this->payment_status] ?? 'muted'; }
    public function isConverted(): bool { return (bool) $this->sale_document_id; }

    public static function nextNumber(): string
    {
        $n = (int) (self::query()->max('id') ?? 0) + 1;

        return 'ORD-'.str_pad((string) $n, 5, '0', STR_PAD_LEFT);
    }
}
