@extends('layouts.admin.main')
@section('title', 'Plans')
@section('subtitle', 'Subscription plans you sell to tenants')

@section('content')
<div class="app-content">
    <x-admin.page-header title="Subscription Plans" subtitle="Bundle modules into plans with a monthly and yearly price.">
        <x-admin.button :href="route('plans.create')" icon="plus-lg">Add Plan</x-admin.button>
    </x-admin.page-header>

    <x-admin.alerts />

    @unless ($gatewaysReady)
        <div class="app-alert app-alert--warning mb-3"><i class="bi bi-exclamation-triangle-fill"></i>
            <div>No payment gateway is configured yet. Add your Stripe or PayPal keys in
                <a href="{{ route('platformbilling.edit') }}">Billing settings</a> so tenants can pay and recurring billing works.</div>
        </div>
    @endunless

    <x-admin.card flush :title="'All plans ('.$plans->count().')'">
        <div class="table-responsive">
            <table class="table app-table table-hover align-middle">
                <thead><tr>
                    <th>Plan</th><th>Modules</th><th class="text-end">Monthly</th><th class="text-end">Yearly</th>
                    <th>Visibility</th><th>Subs</th><th>Gateways</th><th class="text-end">Actions</th>
                </tr></thead>
                <tbody>
                @forelse ($plans as $plan)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $plan->name }}</div>
                            @if ($plan->description)<div class="small text-body-secondary text-truncate" style="max-width:260px">{{ $plan->description }}</div>@endif
                        </td>
                        <td style="max-width:280px">
                            @foreach (collect($plan->moduleNames())->take(4) as $name)
                                <x-admin.badge tone="primary" class="mb-1">{{ $name }}</x-admin.badge>
                            @endforeach
                            @if (count($plan->moduleNames()) > 4)<x-admin.badge tone="muted">+{{ count($plan->moduleNames()) - 4 }}</x-admin.badge>@endif
                        </td>
                        <td class="text-end text-nowrap">{{ $plan->currency }} {{ number_format((float) $plan->price_monthly, 2) }}</td>
                        <td class="text-end text-nowrap">
                            {{ $plan->currency }} {{ number_format((float) $plan->price_yearly, 2) }}
                            @if ($plan->yearlySavingPercent() > 0)<div class="small text-success">save {{ $plan->yearlySavingPercent() }}%</div>@endif
                        </td>
                        <td>
                            @if (! $plan->is_active)
                                <x-admin.badge tone="danger">Inactive</x-admin.badge>
                            @elseif ($plan->is_public)
                                <x-admin.badge tone="success">Public</x-admin.badge>
                            @else
                                <x-admin.badge tone="muted">Hidden</x-admin.badge>
                            @endif
                        </td>
                        <td>{{ $plan->subscriptions_count }}</td>
                        <td class="text-nowrap">
                            @if ($plan->stripe_product_id)<x-admin.badge tone="primary" icon="stripe">Stripe</x-admin.badge>@endif
                            @if ($plan->paypal_product_id)<x-admin.badge tone="primary" icon="paypal">PayPal</x-admin.badge>@endif
                            @if (! $plan->stripe_product_id && ! $plan->paypal_product_id)<span class="small text-body-secondary">—</span>@endif
                        </td>
                        <td class="text-end text-nowrap">
                            @if ($gatewaysReady)
                                <form action="{{ route('plans.sync', $plan) }}" method="POST" class="d-inline" title="Create / update this plan on Stripe & PayPal">
                                    @csrf
                                    <button type="submit" class="btn btn-action" title="Sync to gateways"><i class="bi bi-arrow-repeat"></i></button>
                                </form>
                            @endif
                            <a href="{{ route('plans.edit', $plan) }}" class="btn btn-action" title="Edit"><i class="bi bi-pencil-square"></i></a>
                            <x-admin.delete-button :action="route('plans.destroy', $plan)" :confirm="'Delete plan '.$plan->name.'?'" />
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8"><x-admin.empty icon="box-seam" text="No plans yet. Create your first plan." /></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.card>
</div>
@endsection
