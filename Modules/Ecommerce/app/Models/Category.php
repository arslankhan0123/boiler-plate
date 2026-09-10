<?php
declare(strict_types=1);
namespace Modules\Ecommerce\Models;
use App\Models\Concerns\Auditable; use App\Models\Concerns\UsesTenantConnection; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\SoftDeletes; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Category extends Model { use Auditable, SoftDeletes, UsesTenantConnection; protected $table='ecommerce_categories'; protected $fillable=['parent_id','name','slug','description','image','is_active','sort_order','created_by']; protected function casts(): array{return ['is_active'=>'boolean','sort_order'=>'integer'];} public function parent(): BelongsTo{return $this->belongsTo(self::class,'parent_id');} }
