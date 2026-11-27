@php $S = \App\Support\Settings::class; $money = fn ($v) => number_format((float) $v, 2); @endphp
<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><title>{{ $p->code }}</title>
<style>
    body { font-family: Arial, sans-serif; font-size: 13px; background: #f1f5f9; margin: 0; color: #1e293b; }
    .box { max-width: 520px; margin: 20px auto; background: #fff; padding: 24px; border-radius: 10px; box-shadow: 0 2px 18px rgba(0,0,0,.08); }
    h1 { margin: 0; font-size: 18px; } h2 { margin: 14px 0; text-align: center; color: #4f46e5; letter-spacing: .05em; } table { width: 100%; } td { padding: 6px 0; border-bottom: 1px dashed #e2e8f0; } .r { text-align: right; }
    .amt { font-size: 22px; font-weight: bold; text-align: center; margin: 14px 0; } .sign { margin-top: 40px; display: flex; justify-content: space-between; } .sign div { border-top: 1px solid #94a3b8; width: 160px; text-align: center; padding-top: 4px; color: #64748b; font-size: 11px; }
    button { display: block; margin: 10px auto; padding: 8px 18px; border: 0; border-radius: 8px; background: #4f46e5; color: #fff; cursor: pointer; }
    @media print { body { background: #fff; } .box { box-shadow: none; } button { display: none; } }
</style></head>
<body>
<button onclick="window.print()">Print</button>
<div class="box">
    <h1>{{ $S::companyName() }}</h1><div style="color:#64748b">{{ $S::get('address') }} {{ $S::get('city') }} · {{ $S::get('phone') }}</div>
    <h2>{{ $p->direction === 'in' ? 'PAYMENT RECEIPT' : 'PAYMENT VOUCHER' }}</h2>
    <table>
        <tr><td>Receipt no.</td><td class="r"><b>{{ $p->code }}</b></td></tr>
        <tr><td>Date</td><td class="r">{{ $p->date->format('d M Y') }}</td></tr>
        <tr><td>{{ $p->direction === 'in' ? 'Received from' : 'Paid to' }}</td><td class="r">{{ $p->party->company_name ?? '—' }}</td></tr>
        <tr><td>Type</td><td class="r">{{ \App\Models\Retail\Payment::KINDS[$p->kind] ?? $p->kind }}</td></tr>
        @if ($p->document)<tr><td>Against</td><td class="r">{{ \App\Models\Retail\Document::TYPES[$p->document->type]['one'] }} {{ $p->document->number }}</td></tr>@endif
        <tr><td>Method</td><td class="r">{{ $p->method }}</td></tr>
        @if ($p->cheque_no)<tr><td>Cheque</td><td class="r">{{ $p->cheque_no }} {{ $p->cheque_date?->format('d M Y') }} ({{ $p->cheque_status }})</td></tr>@endif
        @if ($p->note)<tr><td>Note</td><td class="r">{{ $p->note }}</td></tr>@endif
    </table>
    <div class="amt">{{ $S::get('default_currency', 'PKR') }} {{ $money($p->amount) }}</div>
    <div class="sign"><div>{{ $by ?? 'Cashier' }}</div><div>Signature</div></div>
</div>
</body></html>
