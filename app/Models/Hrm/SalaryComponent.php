<?php

declare(strict_types=1);

namespace App\Models\Hrm;

use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Setup\Models\Employee;

class SalaryComponent extends Model
{
    use UsesTenantConnection;

    protected $table = 'hrm_salary_components';

    protected $fillable = ['employee_id', 'name', 'type', 'amount', 'is_active'];

    protected function casts(): array
    {
        return ['amount' => 'float', 'is_active' => 'boolean'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
