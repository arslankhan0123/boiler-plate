<?php

declare(strict_types=1);

namespace App\Models\Hrm;

use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobApplication extends Model
{
    use UsesTenantConnection;

    protected $table = 'hrm_job_applications';

    protected $fillable = ['job_id', 'name', 'email', 'phone', 'resume', 'cover_letter', 'status', 'rating', 'interview_at', 'notes', 'employee_id',
        'interviewer_id', 'interview_mode', 'interview_location', 'interview_result', 'interview_rating', 'interview_feedback', 'feedback_at'];

    public const MODES = ['onsite' => 'On-site', 'online' => 'Online / video', 'phone' => 'Phone'];

    public const RESULTS = ['pass' => ['Recommend', 'success'], 'hold' => ['On hold', 'warning'], 'fail' => ['Not a fit', 'danger'], 'no_show' => ['No show', 'muted']];

    protected function casts(): array
    {
        return ['interview_at' => 'datetime', 'feedback_at' => 'datetime'];
    }

    /** Employee assigned to interview this candidate. */
    public function interviewer(): BelongsTo
    {
        return $this->belongsTo(\Modules\Setup\Models\Employee::class, 'interviewer_id');
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }
}
