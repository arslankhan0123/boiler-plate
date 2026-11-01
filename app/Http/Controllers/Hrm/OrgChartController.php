<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hrm;

use Illuminate\Http\Request;
use Modules\Setup\Models\Designation;
use Modules\Setup\Models\Employee;

/**
 * Organization chart.
 *  people        (default) — who reports to whom: the manager picked on each
 *                employee, falling back to the designation chart.
 *  designations  — the designation "reports to" structure.
 */
class OrgChartController extends HrmController
{
    public function __invoke(Request $request)
    {
        $this->need('hrm.view_employees');
        $mode = $request->input('view') === 'designations' ? 'designations' : 'people';

        $designations = Designation::query()->with('department')->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
        $employees = Employee::query()->with(['designation', 'department'])->where('is_active', true)->orderBy('name')->get();

        if ($mode === 'designations') {
            $ids = $designations->pluck('id')->all();

            return view('hrm.org-chart', [
                'mode' => $mode,
                'roots' => $designations->filter(fn ($d) => ! $d->parent_id || ! in_array($d->parent_id, $ids, true)),
                'children' => $designations->groupBy(fn ($d) => $d->parent_id ?: 0),
                'employees' => $employees->groupBy('designation_id'),
            ]);
        }

        // Resolve each person's manager once (picked manager → designation fallback).
        $managerOf = [];
        foreach ($employees as $e) {
            $m = null;
            if ($e->manager_id && $employees->contains('id', $e->manager_id)) {
                $m = $e->manager_id;
            } elseif ($parent = $e->designation?->parent_id) {
                $m = $employees->where('designation_id', $parent)->where('id', '!=', $e->id)->sortBy('join_date')->first()?->id;
            }
            $managerOf[$e->id] = $m;
        }

        // Break any accidental cycles by making the loop's entry point a root.
        foreach (array_keys($managerOf) as $id) {
            $seen = [];
            for ($cur = $id; $cur && ! isset($seen[$cur]); $cur = $managerOf[$cur] ?? null) {
                $seen[$cur] = true;
            }
            if ($cur) {
                $managerOf[$cur] = null;
            }
        }

        $byManager = $employees->groupBy(fn ($e) => $managerOf[$e->id] ?? 0);

        return view('hrm.org-chart', [
            'mode' => $mode,
            'people' => $byManager,
            'peopleRoots' => $byManager->get(0, collect()),
        ]);
    }
}
