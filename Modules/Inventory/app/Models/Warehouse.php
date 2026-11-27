<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use SoftDeletes,UsesTenantConnection;

    protected $table = 'inventory_warehouses';

    protected $fillable = ['name', 'code', 'address', 'is_active', 'created_by'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
