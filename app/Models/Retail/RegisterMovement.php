<?php

declare(strict_types=1);

namespace App\Models\Retail;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;

class RegisterMovement extends Model
{
    use Auditable;
    use UsesTenantConnection;

    protected $table = 'rt_register_movements';

    protected $fillable = ['register_id', 'direction', 'amount', 'reason', 'created_by'];

    protected function casts(): array
    {
        return ['amount' => 'float'];
    }
}
