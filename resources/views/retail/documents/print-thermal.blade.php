@php
    $S = \App\Support\Settings::class;
    $s = $S::all();
    $sale = $meta['side'] === 'sale';
    $money = fn ($v) => number_format((float) $v, 2);
    $q = fn ($v) => \App\Support\Inventory\StockService::fmt((float) $v);
    $party = $sale ? $doc->customer : $doc->supplier;
    $w = $S::get('thermal_width', '80') === '58' ? '54mm' : '76mm';
    $change = $doc->payments->sum('change_return');
    $tendered = $doc->payments->sum('amount') + $change;
@endphp
<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><title>Receipt {{ $doc->number }}</title>
<style>
    * { box-sizing: border-box; } body { margin: 0; font-family: 'Courier New', monospace; font-size: 12px; color: #000; background: #e2e8f0; }
    .rc { width: {{ $w }}; margin: 10px auto; background: #fff; padding: 8px; } .c { text-align: center; } .r { text-align: right; } .b { font-weight: bold; }
    .hr { border-top: 1px dashed #000; margin: 6px 0; } table { width: 100%; border-collapse: collapse; } td { padding: 1px 0; vertical-align: top; }
    h2 { margin: 2px 0; font-size: 15px; } img { max-width: 60%; max-height: 50px; } .big td { font-size: 14px; font-weight: bold; }
    .toolbar { text-align: center; padding: 8px; font-family: Arial; } .toolbar button, .toolbar a { padding: 7px 14px; border-radius: 8px; border: 0; background: #4f46e5; color: #fff; text-decoration: none; margin: 0 3px; cursor: pointer; font-size: 13px; }
    @media print { body { background: #fff; } .rc { margin: 0; width: 100%; padding: 0; } .toolbar { display: none; } @page { margin: 2mm; size: {{ $w }} auto; } }
</style></head>
<body>
<div class="toolbar"><button onclick="window.print()">Print</button><a href="?format=a4">A4</a>@if ($doc->is_pos && auth()->user()->can('pos.sell'))<a href="{{ route('pos') }}">New sale</a>@endif</div>
<div class="rc">
    <div class="c">
        @if ($S::logoUrl())<img src="{{ $S::logoUrl() }}" alt=""><br>@endif
        <h2>{{ $S::companyName() }}</h2>
        {{ $s['address'] ?? '' }} {{ $s['city'] ?? '' }}<br>
        @if ($s['phone'] ?? null)Ph: {{ $s['phone'] }}<br>@endif
        @if ($s['ntn'] ?? null)NTN: {{ $s['ntn'] }}@endif @if ($s['strn'] ?? null) STRN: {{ $s['strn'] }}@endif
    </div>
    <div class="hr"></div>
    <div class="c b">{{ $doc->type === 'invoice' ? ($doc->tax_total > 0 ? 'SALES TAX INVOICE' : 'SALES RECEIPT') : strtoupper($meta['one']) }}</div>
    <table>
        <tr><td>No: {{ $doc->number }}</td><td class="r">{{ $doc->created_at?->format('d-m-Y h:i A') }}</td></tr>
        <tr><td colspan="2">{{ $sale ? 'Customer' : 'Supplier' }}: {{ $party->company_name ?? 'Walk-in' }}@if ($party?->mobile) ({{ $party->mobile }})@endif</td></tr>
        @if ($by)<tr><td colspan="2">Cashier: {{ $by }}</td></tr>@endif
        @if ($doc->salesman)<tr><td colspan="2">Salesman: {{ $doc->salesman->name }}</td></tr>@endif
    </table>
    <div class="hr"></div>
    <table>
        <tr class="b"><td>Item</td><td class="r">Qty</td><td class="r">Rate</td><td class="r">Amt</td></tr>
        @foreach ($doc->items as $l)
            <tr><td colspan="4">{{ $l->item?->displayName() }}</td></tr>
            <tr><td>@if ($l->discount_amt > 0)<small>disc {{ $money($l->discount_amt) }}</small>@endif</td><td class="r">{{ $q($l->qty) }}</td><td class="r">{{ $money($l->unit_price) }}</td><td class="r">{{ $money($l->total) }}</td></tr>
        @endforeach
    </table>
    <div class="hr"></div>
    <table>
        <tr><td>Items / Qty</td><td class="r">{{ $doc->items->count() }} / {{ $q($doc->items->sum('qty')) }}</td></tr>
        <tr><td>Subtotal</td><td class="r">{{ $money($doc->subtotal) }}</td></tr>
        @if ($doc->other_charges_amt > 0)<tr><td>Other charges</td><td class="r">{{ $money($doc->other_charges_amt) }}</td></tr>@endif
        @if ($doc->discount_amt > 0)<tr><td>Discount</td><td class="r">-{{ $money($doc->discount_amt) }}</td></tr>@endif
        @if ($doc->coupon_amt > 0)<tr><td>Coupon {{ $doc->coupon_code }}</td><td class="r">-{{ $money($doc->coupon_amt) }}</td></tr>@endif
        @if ($doc->round_off != 0)<tr><td>Round off</td><td class="r">{{ $money($doc->round_off) }}</td></tr>@endif
        <tr class="big"><td>TOTAL</td><td class="r">{{ $money($doc->grand_total) }}</td></tr>
        @if ($doc->tax_total > 0)<tr><td>Tax incl.</td><td class="r">{{ $money($doc->tax_total) }}</td></tr>@endif
    </table>
    @if ($meta['payments'])
        <div class="hr"></div>
        <table>
            @foreach ($doc->payments as $p)<tr><td>{{ $p->method }}</td><td class="r">{{ $money($p->amount + $p->change_return) }}</td></tr>@endforeach
            @if ($change > 0)<tr class="b"><td>Change</td><td class="r">{{ $money($change) }}</td></tr>@endif
            @if ($doc->due() > 0)<tr class="b"><td>Balance due</td><td class="r">{{ $money($doc->due()) }}</td></tr>@endif
            @if ($sale && $party && ! $party->is_default && $S::get('show_previous_due', '1') === '1')<tr><td>Total due</td><td class="r">{{ $money($partyDue) }}</td></tr>@endif
        </table>
    @endif
    <div class="hr"></div>
    <div class="c"><div id="qr" style="display:inline-block"></div></div>
    @if ($doc->terms)<div class="c" style="font-size:10px;white-space:pre-line">{{ $doc->terms }}</div>@endif
    <div class="c">{{ $S::get('invoice_footer') ?: 'Thank you for shopping with us!' }}</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
    try { new QRCode(document.getElementById('qr'), { text: @js($S::companyName().' | '.$doc->number.' | '.$doc->date->format('Y-m-d').' | '.$money($doc->grand_total)), width: 90, height: 90 }); } catch (e) {}
    @if ($auto) window.addEventListener('load', () => setTimeout(() => window.print(), 400)); @endif
</script>
</body></html>
