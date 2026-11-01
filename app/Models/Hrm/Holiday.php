<?php

declare(strict_types=1);

namespace App\Models\Hrm;

use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use UsesTenantConnection;
    use \App\Models\Concerns\Auditable;

    protected $table = 'hrm_holidays';

    protected $fillable = ['name', 'date'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }
}
