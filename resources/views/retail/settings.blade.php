@extends('layouts.admin.main')
@section('title', 'Sales Settings')
@section('subtitle', 'Sales')

@section('content')
<div class="app-content">
    <x-admin.page-header title="Sales & Purchase Settings" subtitle="Document numbering, rounding and what prints on invoices." />
    <form method="POST" action="{{ route('sales.settings.update') }}" class="app-form-grid">@csrf @method('PUT')
        <div class="app-card app-form-card">
            <div class="form-section-title">Number prefixes</div>
            <div class="row g-3">
                @foreach ($types as $t => $m)
                    <div class="col-md-3"><x-admin.input :name="'prefix_'.$t" :label="$m['one']" :value="$s['prefix_'.$t] ?? null" :placeholder="$m['prefix']" /></div>
                @endforeach
                <div class="col-md-3"><x-admin.input name="prefix_receipt" label="Payment receipt" :value="$s['prefix_receipt'] ?? null" placeholder="RV" /></div>
                <div class="col-md-3"><x-admin.input name="prefix_voucher" label="Payment voucher" :value="$s['prefix_voucher'] ?? null" placeholder="PV" /></div>
            </div>
            <div class="form-section-title mt-5">Invoice</div>
            <div class="row g-3">
                <div class="col-md-6"><x-admin.input name="invoice_title" label="Invoice title" :value="$s['invoice_title'] ?? null" placeholder="Tax Invoice" /></div>
                <div class="col-md-6"><x-admin.select name="thermal_width" label="Thermal paper" :options="['80' => '80 mm', '58' => '58 mm']" :value="$s['thermal_width'] ?? '80'" required /></div>
                <div class="col-12"><label class="form-label">Default terms & conditions</label><textarea name="invoice_terms" rows="3" class="form-control">{{ old('invoice_terms', $s['invoice_terms'] ?? '') }}</textarea></div>
                <div class="col-12"><x-admin.input name="invoice_footer" label="Invoice footer" :value="$s['invoice_footer'] ?? null" placeholder="Thank you for your business!" /></div>
            </div>
            <div class="d-grid gap-2 small mt-4">
                @foreach (['round_off' => ['Round off grand totals', '0'], 'show_previous_due' => ['Show customer previous due on invoices', '1'], 'show_tax_column' => ['Show tax column on A4 invoices', '1']] as $k => [$l, $d])
                    <label class="d-flex gap-2 align-items-center"><input type="checkbox" class="form-check-input mt-0" name="{{ $k }}" value="1" @checked(($s[$k] ?? $d) === '1')> {{ $l }}</label>
                @endforeach
            </div>
            <div class="app-form-actions"><x-admin.button class="btn-lg-app" icon="check2">Save</x-admin.button></div>
        </div>
        <div class="app-aside">
            <x-admin.tips :items="['Company name, logo, NTN / STRN and address come from Settings → Company Profile.', 'Payment methods come from Settings → Payment & Shipping.', 'Taxes come from Settings → Taxes & Currencies.']" />
        </div>
    </form>
</div>
@endsection
