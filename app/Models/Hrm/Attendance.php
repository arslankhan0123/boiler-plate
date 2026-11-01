<?php

declare(strict_types=1);

namespace App\Models\Hrm;

use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Setup\Models\Employee;

class Attendance extends Model
{
    use UsesTenantConnection;
    use \App\Models\Concerns\Auditable;

    protected $table = 'hrm_attendances';

    protected $fillable = ['employee_id', 'date', 'check_in', 'check_out', 'status', 'worked_minutes', 'note', 'marked_by'];

    public const STATUSES = [
        'present' => ['Present', 'success'], 'late' => ['Late', 'warning'], 'half_day' => ['Half day', 'warning'],
        'absent' => ['Absent', 'danger'], 'on_leave' => ['On leave', 'primary'], 'holiday' => ['Holiday', 'muted'],
    ];

    protected function casts(): array
    {
        return ['date' => 'date', 'worked_minutes' => 'integer'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function workedLabel(): string
    {
        return $this->worked_minutes ? intdiv($this->worked_minutes, 60).'h '.($this->worked_minutes % 60).'m' : '—';
    }
}
