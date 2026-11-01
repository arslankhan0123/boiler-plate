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

    protected $fillable = ['job_id', 'name', 'email', 'phone', 'resume', 'cover_letter', 'status', 'rating', 'interview_at', 'notes', 'employee_id'];

    protected function casts(): array
    {
        return ['interview_at' => 'datetime'];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }
}
