@extends('layouts.admin.main')
@php
    $editing = $tenant->exists;
    $selected = old('modules', (array) ($tenant->modules ?? []));
    $active = (string) old('is_active', $tenant->is_active ? '1' : '0');
@endphp
@section('title', $editing ? 'Edit Tenant' : 'Create Tenant')
@section('subtitle', $editing ? 'Update tenant settings and modules' : 'A new database is created automatically')

@section('content')
<div class="app-content">
    <x-admin.page-header :back="route('tenants.index')" :title="$editing ? 'Edit Tenant' : 'Create Tenant'"
        :subtitle="$editing ? 'Database: '.$tenant->databaseName() : 'Fill in the details — the tenant gets its own database'">
        <x-admin.button variant="light" :href="route('tenants.index')" icon="list-ul">All Tenants</x-admin.button>
    </x-admin.page-header>

    <x-admin.alerts />

    <form method="POST" action="{{ $editing ? route('tenants.update', $tenant) : route('tenants.store') }}" class="app-form-grid">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="app-card app-form-card">
            <x-admin.input name="name" label="Tenant / Company Name" :value="$tenant->name" placeholder="e.g. Acme Retailers" required />

            <div class="app-field">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="form-label mb-0">Modules <span class="opt">what this tenant can use</span></label>
                    <label class="d-flex align-items-center gap-2 mb-0 small fw-semibold" style="cursor:pointer">
                        <input class="form-check-input mt-0" type="checkbox" id="allModules"> Select all
                    </label>
                </div>
                <div class="row g-3">
                    @foreach (\App\Support\ModuleAccess::assignable() as $key => $m)
                        <div class="col-md-6 col-xl-4">
                            <label class="app-radio-card h-100 mb-0">
                                <input type="checkbox" class="module-cb" name="modules[]" value="{{ $key }}" @checked(in_array($key, $selected))>
                                <span class="app-tile__icon tone-primary" style="width:34px;height:34px;font-size:.95rem"><i class="bi {{ $m['icon'] }}"></i></span>
                                <span class="app-radio-card__title">{{ $m['label'] }}</span>
                            </label>
                        </div>
                    @endforeach
                </div>
                <div class="form-text mt-2">Users & Roles are always available so the tenant admin can manage their team.</div>
                @error('modules')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            @unless ($editing)
                <div class="form-section-title mt-4">Tenant admin login</div>
                <x-admin.input name="admin_name" label="Admin Name" placeholder="e.g. Ali Khan" required />
                <x-admin.input name="admin_email" type="email" label="Admin Email" placeholder="admin@acme.com" required
                    hint="The tenant admin signs in with this email and sees only the modules above." />
                <div class="row g-4 mt-0">
                    <div class="col-md-6">
                        <x-admin.input name="admin_password" type="password" label="Password" placeholder="Minimum 8 characters" autocomplete="new-password" required />
                    </div>
                    <div class="col-md-6">
                        <x-admin.input name="admin_password_confirmation" type="password" label="Confirm Password" placeholder="Re-enter password" autocomplete="new-password" />
                    </div>
                </div>
            @endunless

            <div class="app-form-actions">
                <x-admin.button class="btn-lg-app" icon="check2">{{ $editing ? 'Save Changes' : 'Create Tenant' }}</x-admin.button>
                <x-admin.button variant="light" class="btn-lg-app" :href="route('tenants.index')">Cancel</x-admin.button>
            </div>
        </div>

        <div class="app-aside">
            <div class="app-card"><div class="app-card__body">
                <h3 class="app-aside-title">Status</h3>
                <x-admin.radio-card name="is_active" value="1" title="Active" desc="Tenant users can sign in" :checked="$active === '1'" badge="Active" tone="success" />
                <x-admin.radio-card name="is_active" value="0" title="Inactive" desc="Sign-in is blocked" :checked="$active === '0'" badge="Inactive" tone="danger" />
            </div></div>

            @if ($editing && $admin)
                <div class="app-card"><div class="app-card__body">
                    <h3 class="app-aside-title">Tenant admin</h3>
                    <div class="d-flex align-items-center gap-3">
                        <span class="app-avatar">{{ mb_substr($admin->name, 0, 1) }}</span>
                        <div><div class="fw-semibold">{{ $admin->name }}</div><div class="small text-body-secondary">{{ $admin->email }}</div></div>
                    </div>
                </div></div>
            @endif

            <x-admin.tips :items="[
                'Creating a tenant makes a new database (e.g. '.config('tenancy.database.prefix').'001) and migrates it.',
                'The tenant admin can create their own roles and users.',
                'Turning a module off hides it instantly for all of that tenant\'s users.',
                'Deleting a tenant permanently drops its database.',
            ]" />
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const cbs = [...document.querySelectorAll('.module-cb')], all = document.getElementById('allModules');
    const sync = () => all.checked = cbs.length && cbs.every(c => c.checked);
    all.addEventListener('change', () => { cbs.forEach(c => c.checked = all.checked); });
    cbs.forEach(c => c.addEventListener('change', sync)); sync();
})();
</script>
@endpush
