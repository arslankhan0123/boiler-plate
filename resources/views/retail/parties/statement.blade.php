@php
    $c = $side === 'customer';
    $S = \App\Support\Settings::class;
    $money = fn ($v) => number_format((float) $v, 2);
@endphp
<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><title>Statement — {{ $party->company_name }}</title>
<style>
    body { font-family: Arial, sans-serif; font-size: 12px; color: #1e293b; background: #f1f5f9; margin: 0; }
    .page { max-width: 210mm; margin: 16px auto; background: #fff; padding: 12mm; box-shadow: 0 2px 18px rgba(0,0,0,.08); }
    h1 { margin: 0; font-size: 18px; } h2 { margin: 0; color: #4f46e5; font-size: 16px; } .muted { color: #64748b; }
    .top { display: flex; justify-content: space-between; border-bottom: 3px solid #4f46e5; padding-bottom: 10px; margin-bottom: 12px; }
    table { width: 100%; border-collapse: collapse; } th { background: #4f46e5; color: #fff; text-align: left; padding: 6px; font-size: 11px; } td { padding: 6px; border-bottom: 1px solid #e2e8f0; } .r { text-align: right; }
    .sum { display: flex; gap: 10px; margin: 12px 0; } .sum div { flex: 1; background: #f8fafc; border-radius: 8px; padding: 8px 10px; } .sum b { display: block; font-size: 15px; }
    .toolbar { text-align: center; padding: 10px; } .toolbar form { display: inline-flex; gap: 6px; align-items: center; } .toolbar input, .toolbar button, .toolbar a { padding: 7px 10px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 13px; }
    .toolbar button { background: #4f46e5; color: #fff; border: 0; cursor: pointer; } .toolbar a { text-decoration: none; color: #1e293b; background: #fff; }
    @media print { body { background: #fff; } .page { box-shadow: none; margin: 0; max-width: none; } .toolbar { display: none; } }
</style></head>
<body>
<div class="toolbar">
    <a href="{{ $c ? route('sales.customers.show', $party) : route('purchase.suppliers.show', $party) }}">← Back</a>
    <form method="GET">From <input type="date" name="from" value="{{ request('from') }}"> To <input type="date" name="to" value="{{ request('to') }}"><button>Apply</button></form>
    <button onclick="window.print()" style="padding:7px 14px;border-radius:8px;border:0;background:#4f46e5;color:#fff;cursor:pointer">Print / PDF</button>
</div>
<div class="page">
    <div class="top">
        <div><h1>{{ $S::companyName() }}</h1><div class="muted">{{ $S::get('address') }} {{ $S::get('city') }} · {{ $S::get('phone') }}</div></div>
        <div style="text-align:right"><h2>{{ $c ? 'Customer' : 'Supplier' }} Statement</h2><div class="muted">{{ request('from') ? \Illuminate\Support\Carbon::parse(request('from'))->format('d M Y') : 'Beginning' }} – {{ request('to') ? \Illuminate\Support\Carbon::parse(request('to'))->format('d M Y') : 'Today' }}</div></div>
    </div>
    <b>{{ $party->company_name }}</b> <span class="muted">{{ $party->mobile ?? $party->phone }} {{ $party->email }}</span>
    <div class="sum">
        <div><span class="muted">Opening</span><b>{{ $money($opening) }}</b></div>
        <div><span class="muted">{{ $c ? 'Invoiced' : 'Purchased' }} (+)</span><b>{{ $money($debit) }}</b></div>
        <div><span class="muted">{{ $c ? 'Paid / returned' : 'Paid / returned' }} (−)</span><b>{{ $money($credit) }}</b></div>
        <div><span class="muted">Closing {{ $c ? 'receivable' : 'payable' }}</span><b style="color:#b91c1c">{{ $money($closing) }}</b></div>
    </div>
    <table>
        <thead><tr><th>Date</th><th>Reference</th><th>Description</th><th class="r">{{ $c ? 'Debit' : 'Bill' }}</th><th class="r">{{ $c ? 'Credit' : 'Paid' }}</th><th class="r">Balance</th></tr></thead>
        <tbody>
            <tr><td colspan="5"><i>Opening balance</i></td><td class="r">{{ $money($opening) }}</td></tr>
            @foreach ($rows as $row)
                <tr><td>{{ \Illuminate\Support\Carbon::parse($row['date'])->format('d M Y') }}</td><td>{{ $row['ref'] }}</td><td>{{ $row['desc'] }}</td>
                    <td class="r">{{ $row['debit'] ? $money($row['debit']) : '' }}</td><td class="r">{{ $row['credit'] ? $money($row['credit']) : '' }}</td><td class="r"><b>{{ $money($row['balance']) }}</b></td></tr>
            @endforeach
        </tbody>
        <tfoot><tr><th colspan="3">Total</th><th class="r">{{ $money($debit) }}</th><th class="r">{{ $money($credit) }}</th><th class="r">{{ $money($closing) }}</th></tr></tfoot>
    </table>
    <p class="muted" style="margin-top:14px">Printed {{ now()->format('d M Y h:i A') }}. Advances are not included in this balance.</p>
</div>
</body></html>
