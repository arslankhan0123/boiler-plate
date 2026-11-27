<?php

declare(strict_types=1);

namespace App\Models\Hrm;

use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Setup\Models\Employee;

class Payslip extends Model
{
    use UsesTenantConnection;
    use \App\Models\Concerns\Auditable;

    protected $table = 'hrm_payslips';

    protected $fillable = ['payroll_run_id', 'employee_id', 'number', 'basic_salary', 'allowances', 'bonus', 'unpaid_days',
        'unpaid_deduction', 'other_deductions', 'gross', 'net', 'present_days', 'note',
        'component_earnings', 'component_deductions', 'loan_deductions'];

    protected function casts(): array
    {
        return collect(['basic_salary', 'allowances', 'bonus', 'unpaid_days', 'unpaid_deduction', 'other_deductions', 'gross', 'net',
            'component_earnings', 'component_deductions', 'loan_deductions'])
            ->mapWithKeys(fn ($c) => [$c => 'float'])->all();
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    public function lines(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PayslipLine::class);
    }

    public function totalDeductions(): float
    {
        return round($this->unpaid_deduction + $this->other_deductions + $this->component_deductions + $this->loan_deductions, 2);
    }

    /** gross = basic + allowances + bonus + recurring earnings; net = gross − all deductions */
    public function recalc(): void
    {
        $this->gross = round($this->basic_salary + $this->allowances + $this->bonus + $this->component_earnings, 2);
        $this->net = round(max(0, $this->gross - $this->totalDeductions()), 2);
    }
}
