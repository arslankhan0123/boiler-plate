<?php

declare(strict_types=1);

namespace App\Models\Hrm;

use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Setup\Models\Employee;

class ChecklistItem extends Model
{
    use UsesTenantConnection;

    protected $table = 'hrm_checklist_items';

    protected $fillable = ['employee_id', 'type', 'title', 'sort', 'done_at', 'done_by'];

    protected function casts(): array
    {
        return ['done_at' => 'datetime'];
    }
}
