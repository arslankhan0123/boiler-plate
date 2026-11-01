<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hrm;

use App\Models\Hrm\Job;
use App\Models\Hrm\JobApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ApplicationController extends HrmController
{
    public function index(Request $request)
    {
        $this->need('hrm.manage_recruitment');

        return view('hrm.applications.index', [
            'applications' => JobApplication::query()->with('job')
                ->when($request->filled('job'), fn ($q) => $q->where('job_id', $request->job))
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
                ->when($request->filled('search'), fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', "%{$request->search}%")->orWhere('email', 'like', "%{$request->search}%")))
                ->latest()->paginate(20)->withQueryString(),
            'jobs' => Job::query()->orderBy('title')->pluck('title', 'id'),
            'counts' => JobApplication::query()->when($request->filled('job'), fn ($q) => $q->where('job_id', $request->job))
                ->select('status', DB::raw('COUNT(*) c'))->groupBy('status')->pluck('c', 'status'),
        ]);
    }

    public function show(JobApplication $application)
    {
        $this->need('hrm.manage_recruitment');

        return view('hrm.applications.show', ['application' => $application->load('job.department')]);
    }

    public function update(Request $request, JobApplication $application)
    {
        $this->need('hrm.manage_recruitment');
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(config('hrm.application_statuses')))],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'interview_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
        $application->update($data);

        if ($data['status'] === 'hired' && ! $application->employee_id && auth()->user()->can('hrm.manage_employees')) {
            return redirect()->route('hrm.employees.create', ['application' => $application->id])
                ->with('info', 'Candidate marked as hired — complete their employee profile.');
        }

        return back()->with('success', 'Application updated.');
    }

    public function resume(JobApplication $application)
    {
        $this->need('hrm.manage_recruitment');
        abort_unless($application->resume && Storage::disk('local')->exists($application->resume), 404);

        return Storage::disk('local')->download($application->resume, 'resume-'.\Illuminate\Support\Str::slug($application->name).'.'.pathinfo($application->resume, PATHINFO_EXTENSION));
    }

    public function destroy(JobApplication $application)
    {
        $this->need('hrm.manage_recruitment');
        if ($application->resume) {
            Storage::disk('local')->delete($application->resume);
        }
        $application->delete();

        return redirect()->route('hrm.applications.index')->with('success', 'Application deleted.');
    }
}
