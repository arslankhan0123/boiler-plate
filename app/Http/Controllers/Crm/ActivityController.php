<?php

declare(strict_types=1);

namespace App\Http\Controllers\Crm;

use App\Models\Crm\Activity;
use Illuminate\Http\Request;

/** Activities & follow-ups: calls, emails, meetings, tasks — with due dates. */
class ActivityController extends CrmController
{
    public function index(Request $request)
    {
        $this->need('crm.view', 'crm.manage_activities');
        $filter = $request->filter ?: 'open';
        $q = Activity::query()->with('owner')
            ->when($request->type, fn ($x, $v) => $x->where('type', $v))
            ->when($request->owner_id, fn ($x, $v) => $x->where('owner_id', $v));
        $q->when($filter === 'open', fn ($x) => $x->where('done', false))
            ->when($filter === 'done', fn ($x) => $x->where('done', true))
            ->when($filter === 'overdue', fn ($x) => $x->where('done', false)->whereNotNull('due_at')->where('due_at', '<', now()))
            ->when($filter === 'today', fn ($x) => $x->where('done', false)->whereDate('due_at', today()));

        $activities = $q->orderByRaw('done asc')->orderByRaw('due_at is null, due_at asc')->latest('id')->paginate(30)->withQueryString();

        return view('crm.activities.index', [
            'activities' => $activities,
            'types' => Activity::TYPES,
            'filter' => $filter,
            'owners' => $this->owners(),
            'openCount' => Activity::where('done', false)->count(),
            'overdueCount' => Activity::where('done', false)->whereNotNull('due_at')->where('due_at', '<', now())->count(),
            'canEdit' => $this->can('crm.manage_activities'),
        ]);
    }

    public function store(Request $request)
    {
        $this->need('crm.manage_activities');
        Activity::create($this->data($request) + ['created_by' => auth()->id()]);

        return back()->with('success', 'Activity added.');
    }

    public function update(Request $request, Activity $activity)
    {
        $this->need('crm.manage_activities');
        $activity->update($this->data($request));

        return back()->with('success', 'Activity saved.');
    }

    /** Toggle done / not-done. */
    public function toggle(Activity $activity)
    {
        $this->need('crm.manage_activities');
        $activity->update(['done' => ! $activity->done, 'done_at' => $activity->done ? null : now()]);

        return back()->with('success', $activity->done ? 'Marked done.' : 'Reopened.');
    }

    public function destroy(Activity $activity)
    {
        $this->need('crm.manage_activities');
        $activity->delete();

        return back()->with('success', 'Activity deleted.');
    }

    private function data(Request $request): array
    {
        return $request->validate([
            'type' => ['required', 'in:call,email,meeting,task'],
            'subject' => ['required', 'string', 'max:255'],
            'due_at' => ['nullable', 'date'],
            'related_type' => ['nullable', 'in:lead,opportunity,contact,company'],
            'related_id' => ['nullable', 'integer'],
            'owner_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
