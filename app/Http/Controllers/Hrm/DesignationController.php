<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hrm;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Setup\Models\Department;
use Modules\Setup\Models\Designation;

class DesignationController extends HrmController
{
    public function index()
    {
        $this->need('hrm.manage_org');

        return view('hrm.designations.index', [
            'designations' => Designation::query()->with(['department', 'parent'])->withCount('employees')->orderBy('name')->get(),
            'departments' => Department::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'),
            'editing' => request('edit') ? Designation::query()->find(request('edit')) : null,
        ]);
    }

    public function store(Request $request)
    {
        $this->need('hrm.manage_org');
        $data = $this->data($request);
        $data['parent_id'] = ! empty($data['parent_id']) ? (int) $data['parent_id'] : null;
        Designation::create($data + ['created_by' => auth()->id()]);

        return back()->with('success', 'Designation created.');
    }

    public function update(Request $request, Designation $designation)
    {
        $this->need('hrm.manage_org');
        $data = $this->data($request, $designation);
        $data['parent_id'] = ! empty($data['parent_id']) ? (int) $data['parent_id'] : null;
        if ($this->wouldLoop($designation, $data['parent_id'])) {
            return back()->withInput()->with('error', 'A designation cannot report to itself or to someone below it in the chart.');
        }
        $designation->update($data);

        return redirect()->route('hrm.designations.index')->with('success', 'Designation updated.');
    }

    public function destroy(Designation $designation)
    {
        $this->need('hrm.manage_org');
        if ($designation->employees()->exists()) {
            return back()->with('error', 'Employees still hold this designation.');
        }
        Designation::query()->where('parent_id', $designation->id)->update(['parent_id' => $designation->parent_id]);
        $designation->delete();

        return back()->with('success', 'Designation deleted. Its reports now report one level up.');
    }

    private function data(Request $request, ?Designation $self = null): array
    {
        $d = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['nullable', 'string', 'max:30'],
            'department_id' => ['required', Rule::exists((new Department())->getTable(), 'id')],
            'parent_id' => ['nullable', Rule::exists((new Designation())->getTable(), 'id'), Rule::notIn([$self?->id])],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);
        $d['is_active'] = $request->boolean('is_active', true);

        return $d;
    }

    private function wouldLoop(Designation $self, ?int $parentId): bool
    {
        $seen = 0;
        // Walk up the chain from the new parent; reaching $self means a cycle.
        while ($parentId && $seen++ < 100) {
            if ((int) $parentId === (int) $self->id) {
                return true;
            }
            $next = Designation::query()->whereKey($parentId)->value('parent_id');
            $parentId = $next !== null ? (int) $next : null;
        }

        return false;
    }
}
