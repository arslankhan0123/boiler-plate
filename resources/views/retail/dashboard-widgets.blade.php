@php
    $R = \App\Support\Retail\Dashboard::data(auth()->user());
    $cur = \App\Support\Settings::get('default_currency', 'PKR');
    $m = fn ($v) => $cur.' '.number_format((float) $v, 0);
@endphp
@if ($R)
    <div class="row g-4 mb-4">
        @if ($R['sales'])
            <div class="col-sm-6 col-xl-3"><x-admin.stat :label="'Sales today ('.$R['todayCount'].')'" :value="$m($R['today'])" icon="receipt" tone="primary" :href="route('sales.invoices.index', ['from' => today()->toDateString()])" /></div>
            <div class="col-sm-6 col-xl-3"><x-admin.stat label="Sales this month" :value="$m($R['month'])" icon="graph-up-arrow" tone="success" /></div>
            <div class="col-sm-6 col-xl-3"><x-admin.stat label="Receivable (customers)" :value="$m($R['receivable'])" icon="hourglass-split" tone="danger" :href="auth()->user()->can('sales.customers') ? route('sales.customers.index', ['only' => 'due']) : null" /></div>
        @endif
        @if ($R['purchase'])
            <div class="col-sm-6 col-xl-3"><x-admin.stat label="Payable (suppliers)" :value="$m($R['payable'])" icon="wallet2" tone="warning" :href="auth()->user()->can('purchase.suppliers') ? route('purchase.suppliers.index', ['only' => 'due']) : null" /></div>
            <div class="col-sm-6 col-xl-3"><x-admin.stat label="Purchases this month" :value="$m($R['purchaseMonth'])" icon="cart-plus" tone="info" /></div>
        @endif
        @if ($R['inventory'])
            <div class="col-sm-6 col-xl-3"><x-admin.stat label="Low stock items" :value="number_format($R['lowCount'])" icon="exclamation-triangle" tone="warning" :href="route('inventory.items.index', ['stock' => 'low'])" /></div>
            <div class="col-sm-6 col-xl-3"><x-admin.stat label="Expired / expiring (30d)" :value="number_format($R['expiring'])" icon="calendar-x" tone="danger" :href="route('inventory.items.index', ['stock' => 'expired', 'status' => 'all'])" /></div>
        @endif
    </div>
    <div class="row g-4 mb-4">
        @if ($R['sales'])
            <div class="col-xl-6">
                <x-admin.card title="Sales — last 7 days">
                    @php $max = max(1, collect($R['chart'])->max('total')); @endphp
                    <div class="dash-bars">
                        @foreach ($R['chart'] as $c)
                            <div class="dash-bar" title="{{ $m($c['total']) }}"><span style="height: {{ max(3, $c['total'] / $max * 100) }}%"></span><small>{{ $c['day'] }}</small></div>
                        @endforeach
                    </div>
                </x-admin.card>
            </div>
            <div class="col-xl-6">
                <x-admin.card flush title="Top items this month">
                    <div class="table-responsive"><table class="table app-table mb-0"><tbody>
                        @forelse ($R['top'] as $t)<tr><td class="fw-semibold">{{ $t->name }}</td><td class="text-end small text-body-secondary">{{ \App\Support\Inventory\StockService::fmt((float) $t->qty) }} sold</td><td class="text-end fw-semibold">{{ $m($t->amount) }}</td></tr>
                        @empty<tr><td><x-admin.empty icon="bar-chart" text="No sales this month yet." /></td></tr>@endforelse
                    </tbody></table></div>
                </x-admin.card>
            </div>
            <div class="col-xl-6">
                <x-admin.card flush title="Recent invoices">
                    <x-slot:actions><a href="{{ route('sales.invoices.index') }}" class="btn btn-sm btn-light">All</a></x-slot:actions>
                    <div class="table-responsive"><table class="table app-table mb-0"><tbody>
                        @forelse ($R['recent'] as $d)<tr><td><a href="{{ route('sales.invoices.show', $d) }}" class="fw-semibold text-decoration-none">{{ $d->number }}</a> @if ($d->is_pos)<x-admin.badge tone="primary">POS</x-admin.badge>@endif<div class="small text-body-secondary">{{ $d->partyName() }}</div></td>
                            <td class="text-end">{{ $m($d->grand_total) }}<div><x-admin.badge :tone="['paid' => 'success', 'partial' => 'warning', 'unpaid' => 'danger'][$d->payment_status] ?? 'muted'">{{ ucfirst($d->payment_status) }}</x-admin.badge></div></td></tr>
                        @empty<tr><td><x-admin.empty icon="receipt" text="No invoices yet." /></td></tr>@endforelse
                    </tbody></table></div>
                </x-admin.card>
            </div>
        @endif
        @if ($R['inventory'])
            <div class="col-xl-6">
                <x-admin.card flush title="Low stock">
                    <x-slot:actions><a href="{{ route('inventory.stock', ['only' => 'low']) }}" class="btn btn-sm btn-light">All</a></x-slot:actions>
                    <div class="table-responsive"><table class="table app-table mb-0"><tbody>
                        @forelse ($R['low'] as $i)<tr><td class="fw-semibold">{{ $i->displayName() }}</td><td class="text-end small text-body-secondary">alert at {{ \App\Support\Inventory\StockService::fmt($i->alert_qty) }}</td>
                            <td class="text-end fw-bold {{ $i->stock <= 0 ? 'text-danger' : 'text-warning' }}">{{ \App\Support\Inventory\StockService::fmt($i->stock) }} {{ $i->unit->name ?? '' }}</td></tr>
                        @empty<tr><td><x-admin.empty icon="check2-circle" text="All items are above their alert level." /></td></tr>@endforelse
                    </tbody></table></div>
                </x-admin.card>
            </div>
        @endif
    </div>
    @once
    @push('styles')
    <style>
        .dash-bars { display: flex; gap: 10px; align-items: flex-end; height: 180px; }
        .dash-bar { flex: 1; height: 100%; display: flex; flex-direction: column; justify-content: flex-end; align-items: center; gap: 6px; }
        .dash-bar span { width: 100%; max-width: 48px; border-radius: 8px 8px 0 0; background: linear-gradient(180deg, #6366f1, var(--app-primary, #4f46e5)); }
        .dash-bar small { color: var(--app-text-muted); font-size: .72rem; }
    </style>
    @endpush
    @endonce
@endif
