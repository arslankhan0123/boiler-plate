<?php

declare(strict_types=1);

namespace App\Models\Settings;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;

class ShippingMethod extends Model
{
    use Auditable;
    use UsesTenantConnection;

    protected $table = 'shipping_methods';

    protected $fillable = ['name', 'fee', 'free_over', 'delivery_time', 'is_active'];

    protected function casts(): array
    {
        return ['fee' => 'float', 'free_over' => 'float', 'is_active' => 'boolean'];
    }

}
