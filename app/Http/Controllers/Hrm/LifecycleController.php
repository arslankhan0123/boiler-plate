<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hrm;

use App\Models\Hrm\ChecklistItem;
use App\Models\Hrm\EmployeeExit;
use App\Models\Hrm\Loan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Modules\Setup\Models\Employee;

/** Onboarding / offboarding checklists and the exit (resignation / termination) process. */
class LifecycleController extends HrmController
{
    public function index()
    {
        $this->need('hrm.manage_lifecycle');

        $progress = fn (string $type) => ChecklistItem::query()->where('type', $type)
            ->select('employee_id', DB::raw('COUNT(*) total'), DB::raw('SUM(done_at IS NOT NULL) done'))
            ->groupBy('employee_id')->havingRaw('SUM(done_at IS NOT NULL) < COUNT(*)')->get()->keyBy('employee_id');

        $onboarding = $progress('onboarding');

        return view('hrm.lifecycle.index', [
            'onboarding' => Employee::query()->with(['designation', 'department'])->whereIn('id', $onboarding->keys())->get()
                ->each(fn ($e) => $e->progress = $onboarding[$e->id]),
            'exits' => EmployeeExit::query()->with('employee.designation')->where('status', 'in_progress')->orderBy('last_working_day')->get(),
            'offboarding' => $progress('offboarding'),
            'recentExits' => EmployeeExit::query()->with('employee')->whereIn('status', ['completed', 'withdrawn'])->latest('updated_at')->limit(10)->get(),
            'newJoiners' => Employee::query()->where('is_active', true)->whereDate('join_date', '>=', today()->subDays(60))
                ->whereNotIn('id', ChecklistItem::query()->where('type', 'onboarding')->pluck('employee_id'))->orderByDesc('join_date')->get(),
        ]);
    }

    /** Create the onboarding checklist for an employee (from config/hrm.php). */
    public function startOnboarding(Employee $employee)
    {
        $this->need('hrm.manage_lifecycle');
        $this->seedChecklist($employee, 'onboarding');

        return back()->with('success', "Onboarding checklist created for {$employee->name}.");
    }

    public function toggle(ChecklistItem $item)
    {
        $this->need('hrm.manage_lifecycle');
        $item->update($item->done_at ? ['done_at' => null, 'done_by' => null] : ['done_at' => now(), 'done_by' => auth()->user()->name]);

        return back();
    }

    public function addItem(Request $request, Employee $employee)
    {
        $this->need('hrm.manage_lifecycle');
        $d = $request->validate(['type' => ['required', Rule::in(['onboarding', 'offboarding'])], 'title' => ['required', 'string', 'max:190']]);
        ChecklistItem::create($d + ['employee_id' => $employee->id, 'sort' => 999]);

        return back()->with('success', 'Checklist item added.');
    }

    public function startExit(Request $request, Employee $employee)
    {
        $this->need('hrm.manage_lifecycle');
        abort_if(EmployeeExit::query()->where('employee_id', $employee->id)->where('status', 'in_progress')->exists(), 422, 'An exit is already in progress.');
        $d = $request->validate([
            'type' => ['required', Rule::in(array_keys(EmployeeExit::TYPES))],
            'notice_date' => ['required', 'date'],
            'last_working_day' => ['required', 'date', 'after_or_equal:notice_date'],
            'reason' => ['nullable', 'string', 'max:3000'],
        ]);
        EmployeeExit::create($d + ['employee_id' => $employee->id, 'status' => 'in_progress']);
        $this->seedChecklist($employee, 'offboarding');

        return back()->with('success', "Exit started for {$employee->name}. Last working day: ".\Illuminate\Support\Carbon::parse($d['last_working_day'])->format('d M Y').'.');
    }

    /** Finish the exit: deactivate the employee and remove their login. */
    public function completeExit(Request $request, EmployeeExit $exit)
    {
        $this->need('hrm.manage_lifecycle');
        abort_unless($exit->status === 'in_progress', 422);
        $employee = $exit->employee;

        $openLoans = Loan::query()->where('employee_id', $employee->id)->where('status', 'active')->get()->sum(fn ($l) => $l->balance());
        if ($openLoans > 0 && ! $request->boolean('force')) {
            return back()->with('warning', 'This employee still owes '.\App\Support\Hrm\Hrm::money($openLoans).' in loans. Settle them first, or confirm to complete anyway.')
                ->with('confirmExit', $exit->id);
        }

        DB::transaction(function () use ($exit, $employee, $request) {
            $exit->update(['status' => 'completed', 'completed_at' => now(), 'exit_interview' => $request->input('exit_interview', $exit->exit_interview)]);
            if ($employee->user_id && $employee->user_id !== auth()->id()) {
                User::query()->whereKey($employee->user_id)->where('tenant_id', auth()->user()->tenant_id)->forceDelete();
                $employee->user_id = null;
            }
            $employee->is_active = false;
            $employee->save();
        });

        return back()->with('success', "{$employee->name} has left the company. Their login was removed.");
    }

    public function withdrawExit(EmployeeExit $exit)
    {
        $this->need('hrm.manage_lifecycle');
        abort_unless($exit->status === 'in_progress', 422);
        $exit->update(['status' => 'withdrawn']);
        ChecklistItem::query()->where('employee_id', $exit->employee_id)->where('type', 'offboarding')->delete();

        return back()->with('success', 'Exit withdrawn.');
    }

    private function seedChecklist(Employee $employee, string $type): void
    {
        if (ChecklistItem::query()->where('employee_id', $employee->id)->where('type', $type)->exists()) {
            return;
        }
        foreach (config("hrm.{$type}_checklist", []) as $i => $title) {
            ChecklistItem::create(['employee_id' => $employee->id, 'type' => $type, 'title' => $title, 'sort' => $i]);
        }
    }
}
