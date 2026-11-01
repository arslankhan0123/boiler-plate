<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hrm;

use App\Models\Hrm\Attendance;
use App\Models\Hrm\LeaveRequest;
use App\Models\Hrm\PayrollRun;
use App\Models\Hrm\Payslip;
use App\Support\Hrm\Hrm;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Setup\Models\Employee;

class PayrollController extends HrmController
{
    public function index()
    {
        $this->need('hrm.process_payroll');

        return view('hrm.payroll.index', [
            'runs' => PayrollRun::query()->withCount('payslips')->orderByDesc('month')->paginate(12),
        ]);
    }

    /** Create or refresh (while draft) the payroll for a month. */
    public function generate(Request $request)
    {
        $this->need('hrm.process_payroll');
        $month = $request->validate(['month' => ['required', 'date_format:Y-m']])['month'];

        $run = PayrollRun::query()->firstOrCreate(['month' => $month], ['status' => 'draft', 'processed_by' => auth()->id()]);
        abort_unless($run->status === 'draft', 422, 'This month is already finalized.');

        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $workingDays = max(1, Hrm::workingDayCount($start, $end));

        DB::transaction(function () use ($run, $start, $end, $workingDays, $month) {
            $employees = Employee::query()->where('is_active', true)->whereDate('join_date', '<=', $end)->get();

            foreach ($employees as $i => $e) {
                // Days not payable: unpaid leave + absences + days before joining.
                $unpaidLeave = LeaveRequest::query()->with('type')->where('employee_id', $e->id)->where('status', 'approved')
                    ->whereDate('start_date', '<=', $end)->whereDate('end_date', '>=', $start)->get()
                    ->filter(fn ($l) => ! $l->type?->is_paid)
                    ->sum(fn ($l) => $l->days <= 0.5 ? $l->days : Hrm::workingDayCount(max($l->start_date, $start), min($l->end_date, $end)));
                $absent = Attendance::query()->where('employee_id', $e->id)->whereBetween('date', [$start, $end])->where('status', 'absent')->count();
                $halfDays = Attendance::query()->where('employee_id', $e->id)->whereBetween('date', [$start, $end])->where('status', 'half_day')->count();
                $notJoined = Carbon::parse($e->join_date)->gt($start) ? Hrm::workingDayCount($start, Carbon::parse($e->join_date)->subDay()) : 0;
                $present = Attendance::query()->where('employee_id', $e->id)->whereBetween('date', [$start, $end])
                    ->whereIn('status', ['present', 'late', 'half_day'])->count();

                $unpaidDays = min($workingDays, $unpaidLeave + $absent + $halfDays * 0.5 + $notJoined);
                $basic = (float) ($e->basic_salary ?? 0);

                $slip = Payslip::query()->firstOrNew(['payroll_run_id' => $run->id, 'employee_id' => $e->id]);
                $slip->fill([
                    'number' => $slip->number ?: sprintf('PS-%s-%04d', str_replace('-', '', $month), $e->id),
                    'basic_salary' => $basic,
                    'allowances' => (float) ($e->transport_allowance ?? 0),
                    'unpaid_days' => $unpaidDays,
                    'unpaid_deduction' => round($basic / $workingDays * $unpaidDays, 2),
                    'present_days' => $present,
                ]);
                $slip->recalc();
                $slip->save();
            }

            // Employees no longer active are removed from a draft.
            Payslip::query()->where('payroll_run_id', $run->id)->whereNotIn('employee_id', $employees->pluck('id'))->delete();
            $run->update(['working_days' => $workingDays, 'processed_by' => auth()->id()]);
            $run->recalcTotals();
        });

        return redirect()->route('hrm.payroll.show', $run)->with('success', "Payroll for {$run->label()} generated.");
    }

    public function show(PayrollRun $run)
    {
        $this->need('hrm.process_payroll');

        return view('hrm.payroll.show', [
            'run' => $run,
            'payslips' => $run->payslips()->with('employee.department')->get()->sortBy('employee.name'),
        ]);
    }

    public function updatePayslip(Request $request, Payslip $payslip)
    {
        $this->need('hrm.process_payroll');
        abort_unless($payslip->run->status === 'draft', 422, 'Finalized payroll cannot be changed.');
        $payslip->fill($request->validate([
            'bonus' => ['nullable', 'numeric', 'min:0'],
            'other_deductions' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:190'],
        ]));
        $payslip->recalc();
        $payslip->save();
        $payslip->run->recalcTotals();

        return back()->with('success', "Payslip {$payslip->number} updated.");
    }

    public function finalize(PayrollRun $run)
    {
        $this->need('hrm.process_payroll');
        abort_unless($run->status === 'draft', 422);
        $run->update(['status' => 'finalized', 'finalized_at' => now()]);

        return back()->with('success', 'Payroll finalized. Employees can now see their payslips.');
    }

    public function markPaid(PayrollRun $run)
    {
        $this->need('hrm.process_payroll');
        abort_unless($run->status === 'finalized', 422);
        $run->update(['status' => 'paid', 'paid_at' => now()]);

        return back()->with('success', 'Payroll marked as paid.');
    }

    public function destroy(PayrollRun $run)
    {
        $this->need('hrm.process_payroll');
        abort_unless($run->status === 'draft', 422, 'Only draft payroll can be deleted.');
        $run->payslips()->delete();
        $run->delete();

        return redirect()->route('hrm.payroll.index')->with('success', 'Draft payroll deleted.');
    }

    /** Printable payslip (invoice). HR, or the employee for their own finalized slip. */
    public function payslip(Payslip $payslip)
    {
        $payslip->load(['run', 'employee.department', 'employee.designation']);
        $own = Hrm::me()?->id === $payslip->employee_id && $payslip->run->status !== 'draft';
        abort_unless($own || auth()->user()->can('hrm.process_payroll'), 403);

        return view('hrm.payroll.payslip', ['payslip' => $payslip, 'company' => tenant('name') ?? config('app.name')]);
    }
}
