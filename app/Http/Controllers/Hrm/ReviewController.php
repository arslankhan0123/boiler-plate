<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hrm;

use App\Models\Hrm\Review;
use App\Support\Hrm\Hrm;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Setup\Models\Employee;

/**
 * Performance reviews.
 *  HR (hrm.manage_performance) → every employee.
 *  Managers → their direct reports (org chart), no extra permission needed.
 *  Employees → read + acknowledge their own shared reviews (My Reviews).
 */
class ReviewController extends HrmController
{
    private function isHr(): bool
    {
        return (bool) auth()->user()->can('hrm.manage_performance');
    }

    /** Employees this user may review. */
    private function reviewable()
    {
        if ($this->isHr()) {
            return Employee::query()->with('designation')->where('is_active', true)->orderBy('name')->get();
        }

        return Hrm::team();
    }

    private function guard(Review $review): void
    {
        abort_unless($this->isHr() || Hrm::team()->contains('id', $review->employee_id), 404);
    }

    public function index(Request $request)
    {
        $this->need('hrm.manage_performance', 'hrm.self_service');
        $ids = $this->reviewable()->pluck('id');
        abort_if($ids->isEmpty() && ! $this->isHr(), 403, 'You have no team members to review.');

        return view('hrm.reviews.index', [
            'reviews' => Review::query()->with('employee.designation')->whereIn('employee_id', $ids)
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
                ->latest()->paginate(20)->withQueryString(),
            'employees' => $this->reviewable(),
        ]);
    }

    public function create(Request $request)
    {
        $this->need('hrm.manage_performance', 'hrm.self_service');
        $employee = $this->reviewable()->firstWhere('id', (int) $request->employee);
        abort_unless($employee, 404, 'Pick one of your team members.');

        return view('hrm.reviews.form', ['review' => new Review(['employee_id' => $employee->id, 'period' => $this->defaultPeriod()]), 'employee' => $employee]);
    }

    public function store(Request $request)
    {
        $this->need('hrm.manage_performance', 'hrm.self_service');
        $employee = $this->reviewable()->firstWhere('id', (int) $request->input('employee_id'));
        abort_unless($employee, 403);
        $review = Review::create($this->data($request) + [
            'employee_id' => $employee->id, 'reviewer_id' => Hrm::me()?->id, 'reviewer_name' => auth()->user()->name, 'status' => 'draft',
        ]);
        $this->maybeShare($request, $review);

        return redirect()->route('hrm.reviews.show', $review)->with('success', 'Review saved.');
    }

    public function show(Review $review)
    {
        $this->guard($review);

        return view('hrm.reviews.show', ['review' => $review->load('employee.designation'), 'canEdit' => $review->status === 'draft']);
    }

    public function edit(Review $review)
    {
        $this->guard($review);
        abort_unless($review->status === 'draft', 422, 'Shared reviews can no longer be edited.');

        return view('hrm.reviews.form', ['review' => $review, 'employee' => $review->employee]);
    }

    public function update(Request $request, Review $review)
    {
        $this->guard($review);
        abort_unless($review->status === 'draft', 422);
        $review->update($this->data($request));
        $this->maybeShare($request, $review);

        return redirect()->route('hrm.reviews.show', $review)->with('success', 'Review updated.');
    }

    public function destroy(Review $review)
    {
        $this->guard($review);
        abort_unless($review->status === 'draft', 422, 'Only drafts can be deleted.');
        $review->delete();

        return redirect()->route('hrm.reviews.index')->with('success', 'Draft deleted.');
    }

    /* ── Employee side ── */

    public function mine()
    {
        $this->need('hrm.self_service');
        $me = Hrm::meOrFail();

        return view('hrm.me.reviews', [
            'reviews' => Review::query()->where('employee_id', $me->id)->whereIn('status', ['shared', 'acknowledged'])->latest('shared_at')->get(),
        ]);
    }

    public function acknowledge(Request $request, Review $review)
    {
        $this->need('hrm.self_service');
        abort_unless($review->employee_id === Hrm::meOrFail()->id && $review->status === 'shared', 404);
        $review->update([
            'status' => 'acknowledged', 'acknowledged_at' => now(),
            'employee_comment' => $request->validate(['employee_comment' => ['nullable', 'string', 'max:3000']])['employee_comment'] ?? null,
        ]);

        return back()->with('success', 'Thanks — your review is acknowledged.');
    }

    private function maybeShare(Request $request, Review $review): void
    {
        if ($request->boolean('share')) {
            $review->update(['status' => 'shared', 'shared_at' => now()]);
        }
    }

    private function defaultPeriod(): string
    {
        return (now()->month <= 6 ? 'H1 ' : 'H2 ').now()->year;
    }

    private function data(Request $request): array
    {
        $rules = [
            'period' => ['required', 'string', 'max:40'],
            'strengths' => ['nullable', 'string', 'max:5000'],
            'improvements' => ['nullable', 'string', 'max:5000'],
            'goals' => ['nullable', 'string', 'max:5000'],
            'scores' => ['array'],
        ];
        foreach (array_keys(Review::CRITERIA) as $c) {
            $rules["scores.$c"] = ['required', 'integer', Rule::in([1, 2, 3, 4, 5])];
        }
        $d = $request->validate($rules, ['scores.*.required' => 'Please score every criterion.']);
        $d['scores'] = array_map('intval', $d['scores']);
        $d['overall'] = round(array_sum($d['scores']) / max(1, count($d['scores'])), 1);

        return $d;
    }
}
