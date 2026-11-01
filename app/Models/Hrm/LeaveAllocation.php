<?php

declare(strict_types=1);

namespace App\Models\Hrm;

use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Setup\Models\Employee;

class LeaveAllocation extends Model
{
    use UsesTenantConnection;

    protected $table = 'hrm_leave_allocations';

    protected $fillable = ['employee_id', 'leave_type_id', 'year', 'allocated', 'carried_forward'];

    protected function casts(): array
    {
        return ['allocated' => 'float', 'carried_forward' => 'float', 'year' => 'integer'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id');
    }

    public function total(): float
    {
        return $this->allocated + $this->carried_forward;
    }
}
