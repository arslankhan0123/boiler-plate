<?php

declare(strict_types=1);

namespace App\Models\Retail;

use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;

class VoidLog extends Model
{
    use UsesTenantConnection;

    protected $table = 'rt_void_logs';

    protected $fillable = ['item_id', 'item_name', 'qty', 'price', 'customer_id', 'reason', 'user_id'];

    protected function casts(): array
    {
        return ['qty' => 'float', 'price' => 'float'];
    }
}
