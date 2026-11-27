<?php

declare(strict_types=1);

namespace Modules\Pos\Models;

use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;

class PosShift extends Model
{
    use UsesTenantConnection;

    protected $table = 'pos_shifts';

    protected $fillable = ['terminal_id', 'opened_by', 'closed_by', 'opening_cash', 'closing_cash', 'opened_at', 'closed_at', 'status', 'notes'];

    protected function casts(): array
    {
        return ['opening_cash' => 'decimal:2', 'closing_cash' => 'decimal:2', 'opened_at' => 'datetime', 'closed_at' => 'datetime'];
    }
}
