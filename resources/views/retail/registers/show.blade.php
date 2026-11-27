@extends('layouts.admin.main')
@php $m = fn ($v) => number_format((float) $v, 2); $cur = \App\Support\Settings::get('default_currency', 'PKR'); @endphp
@section('title', 'Register #'.$reg->id)
@section('subtitle', 'POS · Cash Register')

@section('content')
<div class="app-content">
    <x-admin.page-header :title="'Register #'.$reg->id" :subtitle="($reg->warehouse->name ?? '').' · opened '.$reg->opened_at->format('d M Y h:i A').' by '.$openedBy" :back="route('pos.registers.index')">
        <x-admin.button variant="light" icon="printer" :href="route('pos.registers.report', $reg)">Report</x-admin.button>
        @if ($reg->status === 'open' && $isOwner)<x-admin.button icon="shop" :href="route('pos')">Back to POS</x-admin.button>@endif
    </x-admin.page-header>

    @if ($reg->status === 'closed')
        <div class="row g-4 mb-4">
            <div class="col-md-3"><x-admin.stat label="Expected cash" :value="$cur.' '.$m($reg->expected_cash)" icon="calculator" /></div>
            <div class="col-md-3"><x-admin.stat label="Counted cash" :value="$cur.' '.$m($reg->counted_cash)" icon="cash-stack" /></div>
            <div class="col-md-3"><x-admin.stat :label="$reg->over_short < 0 ? 'Short' : 'Over'" :value="$cur.' '.$m(abs($reg->over_short))" :icon="$reg->over_short < 0 ? 'arrow-down-circle' : 'arrow-up-circle'" :tone="$reg->over_short < 0 ? 'danger' : ($reg->over_short > 0 ? 'warning' : 'success')" /></div>
            <div class="col-md-3"><x-admin.stat label="Cash sales" :value="$cur.' '.$m($reg->cash_sales)" icon="receipt" tone="success" /></div>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-xl-7 d-grid gap-4 align-content-start">
            @if ($live)
                <div class="app-card"><div class="app-card__body">
                    <h3 class="app-aside-title">Live drawer</h3>
                    <table class="table table-sm mb-0">
                        <tr><td>Opening float</td><td class="text-end fw-semibold">{{ $m($reg->opening_cash) }}</td></tr>
                        <tr><td>+ Cash sales</td><td class="text-end text-success">{{ $m($live['cash_sales']) }}</td></tr>
                        <tr><td>− Cash refunds</td><td class="text-end text-danger">{{ $m($live['cash_refunds']) }}</td></tr>
                        <tr><td>+ Pay-ins</td><td class="text-end text-success">{{ $m($live['cash_in']) }}</td></tr>
                        <tr><td>− Pay-outs</td><td class="text-end text-danger">{{ $m($live['cash_out']) }}</td></tr>
                        <tr class="fw-bold" style="border-top:2px solid var(--app-border)"><td>Expected in drawer</td><td class="text-end fs-5">{{ $cur }} {{ $m($live['expected']) }}</td></tr>
                        <tr><td class="text-body-secondary">Other (card/bank) collected</td><td class="text-end text-body-secondary">{{ $m($live['other_sales']) }}</td></tr>
                        <tr><td class="text-body-secondary">Invoices · total sales</td><td class="text-end text-body-secondary">{{ $live['invoices'] }} · {{ $m($live['total_sales']) }}</td></tr>
                    </table>
                </div></div>
            @endif

            <x-admin.card flush title="Cash movements">
                <div class="table-responsive"><table class="table app-table"><tbody>
                    @forelse ($reg->movements as $mv)
                        <tr><td class="small">{{ $mv->created_at->format('d M h:i A') }}</td><td><x-admin.badge :tone="$mv->direction === 'in' ? 'success' : 'danger'">{{ $mv->direction === 'in' ? 'Pay-in' : 'Pay-out' }}</x-admin.badge></td>
                            <td class="small text-body-secondary">{{ $mv->reason }}</td><td class="text-end fw-semibold">{{ $m($mv->amount) }}</td></tr>
                    @empty
                        <tr><td><x-admin.empty icon="arrow-left-right" text="No manual cash movements." /></td></tr>
                    @endforelse
                </tbody></table></div>
                @if ($reg->status === 'open' && $isOwner)
                    <x-slot:footer>
                        <form method="POST" action="{{ route('pos.registers.movement', $reg) }}" class="row g-2 w-100 align-items-end">@csrf
                            <div class="col-md-3"><label class="form-label small">Type</label><select name="direction" class="form-select form-select-sm"><option value="in">Pay-in (cash added)</option><option value="out">Pay-out (cash taken)</option></select></div>
                            <div class="col-md-3"><label class="form-label small">Amount</label><input type="number" step="any" min="0.01" name="amount" class="form-control form-control-sm" required></div>
                            <div class="col-md-4"><label class="form-label small">Reason</label><input name="reason" class="form-control form-control-sm" placeholder="e.g. bank drop, change float"></div>
                            <div class="col-md-2"><button class="btn btn-sm btn-primary w-100">Record</button></div>
                        </form>
                    </x-slot:footer>
                @endif
            </x-admin.card>
        </div>

        <div class="col-xl-5 d-grid gap-4 align-content-start">
            @if ($canClose)
                <div class="app-card"><div class="app-card__body">
                    <h3 class="app-aside-title">Close register</h3>
                    @unless ($isOwner)<p class="small text-warning-emphasis mb-2"><i class="bi bi-shield-lock"></i> Manager override — closing {{ $openedBy }}'s drawer.</p>@endunless
                    <p class="small text-body-secondary">Count all cash in the drawer and enter the total. Over/short is worked out against the expected {{ $cur }} {{ $m($live['expected']) }}.</p>
                    <form method="POST" action="{{ route('pos.registers.close', $reg) }}" onsubmit="return confirm('Close this register? This cannot be undone.')">@csrf
                        <x-admin.input name="counted_cash" type="number" step="any" min="0" label="Counted cash" required autofocus />
                        <div class="mb-3"><label class="form-label">Note <span class="opt">(optional)</span></label><textarea name="close_note" rows="2" class="form-control"></textarea></div>
                        <x-admin.button icon="lock" class="btn-lg-app w-100">Close Register</x-admin.button>
                    </form>
                </div></div>
            @elseif ($reg->status === 'open')
                <div class="app-card"><div class="app-card__body text-body-secondary small">Only {{ $openedBy }} (who opened this register) or a manager can close it.</div></div>
            @endif
            <x-admin.card title="Details">
                <dl class="row small mb-0">
                    <dt class="col-5 text-body-secondary">Warehouse</dt><dd class="col-7 text-end">{{ $reg->warehouse->name ?? '—' }}</dd>
                    <dt class="col-5 text-body-secondary">Opened by</dt><dd class="col-7 text-end">{{ $openedBy }}</dd>
                    <dt class="col-5 text-body-secondary">Opened at</dt><dd class="col-7 text-end">{{ $reg->opened_at->format('d M Y h:i A') }}</dd>
                    <dt class="col-5 text-body-secondary">Opening float</dt><dd class="col-7 text-end">{{ $m($reg->opening_cash) }}</dd>
                    @if ($reg->status === 'closed')
                        <dt class="col-5 text-body-secondary">Closed by</dt><dd class="col-7 text-end">{{ $closedBy }}</dd>
                        <dt class="col-5 text-body-secondary">Closed at</dt><dd class="col-7 text-end">{{ $reg->closed_at->format('d M Y h:i A') }}</dd>
                    @endif
                </dl>
                @if ($reg->open_note)<p class="small mt-3 mb-0"><b>Open note:</b> {{ $reg->open_note }}</p>@endif
                @if ($reg->close_note)<p class="small mt-2 mb-0"><b>Close note:</b> {{ $reg->close_note }}</p>@endif
            </x-admin.card>
        </div>
    </div>
</div>
@endsection
