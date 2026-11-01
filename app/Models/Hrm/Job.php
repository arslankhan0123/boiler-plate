<?php

declare(strict_types=1);

namespace App\Models\Hrm;

use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Setup\Models\Department;
use Modules\Setup\Models\Designation;

class Job extends Model
{
    use UsesTenantConnection;

    protected $table = 'hrm_jobs';

    protected $fillable = ['title', 'slug', 'department_id', 'designation_id', 'employment_type', 'location', 'positions',
        'salary_min', 'salary_max', 'description', 'requirements', 'status', 'closes_at'];

    public const STATUSES = ['draft' => 'muted', 'open' => 'success', 'closed' => 'danger'];

    protected function casts(): array
    {
        return ['closes_at' => 'date', 'salary_min' => 'float', 'salary_max' => 'float'];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    public function isAcceptingApplications(): bool
    {
        return $this->status === 'open' && (! $this->closes_at || $this->closes_at->endOfDay()->isFuture());
    }
}
