<?php

declare(strict_types=1);

namespace App\Models\Projects;

use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Setup\Models\Employee;

class Task extends Model
{
    use SoftDeletes;
    use UsesTenantConnection;

    protected $table = 'prj_tasks';

    protected $fillable = ['title', 'description', 'status', 'priority', 'due_date', 'assignee_id', 'created_by', 'created_by_name', 'position', 'completed_at'];

    /** Board columns, in order. */
    public const STATUSES = [
        'todo' => ['To Do', '#64748b', 'bi-circle'],
        'in_progress' => ['In Progress', '#4f46e5', 'bi-play-circle'],
        'review' => ['In Review', '#d97706', 'bi-eye'],
        'done' => ['Done', '#059669', 'bi-check-circle'],
    ];

    public const PRIORITIES = [
        'low' => ['Low', 'muted'], 'medium' => ['Medium', 'primary'], 'high' => ['High', 'warning'], 'urgent' => ['Urgent', 'danger'],
    ];

    protected function casts(): array
    {
        return ['due_date' => 'date', 'completed_at' => 'datetime', 'position' => 'integer'];
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assignee_id')->withTrashed();
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class)->oldest();
    }

    public function isOverdue(): bool
    {
        return $this->due_date && $this->status !== 'done' && $this->due_date->endOfDay()->isPast();
    }
}
