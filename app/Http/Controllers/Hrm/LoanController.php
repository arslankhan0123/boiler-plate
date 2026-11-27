<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hrm;

use App\Models\Hrm\Loan;
use App\Models\Hrm\SalaryComponent;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Setup\Models\Employee;

/** Loans / salary advances + recurring salary components (both feed payroll). */
class LoanController extends HrmController
{
    public function index(Request $request)
    {
        $this->need('hrm.process_payroll');
        $status = $request->input('status', 'active');

        return view('hrm.loans.index', [
            'status' => $status,
            'loans' => Loan::query()->with('employee.department')
                ->when($status !== 'all', fn ($q) => $q->where('status', $status))->latest()->paginate(20)->withQueryString(),
            'employees' => Employee::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'),
            'totals' => [
                'outstanding' => Loan::query()->where('status', 'active')->get()->sum(fn ($l) => $l->balance()),
                'monthly' => Loan::query()->where('status', 'active')->sum('installment'),
                'active' => Loan::query()->where('status', 'active')->count(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $this->need('hrm.process_payroll');
        $data = $request->validate([
            'employee_id' => ['required', Rule::exists((new Employee())->getTable(), 'id')],
            'type' => ['required', Rule::in(['loan', 'advance'])],
            'amount' => ['required', 'numeric', 'min:1'],
            'installment' => ['required', 'numeric', 'min:1', 'lte:amount'],
            'start_month' => ['required', 'date_format:Y-m'],
            'reason' => ['nullable', 'string', 'max:190'],
        ]);
        $data['start_month'] = $data['start_month'].'-01';
        Loan::create($data + ['approved_by' => auth()->id(), 'status' => 'active']);

        return back()->with('success', 'Loan recorded. Installments will be deducted in payroll.');
    }

    public function close(Loan $loan)
    {
        $this->need('hrm.process_payroll');
        $loan->update(['status' => 'closed']);

        return back()->with('success', 'Loan closed — no further deductions.');
    }

    /* ── Recurring salary components (on the employee profile) ── */

    public function storeComponent(Request $request, Employee $employee)
    {
        $this->need('hrm.process_payroll');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::in(['earning', 'deduction'])],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);
        SalaryComponent::create($data + ['employee_id' => $employee->id, 'is_active' => true]);

        return back()->with('success', "{$data['name']} added to {$employee->name}'s salary.");
    }

    public function destroyComponent(Employee $employee, SalaryComponent $component)
    {
        $this->need('hrm.process_payroll');
        abort_unless($component->employee_id === $employee->id, 404);
        $component->delete();

        return back()->with('success', 'Salary component removed.');
    }
}
