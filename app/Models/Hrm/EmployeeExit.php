<?php

declare(strict_types=1);

namespace App\Models\Hrm;

use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Setup\Models\Employee;

class EmployeeExit extends Model
{
    use UsesTenantConnection;
    use \App\Models\Concerns\Auditable;

    protected $table = 'hrm_exits';

    protected $fillable = ['employee_id', 'type', 'notice_date', 'last_working_day', 'reason', 'status', 'exit_interview', 'completed_at'];

    public const TYPES = ['resignation' => 'Resignation', 'termination' => 'Termination', 'retirement' => 'Retirement', 'contract_end' => 'Contract end'];

    protected function casts(): array
    {
        return ['notice_date' => 'date', 'last_working_day' => 'date', 'completed_at' => 'datetime'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }
}
