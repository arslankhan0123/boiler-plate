@extends('layouts.admin.main')
@section('title', 'Subscriptions')
@section('subtitle', 'Tenant trials & subscriptions')

@php
    $dayLabel = fn ($d) => $d == 30 ? '1 month' : ($d == 1 ? '1 day' : $d.' days');
    $trialOptions = collect($trialDays)->mapWithKeys(fn ($d) => [$d => $dayLabel($d)])->all();
    $tenantOptions = $tenants->pluck('name', 'id')->all();
    $planOptions = $plans->mapWithKeys(fn ($p) => [$p->id => $p->name.' — '.$p->currency.' '.number_format((float) $p->price_monthly, 2).'/mo'])->all();
    $statusTone = ['active' => 'success', 'pending' => 'warning', 'expired' => 'muted', 'cancelled' => 'danger'];
@endphp

@section('content')
<div class="app-content">
    <x-admin.page-header title="Subscriptions" subtitle="Grant trials, subscribe tenants to plans and manage their billing.">
        <x-admin.button variant="light" :href="route('plans.index')" icon="box-seam">Plans</x-admin.button>
        <x-admin.button variant="light" :href="route('platformbilling.edit')" icon="credit-card">Billing settings</x-admin.button>
    </x-admin.page-header>

    <x-admin.alerts />

    @if (session('payment_link'))
        <div class="app-alert app-alert--success mb-3"><i class="bi bi-link-45deg"></i>
            <div class="flex-grow-1">
                <div class="fw-semibold mb-1">Payment link — send this to the tenant</div>
                <div class="d-flex gap-2 align-items-center">
                    <input type="text" class="form-control form-control-sm" readonly value="{{ session('payment_link') }}" id="payLink" onclick="this.select()">
                    <button type="button" class="btn btn-sm btn-primary" onclick="navigator.clipboard.writeText(document.getElementById('payLink').value); window.Toast && Toast.success('Copied');">Copy</button>
                </div>
            </div>
        </div>
    @endif

    <div class="row g-3 mb-3">
        {{-- Grant a trial --}}
        <div class="col-lg-6">
            <x-admin.card title="Grant a trial" subtitle="Give a tenant free access for a set time. Trials are only visible to you.">
                <form method="POST" action="{{ route('subscriptions.trial') }}">
                    @csrf
                    <x-admin.select name="tenant_id" label="Tenant" :options="$tenantOptions" placeholder="Select a tenant" required />
                    <div class="row g-3">
                        <div class="col-sm-6"><x-admin.select name="days" label="Trial length" :options="$trialOptions" placeholder="Select length" required /></div>
                        <div class="col-sm-6"><x-admin.select name="plan_id" label="Modules from plan" :options="$planOptions" placeholder="All modules (blank)" /></div>
                    </div>
                    <p class="form-text">Leave the plan blank to trial every business module; pick a plan to trial only its modules.</p>
                    <x-admin.button icon="hourglass-split">Grant trial</x-admin.button>
                </form>
            </x-admin.card>
        </div>

        {{-- Subscribe a tenant --}}
        <div class="col-lg-6">
            <x-admin.card title="Subscribe a tenant" subtitle="Activate a paid plan directly (comped) or create a payment link.">
                <form method="POST" action="{{ route('subscriptions.subscribe') }}">
                    @csrf
                    <x-admin.select name="tenant_id" label="Tenant" :options="$tenantOptions" placeholder="Select a tenant" required />
                    <div class="row g-3">
                        <div class="col-sm-6"><x-admin.select name="plan_id" label="Plan" :options="$planOptions" placeholder="Select a plan" required /></div>
                        <div class="col-sm-6"><x-admin.select name="cycle" label="Billing cycle" :options="['monthly' => 'Monthly', 'yearly' => 'Yearly']" placeholder="Select cycle" required /></div>
                    </div>
                    <div class="row g-3">
                        <div class="col-sm-6"><x-admin.select name="mode" label="How" :options="['direct' => 'Activate now (comped, no charge)', 'charge' => 'Create a payment link']" placeholder="Select" required /></div>
                        <div class="col-sm-6"><x-admin.select name="gateway" label="Gateway (for link)" :options="['stripe' => 'Stripe', 'paypal' => 'PayPal']" placeholder="Auto" /></div>
                    </div>
                    @unless ($gatewaysReady)
                        <p class="form-text text-warning"><i class="bi bi-exclamation-triangle"></i> No gateway configured — only "Activate now (comped)" will work.</p>
                    @endunless
                    <x-admin.button icon="bag-check">Subscribe</x-admin.button>
                </form>
            </x-admin.card>
        </div>
    </div>

    <x-admin.card flush :title="'Subscriptions ('.$subscriptions->total().')'">
        <div class="table-responsive">
            <table class="table app-table table-hover align-middle">
                <thead><tr>
                    <th>Tenant</th><th>Plan</th><th>Type</th><th>Status</th><th class="text-end">Amount</th>
                    <th>Started</th><th>Ends</th><th>Left</th><th class="text-end">Actions</th>
                </tr></thead>
                <tbody>
                @forelse ($subscriptions as $sub)
                    <tr>
                        <td class="fw-semibold">{{ $tenantNames[$sub->tenant_id] ?? $sub->tenant_id }}</td>
                        <td>{{ $sub->plan?->name ?? '—' }} @if ($sub->billing_cycle)<span class="small text-body-secondary">({{ ucfirst($sub->billing_cycle) }})</span>@endif</td>
                        <td>
                            @if ($sub->type === 'trial')<x-admin.badge tone="primary" icon="hourglass">Trial</x-admin.badge>
                            @else<x-admin.badge tone="muted">Paid</x-admin.badge>@endif
                            @if ($sub->is_recurring)<x-admin.badge tone="success" icon="arrow-repeat">Recurring</x-admin.badge>@endif
                        </td>
                        <td><x-admin.badge :tone="$statusTone[$sub->status] ?? 'muted'">{{ ucfirst($sub->status) }}</x-admin.badge></td>
                        <td class="text-end text-nowrap">{{ $sub->currency }} {{ number_format((float) $sub->amount, 2) }}</td>
                        <td class="small text-nowrap">{{ $sub->starts_at?->format('d M Y') ?? '—' }}</td>
                        <td class="small text-nowrap">{{ $sub->ends_at?->format('d M Y') ?? '—' }}</td>
                        <td>@if ($sub->isActive() && $sub->ends_at)<span class="small">{{ $sub->daysLeft() }}d</span>@else<span class="small text-body-secondary">—</span>@endif</td>
                        <td class="text-end text-nowrap">
                            @if (in_array($sub->status, ['active', 'pending'], true))
                                <form method="POST" action="{{ route('subscriptions.cancel', $sub) }}" class="d-inline" onsubmit="return confirm('Cancel this subscription and revoke the tenant\'s access?')">
                                    @csrf
                                    <button type="submit" class="btn btn-action btn-action--danger" title="Cancel"><i class="bi bi-x-circle"></i></button>
                                </form>
                            @else
                                <span class="small text-body-secondary">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9"><x-admin.empty icon="receipt" text="No subscriptions yet." /></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($subscriptions->hasPages())
            <x-slot:footer>{{ $subscriptions->links('pagination::bootstrap-5') }}</x-slot:footer>
        @endif
    </x-admin.card>
</div>
@endsection
