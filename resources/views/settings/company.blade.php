@extends('layouts.admin.main')
@section('title', 'Company Profile')
@section('subtitle', 'Settings')

@section('content')
<div class="app-content">
    <x-admin.page-header title="Company Profile" subtitle="Your business details — used on payslips, invoices, the careers page and emails." />

    <form method="POST" action="{{ route('settings.company.update') }}" enctype="multipart/form-data" class="app-form-grid">
        @csrf @method('PUT')
        <fieldset class="app-card app-form-card" @disabled(! $canEdit)>
            <div class="form-section-title">Business</div>
            <div class="row g-4">
                <div class="col-md-6"><x-admin.input name="company_name" label="Company name" :value="$s['company_name'] ?? tenant('name')" required /></div>
                <div class="col-md-6"><x-admin.input name="legal_name" label="Legal / registered name" optional :value="$s['legal_name']" /></div>
                <div class="col-md-6"><x-admin.input name="ntn" label="NTN" optional :value="$s['ntn']" placeholder="National Tax Number" /></div>
                <div class="col-md-6"><x-admin.input name="strn" label="STRN" optional :value="$s['strn']" placeholder="Sales Tax Registration No." /></div>
            </div>

            <div class="form-section-title mt-5">Contact</div>
            <div class="row g-4">
                <div class="col-md-6"><x-admin.input name="email" type="email" label="Email" optional :value="$s['email']" /></div>
                <div class="col-md-6"><x-admin.input name="phone" label="Phone" optional :value="$s['phone']" /></div>
                <div class="col-md-12"><x-admin.input name="website" type="url" label="Website" optional :value="$s['website']" placeholder="https://" /></div>
                <div class="col-md-12"><x-admin.input name="address" label="Address" optional :value="$s['address']" /></div>
                <div class="col-md-6"><x-admin.input name="city" label="City" optional :value="$s['city']" /></div>
                <div class="col-md-6"><x-admin.input name="country" label="Country" optional :value="$s['country']" /></div>
            </div>

            <div class="form-section-title mt-5">Regional & finance</div>
            <div class="row g-4">
                <div class="col-md-3"><x-admin.select name="fiscal_year_start" label="Fiscal year starts" :value="$s['fiscal_year_start']" required
                    :options="collect(range(1, 12))->mapWithKeys(fn ($m) => [$m => \Illuminate\Support\Carbon::create(null, $m, 1)->format('F')])" /></div>
                <div class="col-md-3"><x-admin.select name="date_format" label="Date format" :value="$s['date_format']" required
                    :options="collect(['d M Y', 'd/m/Y', 'm/d/Y', 'Y-m-d'])->mapWithKeys(fn ($f) => [$f => now()->format($f)])" /></div>
                <div class="col-md-3"><x-admin.select name="default_currency" label="Default currency" :value="$s['default_currency']" :options="$currencies" placeholder="—"
                    hint="Manage in Taxes & Currencies." /></div>
                <div class="col-md-3"><x-admin.select name="timezone" label="Timezone" :value="$s['timezone'] ?? ''" placeholder="Default (UTC)"
                    :options="collect($timezones)->mapWithKeys(fn ($t) => [$t => $t])"
                    hint="Dates, reports & attendance use this." /></div>
            </div>

            @if ($canEdit)
                <div class="app-form-actions"><x-admin.button class="btn-lg-app" icon="check2">Save Changes</x-admin.button></div>
            @endif
        </fieldset>

        <div class="app-aside">
            <div class="app-card"><div class="app-card__body text-center">
                <h3 class="app-aside-title text-start">Logo</h3>
                <div class="settings-logo mb-3">
                    @if ($logo = \App\Support\Settings::logoUrl())<img src="{{ $logo }}" alt="Logo">@else<i class="bi bi-image"></i>@endif
                </div>
                @if ($canEdit)
                    <input type="file" name="logo" accept="image/*" class="form-control">
                    <div class="form-text text-start">PNG, JPG or SVG — max 2 MB. Square works best.</div>
                    @if ($logo)<label class="d-flex align-items-center gap-2 small mt-2"><input type="checkbox" class="form-check-input mt-0" name="remove_logo" value="1"> Remove logo</label>@endif
                @endif
            </div></div>
            <x-admin.tips :items="['The company name and logo appear in the sidebar for your team.', 'NTN / STRN are printed on invoices once Sales is enabled.']" />
        </div>
    </form>
</div>
@endsection

@push('styles')
<style>
    .settings-logo { width: 120px; height: 120px; margin: 0 auto; border-radius: 18px; border: 2px dashed var(--app-border); display: flex; align-items: center; justify-content: center; overflow: hidden; background: var(--app-hover); }
    .settings-logo img { max-width: 100%; max-height: 100%; object-fit: contain; }
    .settings-logo i { font-size: 2.2rem; color: var(--app-text-muted); }
    fieldset:disabled .form-control, fieldset:disabled .form-select { background: var(--app-hover); }
</style>
@endpush
