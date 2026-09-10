<?php
declare(strict_types=1);
namespace Modules\Ecommerce\Models;
use App\Models\Concerns\UsesTenantConnection; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ProductImage extends Model { use UsesTenantConnection; protected $table='ecommerce_product_images'; protected $fillable=['path','alt_text','sort_order']; public function product(): BelongsTo{return $this->belongsTo(Product::class);} }
