<?php
declare(strict_types=1);
namespace Modules\Ecommerce\Models;
use App\Models\Concerns\UsesTenantConnection; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class OrderItem extends Model { use UsesTenantConnection; protected $table='ecommerce_order_items'; protected $fillable=['item_type','product_id','service_id','name','sku','quantity','unit_price','cost_price','tax_rate','tax_total','discount_total','line_total','meta']; protected function casts(): array{return ['quantity'=>'integer','unit_price'=>'decimal:2','cost_price'=>'decimal:2','tax_rate'=>'decimal:2','tax_total'=>'decimal:2','discount_total'=>'decimal:2','line_total'=>'decimal:2','meta'=>'array'];} public function order(): BelongsTo{return $this->belongsTo(Order::class);} }
