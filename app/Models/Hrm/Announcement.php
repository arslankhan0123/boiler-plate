<?php

declare(strict_types=1);

namespace App\Models\Hrm;

use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Setup\Models\Employee;

class Announcement extends Model
{
    use UsesTenantConnection;

    protected $table = 'hrm_announcements';

    protected $fillable = ['title', 'body', 'department_id', 'is_pinned', 'expires_at', 'author_name'];

    protected function casts(): array
    {
        return ['is_pinned' => 'boolean', 'expires_at' => 'date'];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(\Modules\Setup\Models\Department::class);
    }

    /** Live announcements visible to an employee (theirs + company-wide). */
    public function scopeVisibleTo($q, ?Employee $employee)
    {
        return $q->where(fn ($w) => $w->whereNull('expires_at')->orWhereDate('expires_at', '>=', today()))
            ->where(fn ($w) => $w->whereNull('department_id')->orWhere('department_id', $employee?->department_id ?? 0));
    }
}
