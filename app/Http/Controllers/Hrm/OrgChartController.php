<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hrm;

use Modules\Setup\Models\Designation;
use Modules\Setup\Models\Employee;

/** Organization chart built from the designation "reports to" tree. */
class OrgChartController extends HrmController
{
    public function __invoke()
    {
        $this->need('hrm.view_employees');

        $designations = Designation::query()->with('department')->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
        $employees = Employee::query()->where('is_active', true)->orderBy('name')->get()->groupBy('designation_id');
        $children = $designations->groupBy(fn ($d) => $d->parent_id ?: 0);
        $ids = $designations->pluck('id')->all();

        // Roots: no parent, or parent missing/inactive.
        $roots = $designations->filter(fn ($d) => ! $d->parent_id || ! in_array($d->parent_id, $ids, true));

        return view('hrm.org-chart', compact('roots', 'children', 'employees'));
    }
}
