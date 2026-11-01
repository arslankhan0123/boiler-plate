<?php

declare(strict_types=1);

namespace App\Models\Hrm;

use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class PayrollRun extends Model
{
    use UsesTenantConnection;

    protected $table = 'hrm_payroll_runs';

    protected $fillable = ['month', 'status', 'working_days', 'total_gross', 'total_deductions', 'total_net', 'processed_by', 'finalized_at', 'paid_at'];

    public const STATUSES = ['draft' => 'warning', 'finalized' => 'primary', 'paid' => 'success'];

    protected function casts(): array
    {
        return ['finalized_at' => 'datetime', 'paid_at' => 'datetime', 'total_gross' => 'float', 'total_deductions' => 'float', 'total_net' => 'float'];
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    public function label(): string
    {
        return Carbon::createFromFormat('Y-m', $this->month)->format('F Y');
    }

    public function recalcTotals(): void
    {
        $this->update([
            'total_gross' => $this->payslips()->sum('gross'),
            'total_deductions' => $this->payslips()->sum('unpaid_deduction') + $this->payslips()->sum('other_deductions'),
            'total_net' => $this->payslips()->sum('net'),
        ]);
    }
}
