<?php

declare(strict_types=1);

namespace App\Models\Hrm;

use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Setup\Models\Employee;

class LeaveRequest extends Model
{
    use UsesTenantConnection;

    protected $table = 'hrm_leave_requests';

    protected $fillable = ['employee_id', 'leave_type_id', 'start_date', 'end_date', 'days', 'reason', 'status', 'reviewed_by', 'reviewed_at', 'review_note'];

    public const STATUSES = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger', 'cancelled' => 'muted'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date', 'reviewed_at' => 'datetime', 'days' => 'float'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id');
    }
}
