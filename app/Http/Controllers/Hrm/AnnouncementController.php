<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hrm;

use App\Models\Hrm\Announcement;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Setup\Models\Department;

class AnnouncementController extends HrmController
{
    public function index()
    {
        $this->need('hrm.manage_announcements');

        return view('hrm.announcements.index', [
            'announcements' => Announcement::query()->with('department')->orderByDesc('is_pinned')->latest()->paginate(15),
            'departments' => Department::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'),
            'editing' => request('edit') ? Announcement::query()->find(request('edit')) : null,
        ]);
    }

    public function store(Request $request)
    {
        $this->need('hrm.manage_announcements');
        Announcement::create($this->data($request) + ['author_name' => auth()->user()->name]);

        return back()->with('success', 'Announcement published.');
    }

    public function update(Request $request, Announcement $announcement)
    {
        $this->need('hrm.manage_announcements');
        $announcement->update($this->data($request));

        return redirect()->route('hrm.announcements.index')->with('success', 'Announcement updated.');
    }

    public function destroy(Announcement $announcement)
    {
        $this->need('hrm.manage_announcements');
        $announcement->delete();

        return back()->with('success', 'Announcement removed.');
    }

    private function data(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'body' => ['required', 'string', 'max:10000'],
            'department_id' => ['nullable', Rule::exists((new Department())->getTable(), 'id')],
            'expires_at' => ['nullable', 'date', 'after_or_equal:today'],
        ]) + ['is_pinned' => $request->boolean('is_pinned')];
    }
}
