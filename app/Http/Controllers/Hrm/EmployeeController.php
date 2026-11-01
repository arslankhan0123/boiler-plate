<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hrm;

use App\Models\Hrm\Attendance;
use App\Models\Hrm\LeaveAllocation;
use App\Models\Hrm\LeaveRequest;
use App\Models\Hrm\Payslip;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\Authorization\DefaultRoles;
use App\Support\Hrm\Hrm;
use App\Support\ModuleAccess;
use App\Support\ModuleRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Modules\Setup\Models\Department;
use Modules\Setup\Models\Designation;
use Modules\Setup\Models\Employee;
use Modules\Setup\Models\EmployeeDocument;

class EmployeeController extends HrmController
{
    public function index(Request $request)
    {
        $this->need('hrm.view_employees');

        $employees = Employee::query()->with(['department', 'designation'])
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$request->search}%")->orWhere('email', 'like', "%{$request->search}%")
                ->orWhere('code', 'like', "%{$request->search}%")))
            ->when($request->filled('department'), fn ($q) => $q->where('department_id', $request->department))
            ->when($request->input('status', 'active') !== 'all', fn ($q) => $q->where('is_active', $request->input('status', 'active') === 'active'))
            ->orderBy('name')->paginate(20)->withQueryString();

        return view('hrm.employees.index', [
            'employees' => $employees,
            'departments' => Department::query()->orderBy('name')->pluck('name', 'id'),
            'stats' => [
                'total' => Employee::query()->where('is_active', true)->count(),
                'with_login' => Employee::query()->where('is_active', true)->whereNotNull('user_id')->count(),
                'on_leave' => LeaveRequest::query()->where('status', 'approved')->whereDate('start_date', '<=', today())->whereDate('end_date', '>=', today())->count(),
                'departments' => Department::query()->where('is_active', true)->count(),
            ],
        ]);
    }

    public function create(Request $request)
    {
        $this->need('hrm.manage_employees');
        $employee = new Employee(['is_active' => true, 'join_date' => today()->toDateString()]);

        // Prefill from a hired job application.
        if ($request->filled('application') && $app = \App\Models\Hrm\JobApplication::query()->with('job')->find($request->application)) {
            $employee->fill(['name' => $app->name, 'email' => $app->email, 'phone' => $app->phone,
                'department_id' => $app->job?->department_id, 'designation_id' => $app->job?->designation_id]);
        }

        return view('hrm.employees.form', $this->formData($employee));
    }

    public function store(Request $request)
    {
        $this->need('hrm.manage_employees');
        $data = $this->validated($request);

        $employee = DB::transaction(function () use ($data, $request) {
            $employee = Employee::create($data['employee'] + ['created_by' => auth()->id()]);
            $this->syncLogin($employee, $request, $data);

            return $employee;
        });

        // Give this year's leave straight away if the year was already allocated.
        if (LeaveAllocation::query()->where('year', now()->year)->exists()) {
            Hrm::allocateYear(now()->year, $employee->id);
        }

        if ($request->filled('application')) {
            \App\Models\Hrm\JobApplication::query()->whereKey($request->application)->update(['status' => 'hired', 'employee_id' => $employee->id]);
        }

        return redirect()->route('hrm.employees.show', $employee)->with('success', 'Employee added.');
    }

    public function show(Employee $employee)
    {
        $this->need('hrm.view_employees');
        $year = (int) request('year', now()->year);

        return view('hrm.employees.show', [
            'employee' => $employee->load(['department', 'designation.parent', 'documents', 'user']),
            'year' => $year,
            'balances' => Hrm::balances($employee, $year),
            'leaves' => LeaveRequest::query()->with('type')->where('employee_id', $employee->id)->latest()->limit(10)->get(),
            'attendance' => Attendance::query()->where('employee_id', $employee->id)->whereBetween('date', [now()->startOfMonth(), now()->endOfMonth()])->orderBy('date')->get(),
            'payslips' => Payslip::query()->with('run')->where('employee_id', $employee->id)->latest()->limit(12)->get(),
            'manager' => $this->managerOf($employee),
            'reports' => Employee::query()->where('is_active', true)
                ->whereIn('designation_id', Designation::query()->where('parent_id', $employee->designation_id)->pluck('id'))->get(),
        ]);
    }

    public function edit(Employee $employee)
    {
        $this->need('hrm.manage_employees');

        return view('hrm.employees.form', $this->formData($employee));
    }

    public function update(Request $request, Employee $employee)
    {
        $this->need('hrm.manage_employees');
        $data = $this->validated($request, $employee);

        DB::transaction(function () use ($employee, $data, $request) {
            $employee->update($data['employee']);
            $this->syncLogin($employee, $request, $data);
        });

        return redirect()->route('hrm.employees.show', $employee)->with('success', 'Employee updated.');
    }

    public function destroy(Employee $employee)
    {
        $this->need('hrm.manage_employees');
        abort_if($employee->user_id === auth()->id(), 403, 'You cannot delete your own employee record.');

        if ($employee->user_id) {
            User::query()->whereKey($employee->user_id)->where('tenant_id', auth()->user()->tenant_id)->forceDelete();
        }
        $employee->update(['user_id' => null, 'is_active' => false]);
        $employee->delete();

        return redirect()->route('hrm.employees.index')->with('success', 'Employee removed and their login disabled.');
    }

    /* ───────────── documents ───────────── */

    public function storeDocument(Request $request, Employee $employee)
    {
        $this->need('hrm.manage_employees');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'expiry_date' => ['nullable', 'date'],
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx'],
        ]);

        $employee->documents()->create([
            'name' => $data['name'],
            'expiry_date' => $data['expiry_date'] ?? null,
            'file' => $request->file('file')->store(tenant_upload_path('hrm/documents/'.$employee->id), 'local'),
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Document uploaded.');
    }

    public function downloadDocument(Employee $employee, EmployeeDocument $document)
    {
        abort_unless($document->employee_id === $employee->id, 404);
        $isSelf = Hrm::me()?->id === $employee->id;
        abort_unless($isSelf || auth()->user()->can('hrm.view_employees'), 403);
        abort_unless($document->file && Storage::disk('local')->exists($document->file), 404);

        return Storage::disk('local')->download($document->file, $document->name.'.'.pathinfo($document->file, PATHINFO_EXTENSION));
    }

    public function destroyDocument(Employee $employee, EmployeeDocument $document)
    {
        $this->need('hrm.manage_employees');
        abort_unless($document->employee_id === $employee->id, 404);
        if ($document->file) {
            Storage::disk('local')->delete($document->file);
        }
        $document->delete();

        return back()->with('success', 'Document deleted.');
    }

    /* ───────────── helpers ───────────── */

    private function managerOf(Employee $employee): ?Employee
    {
        $parentId = $employee->designation?->parent_id;

        return $parentId ? Employee::query()->where('is_active', true)->where('designation_id', $parentId)->orderBy('join_date')->first() : null;
    }

    private function formData(Employee $employee): array
    {
        $editor = auth()->user();
        $user = $employee->user_id ? User::query()->find($employee->user_id) : null;

        return [
            'employee' => $employee,
            'loginUser' => $user,
            'departments' => Department::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'),
            'designations' => Designation::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'department_id']),
            'roles' => $this->assignableRoles(),
            'userRoles' => $user ? $user->roles()->pluck('name')->all() : ['Employee'],
            'userPermissions' => $user ? $user->permissions()->pluck('name')->all() : [],
            'permissionGroups' => $this->assignablePermissions(),
        ];
    }

    /** Roles the editor may hand out (Tenant Admin only by a Tenant Admin). */
    private function assignableRoles()
    {
        return Role::query()->where('guard_name', 'api')
            ->when(! auth()->user()->hasRole(DefaultRoles::TENANT_ADMIN), fn ($q) => $q->where('name', '!=', DefaultRoles::TENANT_ADMIN))
            ->orderBy('name')->get();
    }

    /** Extra (direct) permissions: enabled modules only, and only ones the editor holds. */
    private function assignablePermissions(): array
    {
        $editor = auth()->user();
        $out = [];
        foreach (ModuleRegistry::all() as $code => $m) {
            if (! ModuleAccess::moduleAvailable($editor, $code)) {
                continue;
            }
            $perms = array_filter($m['permissions'], fn ($label, $p) => $editor->can($p), ARRAY_FILTER_USE_BOTH);
            if ($perms) {
                $out[$code] = ['name' => $m['name'], 'icon' => $m['icon'], 'permissions' => $perms];
            }
        }

        return $out;
    }

    private function validated(Request $request, ?Employee $employee = null): array
    {
        $user = $employee?->user_id ? User::query()->find($employee->user_id) : null;

        $v = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:32'],
            'dob' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'others'])],
            'marital_status' => ['nullable', Rule::in(['married', 'unmarried', 'divorced'])],
            'blood_group' => ['nullable', 'string', 'max:8'],
            'national_id' => ['nullable', 'string', 'max:50'],
            'street' => ['nullable', 'string', 'max:190'],
            'city' => ['nullable', 'string', 'max:100'],
            'department_id' => ['required', Rule::exists((new Department())->getTable(), 'id')],
            'designation_id' => ['required', Rule::exists((new Designation())->getTable(), 'id')],
            'join_date' => ['required', 'date'],
            'employment_type' => ['nullable', Rule::in(array_keys(config('hrm.employment_types')))],
            'basic_salary' => ['nullable', 'numeric', 'min:0'],
            'transport_allowance' => ['nullable', 'numeric', 'min:0'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_no' => ['nullable', 'string', 'max:50'],
            'iban_num' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],

            'allow_login' => ['nullable', 'boolean'],
            'login_email' => ['nullable', 'required_if:allow_login,1', 'email', 'max:190', Rule::unique(User::class, 'email')->ignore($user?->id)],
            'password' => [$user ? 'nullable' : 'required_if:allow_login,1', 'nullable', 'confirmed', Password::min(8)],
            'roles' => ['array'],
            'roles.*' => ['string'],
            'permissions' => ['array'],
            'permissions.*' => ['string'],
        ]);

        $employeeData = collect($v)->only(['name', 'code', 'email', 'phone', 'dob', 'gender', 'marital_status', 'blood_group',
            'national_id', 'street', 'city', 'department_id', 'designation_id', 'join_date', 'employment_type', 'basic_salary',
            'transport_allowance', 'bank_name', 'bank_account_no', 'iban_num'])->all();
        $employeeData['is_active'] = $request->boolean('is_active', true);
        $employeeData['gross_salary'] = (float) ($v['basic_salary'] ?? 0) + (float) ($v['transport_allowance'] ?? 0);

        return ['employee' => $employeeData] + $v;
    }

    /** Create / update / remove the employee's login, roles and extra permissions. */
    private function syncLogin(Employee $employee, Request $request, array $data): void
    {
        $tenantId = auth()->user()->tenant_id;
        $user = $employee->user_id ? User::query()->where('tenant_id', $tenantId)->find($employee->user_id) : null;

        if (! $request->boolean('allow_login') || ! $employee->is_active) {
            if ($user && $user->id !== auth()->id()) {
                $user->syncRoles([]);
                $user->syncPermissions([]);
                $user->forceDelete();
                $employee->update(['user_id' => null]);
            }

            return;
        }

        $attrs = ['name' => $employee->name, 'email' => $data['login_email']];
        if (! empty($data['password'])) {
            $attrs['password'] = Hash::make($data['password']);
        }

        if (! $user) {
            $user = User::create($attrs + ['tenant_id' => $tenantId, 'email_verified_at' => now(), 'is_platform_admin' => false]);
            $employee->update(['user_id' => $user->id]);
        } else {
            $user->update($attrs);
        }

        if ($user->id === auth()->id()) {
            return; // never change your own access here
        }

        $allowedRoles = $this->assignableRoles()->pluck('name')->all();
        $roles = Role::query()->where('guard_name', 'api')->whereIn('name', array_intersect($data['roles'] ?? [], $allowedRoles))->get();
        $user->syncRoles($roles->all());

        $allowedPerms = collect($this->assignablePermissions())->flatMap(fn ($g) => array_keys($g['permissions']))->all();
        $perms = Permission::query()->where('guard_name', 'api')->whereIn('name', array_intersect($data['permissions'] ?? [], $allowedPerms))->get();
        $user->syncPermissions($perms->all());
    }
}
