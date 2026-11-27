@extends('layouts.admin.main')
@php
    $editing = $employee->exists;
    $loginOn = (bool) old('allow_login', $loginUser !== null || ! $editing);
    $selRoles = old('roles', $userRoles);
    $selPerms = old('permissions', $userPermissions);
    $isSelf = $loginUser && $loginUser->id === auth()->id();
@endphp
@section('title', $editing ? 'Edit Employee' : 'Add Employee')
@section('subtitle', 'HRM · Employees')

@section('content')
<div class="app-content">
    <x-admin.page-header :back="$editing ? route('hrm.employees.show', $employee) : route('hrm.employees.index')"
        :title="$editing ? 'Edit '.$employee->name : 'Add Employee'"
        subtitle="Profile, job, salary and what this employee can access when they sign in.">
        <x-admin.button variant="light" :href="route('hrm.employees.index')" icon="list-ul">All Employees</x-admin.button>
    </x-admin.page-header>

    <form method="POST" action="{{ $editing ? route('hrm.employees.update', $employee) : route('hrm.employees.store') }}" class="app-form-grid">
        @csrf
        @if ($editing) @method('PUT') @endif
        @if (request('application'))<input type="hidden" name="application" value="{{ request('application') }}">@endif

        <div class="d-grid gap-4">
            <div class="app-card app-form-card">
                <div class="form-section-title">Personal</div>
                <div class="row g-4">
                    <div class="col-md-8"><x-admin.input name="name" label="Full Name" :value="$employee->name" placeholder="e.g. Ali Khan" required /></div>
                    <div class="col-md-4"><x-admin.input name="code" label="Employee Code" optional :value="$employee->code" placeholder="EMP-001" /></div>
                    <div class="col-md-6"><x-admin.input name="email" type="email" label="Personal Email" optional :value="$employee->email" /></div>
                    <div class="col-md-6"><x-admin.input name="phone" label="Phone" optional :value="$employee->phone" /></div>
                    <div class="col-md-4"><x-admin.input name="dob" type="date" label="Date of Birth" optional :value="optional($employee->dob)->format('Y-m-d') ?? $employee->dob" /></div>
                    <div class="col-md-4"><x-admin.select name="gender" label="Gender" optional :options="['male' => 'Male', 'female' => 'Female', 'others' => 'Other']" :value="$employee->gender" placeholder="—" /></div>
                    <div class="col-md-4"><x-admin.select name="marital_status" label="Marital Status" optional :options="['unmarried' => 'Single', 'married' => 'Married', 'divorced' => 'Divorced']" :value="$employee->marital_status" placeholder="—" /></div>
                    <div class="col-md-4"><x-admin.input name="national_id" label="National ID / CNIC" optional :value="$employee->national_id" /></div>
                    <div class="col-md-4"><x-admin.input name="blood_group" label="Blood Group" optional :value="$employee->blood_group" /></div>
                    <div class="col-md-4"><x-admin.input name="city" label="City" optional :value="$employee->city" /></div>
                    <div class="col-12"><x-admin.input name="street" label="Address" optional :value="$employee->street" /></div>
                </div>

                <div class="form-section-title mt-5">Job</div>
                <div class="row g-4">
                    <div class="col-md-6"><x-admin.select name="department_id" label="Department" :options="$departments" :value="$employee->department_id" placeholder="Select department" required /></div>
                    <div class="col-md-6">
                        <div class="app-field">
                            <label class="form-label" for="f_designation_id">Designation<span class="req">*</span></label>
                            <select id="f_designation_id" name="designation_id" required @class(['form-select', 'is-invalid' => $errors->has('designation_id')])>
                                <option value="">Select designation</option>
                                @foreach ($designations as $d)
                                    <option value="{{ $d->id }}" data-dept="{{ $d->department_id }}" @selected(old('designation_id', $employee->designation_id) == $d->id)>{{ $d->name }}</option>
                                @endforeach
                            </select>
                            @error('designation_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="app-field">
                            <label class="form-label" for="f_manager_id">Reports to <span class="opt">the person this employee reports to</span></label>
                            <select id="f_manager_id" name="manager_id" @class(['form-select', 'is-invalid' => $errors->has('manager_id')])>
                                <option value="">— Use the designation chart / nobody (top level) —</option>
                                <optgroup label="Suggested (from the designation chart)" id="mgrSuggested"></optgroup>
                                <optgroup label="Everyone" id="mgrAll">
                                    @foreach ($managers as $m)
                                        <option value="{{ $m->id }}" data-designation="{{ $m->designation_id }}" @selected((string) old('manager_id', $employee->manager_id) === (string) $m->id)>
                                            {{ $m->name }}{{ $m->designation ? ' — '.$m->designation->name : '' }}</option>
                                    @endforeach
                                </optgroup>
                            </select>
                            @error('manager_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">Pick the exact person (e.g. which Team Lead). People whose designation is above this one are suggested first.</div>
                        </div>
                    </div>
                    <div class="col-md-6"><x-admin.input name="join_date" type="date" label="Joining Date" :value="$employee->join_date ? \Illuminate\Support\Carbon::parse($employee->join_date)->format('Y-m-d') : ''" required /></div>
                    <div class="col-md-3"><x-admin.select name="employment_type" label="Employment Type" optional :options="config('hrm.employment_types')" :value="$employee->employment_type" placeholder="—" /></div>
                    <div class="col-md-3"><x-admin.select name="shift_id" label="Shift" optional :options="$shifts->mapWithKeys(fn ($s) => [$s->id => $s->label()])" :value="$employee->shift_id" placeholder="Default shift" /></div>
                </div>

                <div class="form-section-title mt-5">Salary & Bank</div>
                <div class="row g-4">
                    <div class="col-md-6"><x-admin.input name="basic_salary" type="number" step="0.01" min="0" :label="'Basic Salary ('.config('hrm.currency').')'" :value="$employee->basic_salary" placeholder="0.00" /></div>
                    <div class="col-md-6"><x-admin.input name="transport_allowance" type="number" step="0.01" min="0" label="Allowances / month" optional :value="$employee->transport_allowance" placeholder="0.00" /></div>
                    <div class="col-md-4"><x-admin.input name="bank_name" label="Bank" optional :value="$employee->bank_name" /></div>
                    <div class="col-md-4"><x-admin.input name="bank_account_no" label="Account No." optional :value="$employee->bank_account_no" /></div>
                    <div class="col-md-4"><x-admin.input name="iban_num" label="IBAN" optional :value="$employee->iban_num" /></div>
                </div>
            </div>

            {{-- Access --}}
            <div class="app-card app-form-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="form-section-title mb-1">Login & Access</div>
                        <p class="form-text mt-0 mb-0">Choose what this employee sees when they sign in. They only ever see their own HR records unless you grant more.</p>
                    </div>
                    <label class="form-check form-switch m-0 d-flex align-items-center gap-2">
                        <input type="hidden" name="allow_login" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" name="allow_login" value="1" id="allowLogin" @checked($loginOn) @disabled($isSelf)>
                        <span class="fw-semibold small">Allow login</span>
                    </label>
                    @if ($isSelf)<input type="hidden" name="allow_login" value="1">@endif
                </div>

                <div id="loginFields" class="{{ $loginOn ? '' : 'd-none' }} mt-4">
                    <div class="row g-4">
                        <div class="col-md-12"><x-admin.input name="login_email" type="email" label="Login Email" :value="$loginUser?->email ?? $employee->email" placeholder="name@company.com" /></div>
                        <div class="col-md-6"><x-admin.input name="password" type="password" label="Password" :optional="$loginUser ? '(leave blank to keep)' : null" autocomplete="new-password" placeholder="Minimum 8 characters" /></div>
                        <div class="col-md-6"><x-admin.input name="password_confirmation" type="password" label="Confirm Password" autocomplete="new-password" /></div>
                    </div>

                    @if ($isSelf)
                        <div class="app-alert app-alert--danger mt-4 mb-0"><i class="bi bi-info-circle"></i>You can't change your own roles or permissions.</div>
                    @else
                        <div class="form-section-title mt-5">Roles</div>
                        <div class="row g-3">
                            @foreach ($roles as $role)
                                <div class="col-md-6 col-xl-4">
                                    <label class="app-radio-card h-100 mb-0">
                                        <input type="checkbox" name="roles[]" value="{{ $role->name }}" @checked(in_array($role->name, $selRoles))>
                                        <span><span class="app-radio-card__title">{{ $role->name }}</span>
                                            <span class="app-radio-card__desc">{{ $role->permissions()->count() }} permissions</span></span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        <p class="form-text">“Employee” gives self-service only (own profile, leaves, attendance, payslips).</p>

                        <div class="form-section-title mt-4">Extra permissions <span class="text-body-secondary fw-normal text-lowercase">— on top of the roles</span></div>
                        <div class="d-grid gap-2">
                            @foreach ($permissionGroups as $code => $g)
                                <details class="perm-group" @if (array_intersect(array_keys($g['permissions']), $selPerms)) open @endif>
                                    <summary><i class="bi {{ $g['icon'] }}"></i> {{ $g['name'] }}
                                        <span class="small text-body-secondary ms-auto">{{ count(array_intersect(array_keys($g['permissions']), $selPerms)) }} selected</span></summary>
                                    <div class="perm-group__body">
                                        @foreach ($g['permissions'] as $perm => $label)
                                            <label class="d-flex align-items-center gap-2 small m-0">
                                                <input type="checkbox" class="form-check-input mt-0" name="permissions[]" value="{{ $perm }}" @checked(in_array($perm, $selPerms))> {{ $label }}
                                            </label>
                                        @endforeach
                                    </div>
                                </details>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="app-aside">
            <div class="app-card"><div class="app-card__body">
                <h3 class="app-aside-title">Status</h3>
                <x-admin.radio-card name="is_active" value="1" title="Active" desc="Working, can sign in" :checked="(string) old('is_active', (int) ($employee->is_active ?? 1)) === '1'" badge="Active" tone="success" />
                <x-admin.radio-card name="is_active" value="0" title="Inactive" desc="Left / suspended — login disabled" :checked="(string) old('is_active', (int) ($employee->is_active ?? 1)) === '0'" badge="Inactive" tone="danger" />
                <div class="d-grid gap-2 mt-4">
                    <x-admin.button class="btn-lg-app" icon="check2">{{ $editing ? 'Save Changes' : 'Add Employee' }}</x-admin.button>
                    <x-admin.button variant="light" :href="route('hrm.employees.index')">Cancel</x-admin.button>
                </div>
            </div></div>
            <x-admin.tips :items="[
                '“Reports to” decides the org chart; if empty, the designation chart is used.',
                'Allow login to let the employee check in, apply for leave and see payslips.',
                'Give HR staff the “HR Manager” role, or pick extra permissions.',
                'Setting an employee inactive removes their login.',
            ]" />
        </div>
    </form>
</div>
@endsection

@push('styles')
<style>
    .perm-group { border: 1.5px solid var(--app-border); border-radius: 12px; }
    .perm-group summary { display: flex; align-items: center; gap: .6rem; padding: .8rem 1rem; cursor: pointer; font-weight: 600; list-style: none; }
    .perm-group summary::-webkit-details-marker { display: none; }
    .perm-group[open] summary { border-bottom: 1px solid var(--app-border); }
    .perm-group__body { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: .55rem 1rem; padding: 1rem; }
</style>
@endpush

@push('scripts')
<script>
(() => {
    const sw = document.getElementById('allowLogin'), box = document.getElementById('loginFields');
    sw?.addEventListener('change', () => box.classList.toggle('d-none', !sw.checked));
    // Designations follow the chosen department.
    const dept = document.getElementById('f_department_id'), des = document.getElementById('f_designation_id');
    const filter = () => [...des.options].forEach(o => { if (o.value) o.hidden = dept.value && o.dataset.dept !== dept.value; });
    dept?.addEventListener('change', () => { filter(); if (des.selectedOptions[0]?.hidden) des.value = ''; });
    filter();

    // "Reports to": move people from the parent designation into "Suggested".
    const parents = @json($designationParents), mgr = document.getElementById('f_manager_id');
    const sug = document.getElementById('mgrSuggested'), all = document.getElementById('mgrAll');
    const suggest = () => {
        [...sug.children].forEach(o => all.appendChild(o));
        const parent = String(parents[des.value] ?? '');
        if (parent) [...all.children].filter(o => o.dataset.designation === parent).forEach(o => sug.appendChild(o));
        sug.hidden = !sug.children.length;
        [...all.children].sort((a, b) => a.text.localeCompare(b.text)).forEach(o => all.appendChild(o));
        // Only one candidate and nothing chosen yet → pick them.
        if (!mgr.value && sug.children.length === 1) mgr.value = sug.children[0].value;
    };
    des.addEventListener('change', suggest);
    suggest();
})();
</script>
@endpush
