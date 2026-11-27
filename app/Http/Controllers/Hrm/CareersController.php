<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Hrm\Job;
use App\Models\Hrm\JobApplication;
use App\Models\Tenant;
use Illuminate\Http\Request;

/** Public careers site per tenant: /careers/{tenant}. No login. */
class CareersController extends Controller
{
    private function boot(string $tenantId): Tenant
    {
        $tenant = Tenant::query()->find($tenantId);
        abort_unless($tenant && $tenant->is_active && $tenant->hasModule('hrm') && $tenant->featureEnabled('hrm', 'jobs'), 404);
        tenancy()->initialize($tenant);

        return $tenant;
    }

    public function index(string $tenant)
    {
        $t = $this->boot($tenant);

        return view('hrm.careers.index', [
            'tenant' => $t,
            'jobs' => Job::query()->with('department')->where('status', 'open')
                ->where(fn ($q) => $q->whereNull('closes_at')->orWhereDate('closes_at', '>=', today()))->latest()->get(),
        ]);
    }

    public function show(string $tenant, string $slug)
    {
        $t = $this->boot($tenant);
        $job = Job::query()->with('department')->where('slug', $slug)->firstOrFail();
        abort_unless($job->status !== 'draft', 404);

        return view('hrm.careers.show', ['tenant' => $t, 'job' => $job]);
    }

    public function apply(Request $request, string $tenant, string $slug)
    {
        $this->boot($tenant);
        $job = Job::query()->where('slug', $slug)->firstOrFail();
        abort_unless($job->isAcceptingApplications(), 422, 'This job is no longer accepting applications.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:32'],
            'cover_letter' => ['nullable', 'string', 'max:5000'],
            'resume' => ['required', 'file', 'max:5120', 'mimes:pdf,doc,docx'],
            'website' => ['prohibited'], // honeypot
        ]);

        if (JobApplication::query()->where('job_id', $job->id)->where('email', $data['email'])->exists()) {
            return back()->withInput()->with('toast', ['type' => 'warning', 'title' => 'Already applied', 'message' => 'We already have your application for this job.']);
        }

        JobApplication::create([
            'job_id' => $job->id, 'name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'] ?? null,
            'cover_letter' => $data['cover_letter'] ?? null, 'status' => 'new',
            'resume' => $request->file('resume')->store(tenant_upload_path('hrm/resumes'), 'local'),
        ]);

        return redirect()->route('careers.show', [$tenant, $slug])
            ->with('toast', ['type' => 'success', 'title' => 'Application sent', 'message' => 'Thank you! Our team will get back to you soon.']);
    }
}
