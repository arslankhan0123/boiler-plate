@php
    $S = \App\Support\Settings::class;
    $s = $S::all();
    $sale = $meta['side'] === 'sale';
    $cur = $S::get('default_currency', 'PKR');
    $money = fn ($v) => number_format((float) $v, 2);
    $q = fn ($v) => \App\Support\Inventory\StockService::fmt((float) $v);
    $party = $sale ? $doc->customer : $doc->supplier;
    $title = $doc->type === 'invoice' ? ($S::get('invoice_title') ?: ($doc->tax_total > 0 ? 'Tax Invoice' : 'Invoice')) : $meta['one'];
    $showTax = $S::get('show_tax_column', '1') === '1';
    $words = class_exists(\NumberFormatter::class) ? ucfirst((new \NumberFormatter('en', \NumberFormatter::SPELLOUT))->format(round($doc->grand_total))) : null;
    $logo = $S::logoUrl();
@endphp
<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><title>{{ $title }} {{ $doc->number }}</title>
<style>
    * { box-sizing: border-box; } body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #1e293b; margin: 0; background: #f1f5f9; }
    .page { width: 210mm; min-height: 297mm; margin: 16px auto; background: #fff; padding: 14mm; box-shadow: 0 2px 18px rgba(0,0,0,.08); }
    .head { display: flex; justify-content: space-between; gap: 20px; border-bottom: 3px solid #4f46e5; padding-bottom: 12px; }
    .head img { max-height: 64px; max-width: 180px; } .co h1 { margin: 0 0 4px; font-size: 20px; } .co div { color: #475569; line-height: 1.5; }
    .ttl { text-align: right; } .ttl h2 { margin: 0; font-size: 22px; color: #4f46e5; text-transform: uppercase; letter-spacing: .04em; } .ttl table td { padding: 1px 0 1px 12px; }
    .parties { display: flex; gap: 20px; margin: 16px 0; } .box { flex: 1; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 12px; line-height: 1.5; }
    .box h4 { margin: 0 0 4px; font-size: 10px; text-transform: uppercase; color: #64748b; letter-spacing: .05em; }
    table.items { width: 100%; border-collapse: collapse; } .items th { background: #4f46e5; color: #fff; font-size: 11px; text-align: left; padding: 7px 6px; }
    .items td { padding: 7px 6px; border-bottom: 1px solid #e2e8f0; vertical-align: top; } .r { text-align: right; } .items tr:nth-child(even) td { background: #f8fafc; }
    .bottom { display: flex; gap: 20px; margin-top: 14px; } .bottom .left { flex: 1; } .tot { width: 300px; border-collapse: collapse; } .tot td { padding: 4px 6px; }
    .tot .grand td { background: #4f46e5; color: #fff; font-weight: bold; font-size: 14px; } .muted { color: #64748b; } .terms { margin-top: 12px; white-space: pre-line; font-size: 11px; color: #475569; }
    .sign { display: flex; justify-content: space-between; margin-top: 50px; } .sign div { border-top: 1px solid #94a3b8; width: 180px; text-align: center; padding-top: 4px; color: #64748b; }
    .foot { text-align: center; margin-top: 18px; color: #64748b; font-size: 11px; } .bar { text-align: center; margin: 14px 0; }
    .toolbar { text-align: center; padding: 10px; } .toolbar button, .toolbar a { padding: 8px 16px; border-radius: 8px; border: 0; background: #4f46e5; color: #fff; cursor: pointer; text-decoration: none; font-size: 13px; margin: 0 4px; }
    @media print { body { background: #fff; } .page { box-shadow: none; margin: 0; width: auto; min-height: 0; padding: 8mm; } .toolbar { display: none; } @page { size: A4; margin: 6mm; } }
</style></head>
<body>
@if (empty($pdf))<div class="toolbar"><button onclick="window.print()">Print</button><a href="?format=thermal">Thermal</a><a href="?format=a4&pdf=1">PDF</a></div>@endif
<div class="page">
    <div class="head">
        <div class="co">
            @if ($logo)<img src="{{ $logo }}" alt=""><br>@endif
            <h1>{{ $S::companyName() }}</h1>
            <div>{{ trim(($s['address'] ?? '').', '.($s['city'] ?? ''), ', ') }}<br>
                @if ($s['phone'] ?? null)Phone: {{ $s['phone'] }} @endif @if ($s['email'] ?? null)· {{ $s['email'] }}@endif<br>
                @if ($s['ntn'] ?? null)NTN: {{ $s['ntn'] }} @endif @if ($s['strn'] ?? null)· STRN: {{ $s['strn'] }}@endif</div>
        </div>
        <div class="ttl">
            <h2>{{ $title }}</h2>
            <table>
                <tr><td class="muted">Number</td><td><b>{{ $doc->number }}</b></td></tr>
                <tr><td class="muted">Date</td><td>{{ $doc->date->format('d M Y') }}</td></tr>
                @if ($doc->due_date)<tr><td class="muted">{{ $doc->type === 'quotation' ? 'Valid until' : 'Due date' }}</td><td>{{ $doc->due_date->format('d M Y') }}</td></tr>@endif
                @if ($doc->reference_no)<tr><td class="muted">Reference</td><td>{{ $doc->reference_no }}</td></tr>@endif
                @if ($doc->parent)<tr><td class="muted">Against</td><td>{{ $doc->parent->number }}</td></tr>@endif
                @if ($doc->salesman)<tr><td class="muted">Salesman</td><td>{{ $doc->salesman->name }}</td></tr>@endif
            </table>
        </div>
    </div>

    <div class="parties">
        <div class="box"><h4>{{ $sale ? 'Bill to' : 'Supplier' }}</h4>
            <b>{{ $party->company_name ?? 'Walk-in Customer' }}</b><br>
            @if ($party?->contact_person){{ $party->contact_person }}<br>@endif
            {{ trim(($party->address ?? $party->street ?? '').' '.($party->city ?? '')) }}
            @if ($party?->mobile ?? $party?->phone)<br>Phone: {{ $party->mobile ?? $party->phone }}@endif
            @if ($party?->vat_number)<br>Tax no.: {{ $party->vat_number }}@endif
        </div>
        @if ($sale && ($party?->ship_address))
            <div class="box"><h4>Ship to</h4>{{ $party->ship_address }}<br>{{ $party->ship_city }} {{ $party->ship_zip }}</div>
        @endif
        <div class="box"><h4>From warehouse</h4>{{ $doc->warehouse->name ?? '' }}<br>{{ $doc->warehouse->address ?? '' }}</div>
    </div>

    <table class="items">
        <thead><tr><th>#</th><th>Item</th><th class="r">Qty</th><th class="r">Price</th><th class="r">Discount</th>@if ($showTax)<th class="r">Tax</th>@endif<th class="r">Amount</th></tr></thead>
        <tbody>
        @foreach ($doc->items as $i => $l)
            <tr><td>{{ $i + 1 }}</td>
                <td><b>{{ $l->item?->displayName() }}</b><br><span class="muted">{{ $l->item->code ?? '' }}{{ $l->item?->hsn ? ' · HSN '.$l->item->hsn : '' }}</span>@if ($l->description)<br>{{ $l->description }}@endif</td>
                <td class="r">{{ $q($l->qty) }} {{ $l->item->unit->name ?? '' }}</td><td class="r">{{ $money($l->unit_price) }}</td>
                <td class="r">{{ $l->discount_amt > 0 ? $money($l->discount_amt) : '—' }}</td>
                @if ($showTax)<td class="r">{{ $l->tax_amt > 0 ? $money($l->tax_amt) : '—' }}@if ($l->tax_rate > 0)<br><span class="muted">{{ +$l->tax_rate }}%</span>@endif</td>@endif
                <td class="r"><b>{{ $money($l->total) }}</b></td></tr>
        @endforeach
        </tbody>
    </table>

    <div class="bottom">
        <div class="left">
            @if ($words)<div><span class="muted">Amount in words:</span> <b>{{ $words }} only</b></div>@endif
            @if ($meta['payments'] && $doc->payments->isNotEmpty())
                <div style="margin-top:10px"><span class="muted">Payments:</span>
                    @foreach ($doc->payments as $p)<div>{{ $p->date->format('d M Y') }} · {{ $p->method }}{{ $p->cheque_no ? ' (chq '.$p->cheque_no.')' : '' }} · {{ $money($p->amount) }}</div>@endforeach</div>
            @endif
            @if ($doc->note)<div class="terms"><b>Note:</b> {{ $doc->note }}</div>@endif
            @if ($doc->terms)<div class="terms"><b>Terms & conditions</b><br>{{ $doc->terms }}</div>@endif
        </div>
        <table class="tot">
            <tr><td>Subtotal</td><td class="r">{{ $money($doc->subtotal) }}</td></tr>
            @if ($doc->other_charges_amt > 0)<tr><td>Other charges</td><td class="r">{{ $money($doc->other_charges_amt) }}</td></tr>@endif
            @if ($doc->discount_amt > 0)<tr><td>Discount</td><td class="r">−{{ $money($doc->discount_amt) }}</td></tr>@endif
            @if ($doc->coupon_amt > 0)<tr><td>Coupon ({{ $doc->coupon_code }})</td><td class="r">−{{ $money($doc->coupon_amt) }}</td></tr>@endif
            @if ($doc->round_off != 0)<tr><td>Round off</td><td class="r">{{ $money($doc->round_off) }}</td></tr>@endif
            <tr class="grand"><td>Total ({{ $cur }})</td><td class="r">{{ $money($doc->grand_total) }}</td></tr>
            @if ($doc->tax_total > 0)
                @php $taxParts = $doc->taxSummary(); @endphp
                @if (count($taxParts) > 1)
                    @foreach ($taxParts as $tName => $tAmt)<tr><td class="muted">{{ $tName }}</td><td class="r muted">{{ $money($tAmt) }}</td></tr>@endforeach
                @else
                    <tr><td class="muted">Tax included</td><td class="r muted">{{ $money($doc->tax_total) }}</td></tr>
                @endif
            @endif
            @if ($meta['payments'])
                <tr><td>{{ in_array($doc->type, ['sale_return', 'purchase_return']) ? 'Refunded' : 'Paid' }}</td><td class="r">{{ $money($doc->paid_amount) }}</td></tr>
                <tr><td><b>Balance</b></td><td class="r"><b>{{ $money($doc->due()) }}</b></td></tr>
            @endif
            @if ($doc->type === 'invoice' && $S::get('show_previous_due', '1') === '1' && $party && ! $party->is_default)
                <tr><td class="muted">Previous due</td><td class="r muted">{{ $money($doc->previous_due) }}</td></tr>
                <tr><td><b>Total due now</b></td><td class="r"><b>{{ $money($partyDue) }}</b></td></tr>
            @endif
        </table>
    </div>

    @if (empty($pdf))<div class="bar"><svg id="bc"></svg></div>@endif
    <div class="sign"><div>Received by</div><div>Authorised signature</div></div>
    @if ($S::get('invoice_footer'))<div class="foot">{{ $S::get('invoice_footer') }}</div>@endif
    <div class="foot">Printed {{ now()->format('d M Y h:i A') }}{{ $by ? ' · Created by '.$by : '' }}</div>
</div>
@if (empty($pdf))
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script>try { JsBarcode('#bc', @js($doc->number), { height: 40, fontSize: 12, margin: 0 }); } catch (e) {} @if ($auto) window.addEventListener('load', () => setTimeout(() => window.print(), 300)); @endif</script>
@endif
</body></html>
