<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hrm;

use Illuminate\Http\Request;
use Modules\Setup\Models\Department;

class DepartmentController extends HrmController
{
    public function index()
    {
        $this->need('hrm.manage_org');

        return view('hrm.departments.index', [
            'departments' => Department::query()->withCount(['employees', 'designations'])->orderBy('name')->get(),
            'editing' => request('edit') ? Department::query()->find(request('edit')) : null,
        ]);
    }

    public function store(Request $request)
    {
        $this->need('hrm.manage_org');
        Department::create($this->data($request) + ['created_by' => auth()->id()]);

        return back()->with('success', 'Department created.');
    }

    public function update(Request $request, Department $department)
    {
        $this->need('hrm.manage_org');
        $department->update($this->data($request));

        return redirect()->route('hrm.departments.index')->with('success', 'Department updated.');
    }

    public function destroy(Department $department)
    {
        $this->need('hrm.manage_org');
        if ($department->employees()->exists()) {
            return back()->with('error', 'Move this department\'s employees first.');
        }
        $department->delete();

        return back()->with('success', 'Department deleted.');
    }

    private function data(Request $request): array
    {
        $d = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['nullable', 'string', 'max:30'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $d['is_active'] = $request->boolean('is_active', true);

        return $d;
    }
}
