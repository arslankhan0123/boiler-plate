<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hrm;

use App\Models\Hrm\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\Setup\Models\Department;
use Modules\Setup\Models\Designation;

class JobController extends HrmController
{
    public function index(Request $request)
    {
        $this->need('hrm.manage_recruitment');

        return view('hrm.jobs.index', [
            'jobs' => Job::query()->with('department')->withCount([
                'applications', 'applications as new_count' => fn ($q) => $q->where('status', 'new'),
            ])->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))->latest()->paginate(15)->withQueryString(),
            'careersUrl' => route('careers.index', tenant('id')),
        ]);
    }

    public function create()
    {
        $this->need('hrm.manage_recruitment');

        return view('hrm.jobs.form', $this->formData(new Job(['status' => 'draft', 'positions' => 1, 'employment_type' => 'full_time'])));
    }

    public function store(Request $request)
    {
        $this->need('hrm.manage_recruitment');
        $data = $this->data($request);
        $data['slug'] = $this->slug($data['title']);
        $job = Job::create($data);

        return redirect()->route('hrm.jobs.index')->with('success', "Job \"{$job->title}\" created.");
    }

    public function edit(Job $job)
    {
        $this->need('hrm.manage_recruitment');

        return view('hrm.jobs.form', $this->formData($job));
    }

    public function update(Request $request, Job $job)
    {
        $this->need('hrm.manage_recruitment');
        $job->update($this->data($request));

        return redirect()->route('hrm.jobs.index')->with('success', 'Job updated.');
    }

    public function destroy(Job $job)
    {
        $this->need('hrm.manage_recruitment');
        if ($job->applications()->exists()) {
            $job->update(['status' => 'closed']);

            return back()->with('warning', 'This job has applications, so it was closed instead of deleted.');
        }
        $job->delete();

        return back()->with('success', 'Job deleted.');
    }

    private function formData(Job $job): array
    {
        return [
            'job' => $job,
            'departments' => Department::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'),
            'designations' => Designation::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'),
        ];
    }

    private function data(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'department_id' => ['nullable', Rule::exists((new Department())->getTable(), 'id')],
            'designation_id' => ['nullable', Rule::exists((new Designation())->getTable(), 'id')],
            'employment_type' => ['required', Rule::in(array_keys(config('hrm.employment_types')))],
            'location' => ['nullable', 'string', 'max:190'],
            'positions' => ['required', 'integer', 'min:1', 'max:999'],
            'salary_min' => ['nullable', 'numeric', 'min:0'],
            'salary_max' => ['nullable', 'numeric', 'gte:salary_min'],
            'description' => ['nullable', 'string', 'max:20000'],
            'requirements' => ['nullable', 'string', 'max:20000'],
            'status' => ['required', Rule::in(array_keys(Job::STATUSES))],
            'closes_at' => ['nullable', 'date'],
        ]);
    }

    private function slug(string $title): string
    {
        $base = Str::slug($title) ?: 'job';
        $slug = $base;
        $i = 2;
        while (Job::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
