<?php

declare(strict_types=1);

namespace App\Models\Hrm;

use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    use UsesTenantConnection;
    use \App\Models\Concerns\Auditable;

    protected $table = 'hrm_leave_types';

    protected $fillable = ['name', 'code', 'days_per_year', 'is_paid', 'carry_forward', 'max_carry_forward', 'color', 'is_active'];

    protected function casts(): array
    {
        return ['days_per_year' => 'float', 'max_carry_forward' => 'float', 'is_paid' => 'boolean', 'carry_forward' => 'boolean', 'is_active' => 'boolean'];
    }
}
