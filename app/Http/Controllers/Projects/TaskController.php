<?php

declare(strict_types=1);

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Models\Projects\Task;
use App\Models\Projects\TaskComment;
use App\Support\Hrm\Hrm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Modules\Setup\Models\Employee;

/**
 * Kanban task board.
 *  projects.manage_tasks → sees/creates/assigns/edits every task.
 *  projects.view         → sees ONLY tasks assigned to their own employee record,
 *                          and can move them between columns + comment.
 */
class TaskController extends Controller
{
    private function canManage(): bool
    {
        return (bool) auth()->user()?->can('projects.manage_tasks');
    }

    private function needView(): void
    {
        abort_unless(auth()->user()?->can('projects.view') || $this->canManage(), 403);
    }

    /** Managers see everything; others only their own tasks. */
    private function visible()
    {
        $q = Task::query();
        if (! $this->canManage()) {
            $me = Hrm::me();
            $q->where('assignee_id', $me?->id ?? 0);
        }

        return $q;
    }

    private function findVisible(int $id): Task
    {
        return $this->visible()->findOrFail($id);
    }

    public function index(Request $request)
    {
        $this->needView();
        $manage = $this->canManage();
        abort_if(! $manage && ! Hrm::me(), 403, 'Your login is not linked to an employee profile. Please contact HR.');

        $tasks = $this->visible()->with('assignee')->withCount('comments')
            ->when($manage && $request->filled('assignee'), fn ($q) => $request->assignee === 'none'
                ? $q->whereNull('assignee_id') : $q->where('assignee_id', $request->assignee))
            ->when($request->filled('priority'), fn ($q) => $q->where('priority', $request->priority))
            ->when($request->filled('search'), fn ($q) => $q->where('title', 'like', "%{$request->search}%"))
            // Done column only shows the last 30 days to keep the board light.
            ->where(fn ($q) => $q->where('status', '!=', 'done')->orWhere('completed_at', '>=', now()->subDays(30)))
            ->orderBy('position')->orderByDesc('id')->get()->groupBy('status');

        return view('projects.tasks.index', [
            'columns' => Task::STATUSES,
            'tasks' => $tasks,
            'manage' => $manage,
            'employees' => $manage ? $this->assignableEmployees() : collect(),
            'stats' => [
                'open' => $this->visible()->where('status', '!=', 'done')->count(),
                'overdue' => $this->visible()->where('status', '!=', 'done')->whereDate('due_date', '<', today())->count(),
                'due_today' => $this->visible()->where('status', '!=', 'done')->whereDate('due_date', today())->count(),
                'done_week' => $this->visible()->where('status', 'done')->where('completed_at', '>=', now()->startOfWeek())->count(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($this->canManage(), 403);
        $data = $this->validated($request);
        // New tasks go to the top of their column: push the others down one.
        DB::transaction(function () use ($data) {
            Task::query()->where('status', $data['status'])->increment('position');
            Task::create($data + ['position' => 0, 'created_by' => auth()->id(), 'created_by_name' => auth()->user()->name,
                'completed_at' => $data['status'] === 'done' ? now() : null]);
        });

        return back()->with('success', 'Task created.');
    }

    public function show(int $task)
    {
        $this->needView();
        $task = $this->findVisible($task)->load(['assignee.designation', 'comments']);

        return view('projects.tasks.show', [
            'task' => $task,
            'manage' => $this->canManage(),
            'employees' => $this->canManage() ? $this->assignableEmployees() : collect(),
        ]);
    }

    public function update(Request $request, int $task)
    {
        abort_unless($this->canManage(), 403);
        $task = $this->findVisible($task);
        $data = $this->validated($request);
        $data['completed_at'] = $data['status'] === 'done' ? ($task->completed_at ?? now()) : null;
        $task->update($data);

        return back()->with('success', 'Task updated.');
    }

    /** Drag & drop: new status + order of the target column. Assignees can move their own tasks. */
    public function move(Request $request, int $task): JsonResponse
    {
        $this->needView();
        $task = $this->findVisible($task);
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Task::STATUSES))],
            'order' => ['array'],
            'order.*' => ['integer'],
        ]);

        DB::transaction(function () use ($task, $data) {
            $task->update([
                'status' => $data['status'],
                'completed_at' => $data['status'] === 'done' ? ($task->completed_at ?? now()) : null,
            ]);
            // Re-number only tasks this user can see in that column.
            $ids = $this->visible()->whereIn('id', $data['order'] ?? [])->pluck('id')->all();
            foreach (array_values(array_intersect($data['order'] ?? [], $ids)) as $i => $id) {
                Task::query()->whereKey($id)->update(['position' => $i]);
            }
        });

        return response()->json(['ok' => true, 'status' => $task->status, 'label' => Task::STATUSES[$task->status][0]]);
    }

    public function destroy(int $task)
    {
        abort_unless($this->canManage(), 403);
        $this->findVisible($task)->delete();

        return redirect()->route('projects.tasks.index')->with('success', 'Task deleted.');
    }

    public function comment(Request $request, int $task)
    {
        $this->needView();
        $task = $this->findVisible($task);
        $body = $request->validate(['body' => ['required', 'string', 'max:3000']])['body'];
        TaskComment::create(['task_id' => $task->id, 'user_id' => auth()->id(), 'user_name' => auth()->user()->name, 'body' => $body]);

        return back()->with('success', 'Comment added.');
    }

    private function assignableEmployees()
    {
        return Employee::query()->with('designation')->where('is_active', true)->orderBy('name')->get();
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:10000'],
            'status' => ['required', Rule::in(array_keys(Task::STATUSES))],
            'priority' => ['required', Rule::in(array_keys(Task::PRIORITIES))],
            'due_date' => ['nullable', 'date'],
            'assignee_id' => ['nullable', Rule::exists((new Employee())->getTable(), 'id')->whereNull('deleted_at')],
        ]);
    }
}
