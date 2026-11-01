<?php

declare(strict_types=1);

namespace App\Models\Hrm;

use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Setup\Models\Employee;

class Loan extends Model
{
    use UsesTenantConnection;
    use \App\Models\Concerns\Auditable;

    protected $table = 'hrm_loans';

    protected $fillable = ['employee_id', 'type', 'amount', 'installment', 'repaid', 'start_month', 'status', 'reason', 'approved_by'];

    protected function casts(): array
    {
        return ['amount' => 'float', 'installment' => 'float', 'repaid' => 'float', 'start_month' => 'date'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    public function balance(): float
    {
        return round(max(0, $this->amount - $this->repaid), 2);
    }

    /** Installment due for a payroll month (Y-m), or 0. */
    public function dueFor(string $month): float
    {
        if ($this->status !== 'active' || $this->start_month->format('Y-m') > $month) {
            return 0.0;
        }

        return round(min($this->installment, $this->balance()), 2);
    }
}
