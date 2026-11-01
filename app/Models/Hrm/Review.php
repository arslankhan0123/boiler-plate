<?php

declare(strict_types=1);

namespace App\Models\Hrm;

use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Setup\Models\Employee;

class Review extends Model
{
    use UsesTenantConnection;
    use \App\Models\Concerns\Auditable;

    protected $table = 'hrm_reviews';

    protected $fillable = ['employee_id', 'reviewer_id', 'reviewer_name', 'period', 'scores', 'overall', 'strengths', 'improvements', 'goals', 'status', 'employee_comment', 'shared_at', 'acknowledged_at'];

    public const CRITERIA = [
        'quality' => 'Quality of work', 'productivity' => 'Productivity', 'communication' => 'Communication',
        'teamwork' => 'Teamwork', 'initiative' => 'Initiative', 'punctuality' => 'Punctuality',
    ];

    public const STATUSES = ['draft' => 'muted', 'shared' => 'warning', 'acknowledged' => 'success'];

    protected function casts(): array
    {
        return ['scores' => 'array', 'overall' => 'float', 'shared_at' => 'datetime', 'acknowledged_at' => 'datetime'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }
}
