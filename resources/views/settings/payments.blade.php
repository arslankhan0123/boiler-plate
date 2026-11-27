@extends('layouts.admin.main')
@php $money = fn ($v) => \App\Support\Settings::get('default_currency', 'PKR').' '.number_format((float) $v, 2); @endphp
@section('title', 'Payment & Shipping')
@section('subtitle', 'Settings')

@section('content')
<div class="app-content">
    <x-admin.page-header title="Payment & Shipping" subtitle="How customers pay you and how orders are delivered (POS, sales and online store)." />

    <div class="row g-4">
        <div class="col-xl-6 d-grid gap-4 align-content-start">
            <x-admin.card flush :title="'Payment methods ('.$methods->count().')'">
                <div class="table-responsive"><table class="table app-table"><tbody>
                    @forelse ($methods as $m)
                        <tr>
                            <td><div class="fw-semibold">{{ $m->name }}</div><div class="small text-body-secondary">{{ \App\Models\Settings\PaymentMethod::TYPES[$m->type] ?? $m->type }}</div>
                                @if ($m->details)<div class="small text-body-secondary" style="white-space:pre-line">{{ \Illuminate\Support\Str::limit($m->details, 80) }}</div>@endif</td>
                            <td><x-admin.badge :tone="$m->is_active ? 'success' : 'muted'">{{ $m->is_active ? 'Active' : 'Off' }}</x-admin.badge></td>
                            <td class="text-end text-nowrap">@if ($canEdit)
                                <a href="{{ route('settings.payments', ['payment' => $m->id]) }}" class="btn btn-action"><i class="bi bi-pencil-square"></i></a>
                                <x-admin.delete-button :action="route('settings.payment-methods.destroy', $m)" confirm="Remove this payment method?" />@endif</td>
                        </tr>
                    @empty
                        <tr><td><x-admin.empty icon="credit-card" text="No payment methods (Cash, Bank transfer, JazzCash…)." /></td></tr>
                    @endforelse
                </tbody></table></div>
                @if ($canEdit)
                    <x-slot:footer>
                        <form method="POST" action="{{ $editPayment ? route('settings.payment-methods.update', $editPayment) : route('settings.payment-methods.store') }}" class="w-100">@csrf @if ($editPayment) @method('PUT') @endif
                            <div class="row g-2">
                                <div class="col-md-5"><input name="name" class="form-control" style="min-height:42px" placeholder="Name" value="{{ old('name', $editPayment?->name) }}" required></div>
                                <div class="col-md-7"><select name="type" class="form-select" style="min-height:42px">
                                    @foreach (\App\Models\Settings\PaymentMethod::TYPES as $k => $l)<option value="{{ $k }}" @selected(($editPayment?->type ?? 'cash') === $k)>{{ $l }}</option>@endforeach</select></div>
                                <div class="col-12"><textarea name="details" rows="2" class="form-control" placeholder="Details shown to customers / on invoices (account no., IBAN…)">{{ old('details', $editPayment?->details) }}</textarea></div>
                                <div class="col-12 d-flex align-items-center gap-3">
                                    <label class="d-flex gap-2 align-items-center small m-0"><input type="hidden" name="is_active" value="0"><input type="checkbox" class="form-check-input mt-0" name="is_active" value="1" @checked($editPayment?->is_active ?? true)> Active</label>
                                    <x-admin.button size="sm" icon="check2" class="ms-auto">{{ $editPayment ? 'Save' : 'Add method' }}</x-admin.button>
                                    @if ($editPayment)<a href="{{ route('settings.payments') }}" class="btn btn-sm btn-light">Cancel</a>@endif
                                </div>
                            </div>
                        </form>
                    </x-slot:footer>
                @endif
            </x-admin.card>
        </div>

        <div class="col-xl-6 d-grid gap-4 align-content-start">
            <x-admin.card flush :title="'Shipping methods ('.$shipping->count().')'">
                <div class="table-responsive"><table class="table app-table"><tbody>
                    @forelse ($shipping as $s)
                        <tr>
                            <td><div class="fw-semibold">{{ $s->name }}</div><div class="small text-body-secondary">{{ $s->delivery_time ?: '—' }}</div></td>
                            <td><div class="fw-semibold">{{ $s->fee > 0 ? $money($s->fee) : 'Free' }}</div>
                                @if ($s->free_over)<div class="small text-success">Free over {{ $money($s->free_over) }}</div>@endif</td>
                            <td><x-admin.badge :tone="$s->is_active ? 'success' : 'muted'">{{ $s->is_active ? 'Active' : 'Off' }}</x-admin.badge></td>
                            <td class="text-end text-nowrap">@if ($canEdit)
                                <a href="{{ route('settings.payments', ['shipping' => $s->id]) }}" class="btn btn-action"><i class="bi bi-pencil-square"></i></a>
                                <x-admin.delete-button :action="route('settings.shipping-methods.destroy', $s)" confirm="Remove this shipping method?" />@endif</td>
                        </tr>
                    @empty
                        <tr><td><x-admin.empty icon="truck" text="No shipping methods (Standard, Express, Pickup…)." /></td></tr>
                    @endforelse
                </tbody></table></div>
                @if ($canEdit)
                    <x-slot:footer>
                        <form method="POST" action="{{ $editShipping ? route('settings.shipping-methods.update', $editShipping) : route('settings.shipping-methods.store') }}" class="w-100">@csrf @if ($editShipping) @method('PUT') @endif
                            <div class="row g-2">
                                <div class="col-md-6"><input name="name" class="form-control" style="min-height:42px" placeholder="Name" value="{{ old('name', $editShipping?->name) }}" required></div>
                                <div class="col-md-6"><input name="delivery_time" class="form-control" style="min-height:42px" placeholder="Delivery time (2-4 days)" value="{{ old('delivery_time', $editShipping?->delivery_time) }}"></div>
                                <div class="col-md-6"><input name="fee" type="number" step="0.01" min="0" class="form-control" style="min-height:42px" placeholder="Fee" value="{{ old('fee', $editShipping?->fee ?? 0) }}" required></div>
                                <div class="col-md-6"><input name="free_over" type="number" step="0.01" min="0" class="form-control" style="min-height:42px" placeholder="Free over (optional)" value="{{ old('free_over', $editShipping?->free_over) }}"></div>
                                <div class="col-12 d-flex align-items-center gap-3">
                                    <label class="d-flex gap-2 align-items-center small m-0"><input type="hidden" name="is_active" value="0"><input type="checkbox" class="form-check-input mt-0" name="is_active" value="1" @checked($editShipping?->is_active ?? true)> Active</label>
                                    <x-admin.button size="sm" icon="check2" class="ms-auto">{{ $editShipping ? 'Save' : 'Add method' }}</x-admin.button>
                                    @if ($editShipping)<a href="{{ route('settings.payments') }}" class="btn btn-sm btn-light">Cancel</a>@endif
                                </div>
                            </div>
                        </form>
                    </x-slot:footer>
                @endif
            </x-admin.card>
        </div>
    </div>
</div>
@endsection
