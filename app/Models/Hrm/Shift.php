<?php

declare(strict_types=1);

namespace App\Models\Hrm;

use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Setup\Models\Employee;

class Shift extends Model
{
    use UsesTenantConnection;

    protected $table = 'hrm_shifts';

    protected $fillable = ['name', 'start_time', 'end_time', 'grace_minutes', 'half_day_minutes', 'is_default'];

    protected function casts(): array
    {
        return ['is_default' => 'boolean', 'grace_minutes' => 'integer', 'half_day_minutes' => 'integer'];
    }

    public function label(): string
    {
        return $this->name.' ('.substr((string) $this->start_time, 0, 5).' – '.substr((string) $this->end_time, 0, 5).')';
    }

    /** The shift that applies to an employee (their own, else the default). */
    public static function forEmployee(?Employee $employee): ?self
    {
        static $default = false;
        if ($employee?->shift_id && ($own = self::query()->find($employee->shift_id))) {
            return $own;
        }
        if ($default === false) {
            $default = self::query()->where('is_default', true)->first();
        }

        return $default;
    }
}
