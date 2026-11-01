<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Notifications\AppNotice;
use Illuminate\Support\Facades\Crypt;
use App\Http\Controllers\Controller;
use App\Models\AuditTrail;
use App\Models\Settings\Branch;
use App\Models\Settings\PaymentMethod;
use App\Models\Settings\ShippingMethod;
use App\Models\Settings\TaxRate;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\Setup\Models\Currency;
use Modules\Setup\Models\Employee;
use Modules\Setup\Models\Lookup;
use Modules\Setup\Services\CurrencyService;

/**
 * Settings (core module, every tenant). View needs settings.view,
 * changes need settings.update, audit logs need settings.audit_logs.
 */
class SettingsController extends Controller
{
    private function need(string $permission): void
    {
        abort_unless(auth()->user()?->can($permission), 403, 'You do not have permission for this action.');
    }

    /* ───────────── Company profile ───────────── */

    public function company()
    {
        $this->need('settings.view');

        return view('settings.company', [
            's' => Settings::all(),
            'currencies' => Currency::query()->where('is_active', true)->orderBy('code')->pluck('code', 'code'),
            'canEdit' => auth()->user()->can('settings.update'),
        ]);
    }

    public function updateCompany(Request $request)
    {
        $this->need('settings.update');
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:150'],
            'legal_name' => ['nullable', 'string', 'max:190'],
            'email' => ['nullable', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'website' => ['nullable', 'url', 'max:190'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'ntn' => ['nullable', 'string', 'max:40'],
            'strn' => ['nullable', 'string', 'max:40'],
            'fiscal_year_start' => ['required', 'integer', 'between:1,12'],
            'date_format' => ['required', Rule::in(['d M Y', 'd/m/Y', 'm/d/Y', 'Y-m-d'])],
            'default_currency' => ['nullable', 'string', 'max:3'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
        ]);

        if ($request->hasFile('logo') || $request->boolean('remove_logo')) {
            if ($old = Settings::get('logo')) {
                Storage::disk('public')->delete($old);
            }
            $data['logo'] = $request->hasFile('logo') ? $request->file('logo')->store(tenant_upload_path('settings'), 'public') : null;
        } else {
            unset($data['logo']);
        }
        unset($data['remove_logo']);

        Settings::set($data);

        return back()->with('success', 'Company profile saved.');
    }

    /* ───────────── Branches ───────────── */

    public function branches()
    {
        $this->need('settings.view');

        return view('settings.branches', [
            'branches' => Branch::query()->with('manager')->orderByDesc('is_head_office')->orderBy('name')->get(),
            'employees' => class_exists(Employee::class) && \Illuminate\Support\Facades\Schema::hasTable('employees')
                ? Employee::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id') : collect(),
            'editing' => request('edit') ? Branch::query()->find(request('edit')) : null,
            'canEdit' => auth()->user()->can('settings.update'),
        ]);
    }

    public function storeBranch(Request $request)
    {
        $this->need('settings.update');
        $branch = Branch::create($this->branchData($request));
        $this->singleHeadOffice($branch);

        return back()->with('success', 'Branch added.');
    }

    public function updateBranch(Request $request, Branch $branch)
    {
        $this->need('settings.update');
        $branch->update($this->branchData($request));
        $this->singleHeadOffice($branch);

        return redirect()->route('settings.branches')->with('success', 'Branch updated.');
    }

    public function destroyBranch(Branch $branch)
    {
        $this->need('settings.update');
        $branch->delete();

        return back()->with('success', 'Branch removed.');
    }

    private function singleHeadOffice(Branch $branch): void
    {
        if ($branch->is_head_office) {
            Branch::query()->whereKeyNot($branch->id)->update(['is_head_office' => false]);
        }
    }

    private function branchData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['nullable', 'string', 'max:30'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:190'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'manager_id' => ['nullable', 'integer'],
        ]) + ['is_head_office' => $request->boolean('is_head_office'), 'is_active' => $request->boolean('is_active', true)];
    }

    /* ───────────── Taxes & currencies ───────────── */

    public function taxes()
    {
        $this->need('settings.view');
        // The master currency list lives in the central DB — fill it once if it was never seeded.
        if (! Lookup::query()->where('type', Lookup::TYPE_CURRENCY)->exists()) {
            \Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => \Modules\Setup\Database\Seeders\LookupCurrencySeeder::class, '--force' => true]);
        }
        $have = Currency::query()->pluck('lookup_id');

        return view('settings.taxes', [
            'taxes' => TaxRate::query()->orderByDesc('is_default')->orderBy('name')->get(),
            'currencies' => Currency::query()->orderByDesc('is_default')->orderBy('code')->get(),
            'available' => Lookup::query()->where('type', Lookup::TYPE_CURRENCY)->whereNotIn('id', $have)->orderBy('code')->get(['id', 'code', 'name']),
            'editing' => request('edit') ? TaxRate::query()->find(request('edit')) : null,
            'canEdit' => auth()->user()->can('settings.update'),
        ]);
    }

    public function storeTax(Request $request)
    {
        $this->need('settings.update');
        $tax = TaxRate::create($this->taxData($request));
        $this->singleDefault($tax);

        return back()->with('success', 'Tax rate added.');
    }

    public function updateTax(Request $request, TaxRate $tax)
    {
        $this->need('settings.update');
        $tax->update($this->taxData($request));
        $this->singleDefault($tax);

        return redirect()->route('settings.taxes')->with('success', 'Tax rate updated.');
    }

    public function destroyTax(TaxRate $tax)
    {
        $this->need('settings.update');
        $tax->delete();

        return back()->with('success', 'Tax rate removed.');
    }

    private function singleDefault(TaxRate $tax): void
    {
        if ($tax->is_default) {
            TaxRate::query()->whereKeyNot($tax->id)->update(['is_default' => false]);
        }
    }

    private function taxData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'type' => ['required', Rule::in(['exclusive', 'inclusive'])],
        ]) + ['is_default' => $request->boolean('is_default'), 'is_active' => $request->boolean('is_active', true)];
    }

    public function addCurrency(Request $request, CurrencyService $service)
    {
        $this->need('settings.update');
        $id = (int) $request->validate(['lookup_id' => ['required', 'integer']])['lookup_id'];
        $first = ! Currency::query()->exists();
        $currency = $service->createFromLookups([$id])->first();
        if ($first && $currency) { // the first currency becomes the default
            $currency->update(['is_default' => true, 'is_active' => true]);
            Settings::set(['default_currency' => $currency->code]);
        }

        return back()->with('success', 'Currency added.');
    }

    public function updateCurrency(Request $request, Currency $currency)
    {
        $this->need('settings.update');
        DB::transaction(function () use ($request, $currency) {
            if ($request->boolean('make_default')) {
                Currency::query()->update(['is_default' => false]);
                $currency->update(['is_default' => true, 'is_active' => true]);
                Settings::set(['default_currency' => $currency->code]);
            } elseif ($request->has('is_active')) {
                abort_if($currency->is_default && ! $request->boolean('is_active'), 422, 'The default currency cannot be deactivated.');
                $currency->update(['is_active' => $request->boolean('is_active')]);
            }
        });

        return back()->with('success', 'Currency updated.');
    }

    /* ───────────── Payment & shipping ───────────── */

    public function payments()
    {
        $this->need('settings.view');

        return view('settings.payments', [
            'methods' => PaymentMethod::query()->orderBy('sort')->orderBy('name')->get(),
            'shipping' => ShippingMethod::query()->orderBy('fee')->get(),
            'editPayment' => request('payment') ? PaymentMethod::query()->find(request('payment')) : null,
            'editShipping' => request('shipping') ? ShippingMethod::query()->find(request('shipping')) : null,
            'canEdit' => auth()->user()->can('settings.update'),
        ]);
    }

    public function savePayment(Request $request, ?PaymentMethod $method = null)
    {
        $this->need('settings.update');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::in(array_keys(PaymentMethod::TYPES))],
            'details' => ['nullable', 'string', 'max:2000'],
        ]) + ['is_active' => $request->boolean('is_active', true)];
        $method?->exists ? $method->update($data) : PaymentMethod::create($data);

        return redirect()->route('settings.payments')->with('success', 'Payment method saved.');
    }

    public function destroyPayment(PaymentMethod $method)
    {
        $this->need('settings.update');
        $method->delete();

        return back()->with('success', 'Payment method removed.');
    }

    public function saveShipping(Request $request, ?ShippingMethod $method = null)
    {
        $this->need('settings.update');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'fee' => ['required', 'numeric', 'min:0'],
            'free_over' => ['nullable', 'numeric', 'min:0'],
            'delivery_time' => ['nullable', 'string', 'max:60'],
        ]) + ['is_active' => $request->boolean('is_active', true)];
        $method?->exists ? $method->update($data) : ShippingMethod::create($data);

        return redirect()->route('settings.payments')->with('success', 'Shipping method saved.');
    }

    public function destroyShipping(ShippingMethod $method)
    {
        $this->need('settings.update');
        $method->delete();

        return back()->with('success', 'Shipping method removed.');
    }

    /* ───────────── Notifications ───────────── */

    public const NOTIFICATIONS = [
        'notify_leave_request' => ['Leave requested', 'Email the manager / HR when an employee applies for leave.'],
        'notify_leave_decision' => ['Leave approved / rejected', 'Email the employee when their leave is decided.'],
        'notify_payslip' => ['Payslip ready', 'Email employees when payroll is finalized.'],
        'notify_task_assigned' => ['Task assigned', 'Email an employee when a task is assigned to them.'],
        'notify_interview_assigned' => ['Interview assigned', 'Email the interviewer when they are assigned a candidate.'],
        'notify_announcement' => ['New announcement', 'Email everyone in the audience when an announcement is published.'],
    ];

    public function notifications()
    {
        $this->need('settings.view');

        return view('settings.notifications', [
            's' => Settings::all(),
            'items' => self::NOTIFICATIONS,
            'canEdit' => auth()->user()->can('settings.update'),
            'mailer' => Settings::get('smtp_host') ? 'SMTP' : 'Not configured',
        ]);
    }

    public function updateNotifications(Request $request)
    {
        $this->need('settings.update');
        $data = $request->validate([
            'mail_from_name' => ['nullable', 'string', 'max:100'],
            'mail_from_address' => ['nullable', 'email', 'max:150'],
            'smtp_host' => ['required', 'string', 'max:150'],
            'smtp_port' => ['required', 'integer', 'between:1,65535'],
            'smtp_encryption' => ['nullable', 'in:tls,ssl,none'],
            'smtp_username' => ['nullable', 'string', 'max:150'],
            'smtp_password' => ['nullable', 'string', 'max:255'],
        ]);

        $values = collect($data)->except('smtp_password')->all();
        $values['smtp_enabled'] = '1';
        $values['smtp_verify_peer'] = $request->boolean('smtp_verify_peer') ? '1' : '0';
        if ($request->boolean('clear_smtp_password')) {
            $values['smtp_password'] = null;
        } elseif (filled($data['smtp_password'] ?? null)) {
            $values['smtp_password'] = Crypt::encryptString($data['smtp_password']); // blank = keep current
        }
        foreach (array_keys(self::NOTIFICATIONS) as $key) {
            $values[$key] = $request->boolean($key) ? '1' : '0';
        }
        Settings::set($values);

        return back()->with('success', 'Notification settings saved.');
    }

    /** Send a test email to the current user with this tenant's mail settings. */
    public function testMail()
    {
        $this->need('settings.update');
        $user = auth()->user();
        if (! Settings::get('smtp_host')) {
            return back()->with('error', 'Save your SMTP details first.');
        }
        try {
            $user->notifyNow(new AppNotice('Test email', [
                'This is a test email from your '.Settings::companyName().' mail settings.',
                'Sent through your SMTP server ('.Settings::get('smtp_host').').',
            ]));
        } catch (\Throwable $e) {
            return back()->with('error', 'Test email failed: '.\Illuminate\Support\Str::limit($e->getMessage(), 180));
        }

        return back()->with('success', 'Test email sent to '.$user->email.'.');
    }

    /* ───────────── Audit logs ───────────── */

    public function audit(Request $request)
    {
        $this->need('settings.audit_logs');
        $tenantId = auth()->user()->tenant_id;

        $logs = AuditTrail::query()
            ->when($tenantId === null, fn ($q) => $q->whereNull('tenant_id'), fn ($q) => $q->where('tenant_id', $tenantId))
            ->when($request->filled('event'), fn ($q) => $q->where('event', $request->event))
            ->when($request->filled('type'), fn ($q) => $q->where('auditable_type', $request->type))
            ->when($request->filled('user'), fn ($q) => $q->where('user_id', $request->user))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->to))
            ->latest('created_at')->paginate(25)->withQueryString();

        $types = AuditTrail::query()
            ->when($tenantId === null, fn ($q) => $q->whereNull('tenant_id'), fn ($q) => $q->where('tenant_id', $tenantId))
            ->distinct()->orderBy('auditable_type')->pluck('auditable_type');

        return view('settings.audit', [
            'logs' => $logs,
            'names' => $this->auditNames($logs->getCollection()),
            'types' => $types->mapWithKeys(fn ($t) => [$t => Str::headline(class_basename($t))]),
            'users' => User::query()->whereIn('id', $logs->pluck('user_id')->filter()->unique())->pluck('name', 'id'),
            'allUsers' => User::query()->where('tenant_id', $tenantId)->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    /** Foreign-key columns shown in audit logs → model that holds the readable name. */
    private const AUDIT_REFS = [
        'employee_id' => \Modules\Setup\Models\Employee::class, 'manager_id' => \Modules\Setup\Models\Employee::class,
        'assignee_id' => \Modules\Setup\Models\Employee::class, 'interviewer_id' => \Modules\Setup\Models\Employee::class,
        'reviewer_id' => \Modules\Setup\Models\Employee::class,
        'department_id' => \Modules\Setup\Models\Department::class, 'designation_id' => \Modules\Setup\Models\Designation::class,
        'leave_type_id' => \App\Models\Hrm\LeaveType::class, 'shift_id' => \App\Models\Hrm\Shift::class,
        'job_id' => \App\Models\Hrm\Job::class, 'loan_id' => \App\Models\Hrm\Loan::class,
        'payroll_run_id' => \App\Models\Hrm\PayrollRun::class, 'payslip_id' => \App\Models\Hrm\Payslip::class,
        'task_id' => \App\Models\Projects\Task::class, 'component_id' => \App\Models\Hrm\SalaryComponent::class,
        'branch_id' => \App\Models\Settings\Branch::class, 'currency_id' => Currency::class,
    ];

    /**
     * Resolve ids in the visible audit rows to names: [field => [id => label]].
     * `*_by` and `user_id` columns are users; others come from AUDIT_REFS.
     */
    private function auditNames(\Illuminate\Support\Collection $logs): array
    {
        $ids = [];
        foreach ($logs as $log) {
            foreach ([$log->old_values ?? [], $log->new_values ?? []] as $values) {
                foreach ($values as $field => $value) {
                    if (is_numeric($value) && (isset(self::AUDIT_REFS[$field]) || $field === 'user_id' || str_ends_with($field, '_by'))) {
                        $ids[$field][] = (int) $value;
                    }
                }
            }
        }

        $names = [];
        foreach ($ids as $field => $list) {
            $class = self::AUDIT_REFS[$field] ?? User::class;
            try {
                $names[$field] = $class::query()->whereIn('id', array_unique($list))->get()
                    ->mapWithKeys(function ($m) use ($class) {
                        $label = $m->name ?? $m->title ?? $m->code ?? null;
                        if (! $label && $m->employee_id ?? null) {
                            $label = \Modules\Setup\Models\Employee::query()->whereKey($m->employee_id)->value('name');
                        }

                        return [$m->getKey() => $label ?: Str::headline(class_basename($class)).' #'.$m->getKey()];
                    })->all();
            } catch (\Throwable) {
                $names[$field] = [];
            }
        }

        return $names;
    }
}
