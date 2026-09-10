<?php
declare(strict_types=1);
namespace Modules\Ecommerce\Models;
use App\Models\Concerns\UsesTenantConnection; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class OrderTracking extends Model { use UsesTenantConnection; protected $table='ecommerce_order_trackings'; protected $fillable=['status','message','carrier','tracking_number','location','occurred_at','created_by']; protected function casts(): array{return ['occurred_at'=>'datetime'];} public function order(): BelongsTo{return $this->belongsTo(Order::class);} }
