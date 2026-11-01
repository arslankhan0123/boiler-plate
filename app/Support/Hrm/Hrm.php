<?php

declare(strict_types=1);

namespace App\Support\Hrm;

use App\Models\Hrm\Holiday;
use App\Models\Hrm\LeaveAllocation;
use App\Models\Hrm\LeaveRequest;
use App\Models\Hrm\LeaveType;
use Illuminate\Support\Carbon;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Modules\Setup\Models\Employee;

/** Shared HRM rules: who is "me", working days, leave balances, allocation. */
class Hrm
{
    private static array $me = [];

    /** The employee record linked to the logged-in user (null if none). */
    public static function me(): ?Employee
    {
        $id = auth()->id();
        if (! $id) {
            return null;
        }

        return self::$me[$id] ??= Employee::query()->where('user_id', $id)->first();
    }

    public static function meOrFail(): Employee
    {
        $me = self::me();
        abort_unless($me, 403, 'Your login is not linked to an employee profile. Please contact HR.');

        return $me;
    }

    /** Working dates between two dates (excludes weekend days + holidays). */
    public static function workingDates(Carbon $from, Carbon $to): Collection
    {
        if ($to->lt($from)) {
            return collect();
        }

        $weekend = config('hrm.weekend_days', [0]);
        $holidays = Holiday::query()->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->pluck('date')->map(fn ($d) => substr((string) $d, 0, 10))->all(); // pluck() returns raw strings

        return collect(CarbonPeriod::create($from->copy()->startOfDay(), $to->copy()->startOfDay()))
            ->map(fn (CarbonInterface $d) => Carbon::instance($d))
            ->reject(fn (Carbon $d) => in_array($d->dayOfWeek, $weekend, true) || in_array($d->toDateString(), $holidays, true))
            ->values();
    }

    public static function workingDayCount(Carbon $from, Carbon $to): int
    {
        return self::workingDates($from, $to)->count();
    }

    /**
     * Leave balance per type for an employee and year.
     *
     * @return Collection<int, array{type: LeaveType, allocated: float, used: float, pending: float, remaining: float}>
     */
    public static function balances(Employee $employee, int $year): Collection
    {
        $allocs = LeaveAllocation::query()->where('employee_id', $employee->id)->where('year', $year)->get()->keyBy('leave_type_id');
        $requests = LeaveRequest::query()->where('employee_id', $employee->id)
            ->whereYear('start_date', $year)->whereIn('status', ['approved', 'pending'])->get();

        return LeaveType::query()->where('is_active', true)->orderBy('name')->get()->map(function (LeaveType $type) use ($allocs, $requests) {
            $allocated = (float) (optional($allocs->get($type->id))->total() ?? 0);
            $used = (float) $requests->where('leave_type_id', $type->id)->where('status', 'approved')->sum('days');
            $pending = (float) $requests->where('leave_type_id', $type->id)->where('status', 'pending')->sum('days');

            return [
                'type' => $type,
                'allocated' => $allocated,
                'used' => $used,
                'pending' => $pending,
                'remaining' => $type->is_paid ? max(0, $allocated - $used - $pending) : null,
            ];
        });
    }

    /**
     * Allocate a year's leave to every active employee (idempotent).
     * Carry-forward = unused days from previous year, capped per type.
     */
    public static function allocateYear(int $year, ?int $employeeId = null): int
    {
        $types = LeaveType::query()->where('is_active', true)->where('is_paid', true)->get();
        $employees = Employee::query()->where('is_active', true)
            ->when($employeeId, fn ($q) => $q->whereKey($employeeId))->get();
        $count = 0;

        foreach ($employees as $employee) {
            $prev = $type = null;
            $prevBalances = self::balances($employee, $year - 1)->keyBy(fn ($b) => $b['type']->id);

            foreach ($types as $type) {
                // Pro-rate for employees who join during the year.
                $days = $type->days_per_year;
                $join = $employee->join_date ? Carbon::parse($employee->join_date) : null;
                if ($join && $join->year === $year) {
                    $days = round($type->days_per_year * (13 - $join->month) / 12 * 2) / 2;
                } elseif ($join && $join->year > $year) {
                    continue;
                }

                $carry = 0.0;
                if ($type->carry_forward && ($prev = $prevBalances->get($type->id)) && $prev['allocated'] > 0) {
                    $carry = min((float) $type->max_carry_forward, max(0, $prev['allocated'] - $prev['used']));
                }

                LeaveAllocation::query()->updateOrCreate(
                    ['employee_id' => $employee->id, 'leave_type_id' => $type->id, 'year' => $year],
                    ['allocated' => $days, 'carried_forward' => $carry],
                );
                $count++;
            }
        }

        return $count;
    }

    public static function money(float|int|string|null $amount): string
    {
        return config('hrm.currency').' '.number_format((float) $amount, 2);
    }

    /** "Ali Khan" → "AK" */
    public static function initials(string $name): string
    {
        return collect(explode(' ', trim($name)))->filter()->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->take(2)->join('');
    }
}
