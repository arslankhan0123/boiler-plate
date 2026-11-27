@php $S = \App\Support\Settings::class; $m = fn ($v) => number_format((float) $v, 2); $x = $live ?? ['expected' => $reg->expected_cash, 'cash_sales' => $reg->cash_sales, 'cash_refunds' => $reg->cash_refunds, 'other_sales' => $reg->other_sales, 'cash_in' => $reg->cash_in, 'cash_out' => $reg->cash_out, 'invoices' => null, 'total_sales' => null]; @endphp
<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><title>Register #{{ $reg->id }}</title>
<style>
    body { font-family: 'Courier New', monospace; font-size: 12px; background: #e2e8f0; margin: 0; }
    .rc { width: 76mm; margin: 10px auto; background: #fff; padding: 10px; } .c { text-align: center; } .hr { border-top: 1px dashed #000; margin: 6px 0; } .r { text-align: right; } table { width: 100%; } td { padding: 1px 0; } .b { font-weight: bold; }
    .toolbar { text-align: center; padding: 8px; font-family: Arial; } .toolbar button { padding: 7px 14px; border: 0; border-radius: 8px; background: #4f46e5; color: #fff; cursor: pointer; }
    @media print { body { background: #fff; } .rc { margin: 0; width: 100%; } .toolbar { display: none; } @page { margin: 2mm; } }
</style></head>
<body>
<div class="toolbar"><button onclick="window.print()">Print</button></div>
<div class="rc">
    <div class="c b">{{ $S::companyName() }}</div>
    <div class="c">REGISTER REPORT #{{ $reg->id }}</div>
    <div class="hr"></div>
    <table>
        <tr><td>Warehouse</td><td class="r">{{ $reg->warehouse->name ?? '—' }}</td></tr>
        <tr><td>Opened</td><td class="r">{{ $reg->opened_at->format('d-m-y H:i') }}</td></tr>
        <tr><td>By</td><td class="r">{{ $openedBy }}</td></tr>
        @if ($reg->status === 'closed')<tr><td>Closed</td><td class="r">{{ $reg->closed_at->format('d-m-y H:i') }}</td></tr><tr><td>By</td><td class="r">{{ $closedBy }}</td></tr>@endif
    </table>
    <div class="hr"></div>
    <table>
        <tr><td>Opening float</td><td class="r">{{ $m($reg->opening_cash) }}</td></tr>
        <tr><td>Cash sales</td><td class="r">{{ $m($x['cash_sales']) }}</td></tr>
        <tr><td>Cash refunds</td><td class="r">-{{ $m($x['cash_refunds']) }}</td></tr>
        <tr><td>Pay-ins</td><td class="r">{{ $m($x['cash_in']) }}</td></tr>
        <tr><td>Pay-outs</td><td class="r">-{{ $m($x['cash_out']) }}</td></tr>
        <tr class="b"><td>Expected cash</td><td class="r">{{ $m($x['expected']) }}</td></tr>
        <tr><td>Other collected</td><td class="r">{{ $m($x['other_sales']) }}</td></tr>
    </table>
    @if ($reg->status === 'closed')
        <div class="hr"></div>
        <table>
            <tr class="b"><td>Counted cash</td><td class="r">{{ $m($reg->counted_cash) }}</td></tr>
            <tr class="b"><td>{{ $reg->over_short < 0 ? 'SHORT' : 'OVER' }}</td><td class="r">{{ $m(abs($reg->over_short)) }}</td></tr>
        </table>
    @endif
    <div class="hr"></div>
    <div class="c">{{ now()->format('d M Y h:i A') }}</div>
</div>
<script>@if (request('auto')) window.print(); @endif</script>
</body></html>
