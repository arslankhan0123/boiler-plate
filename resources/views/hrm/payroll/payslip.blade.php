@php
    $money = fn ($v) => \App\Support\Hrm\Hrm::money($v);
    $e = $payslip->employee;
    $deductions = $payslip->unpaid_deduction + $payslip->other_deductions;
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payslip {{ $payslip->number }} — {{ $e->name }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; } body { font-family: Inter, sans-serif; background: #f1f5f9; color: #0f172a; margin: 0; padding: 32px 16px; font-size: 14px; }
        .sheet { max-width: 820px; margin: 0 auto; background: #fff; border-radius: 16px; box-shadow: 0 10px 40px rgba(15,23,42,.08); overflow: hidden; }
        .head { background: linear-gradient(135deg, #1e1b4b, #4338ca 60%, #6d28d9); color: #fff; padding: 28px 36px; display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; }
        .head h1 { margin: 0; font-size: 22px; } .head small { opacity: .75; }
        .tag { background: rgba(255,255,255,.15); padding: 6px 12px; border-radius: 99px; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; }
        .body { padding: 32px 36px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 6px 32px; margin-bottom: 28px; }
        .grid div span { color: #64748b; display: inline-block; width: 120px; }
        table { width: 100%; border-collapse: collapse; } th, td { padding: 11px 0; border-bottom: 1px solid #e2e8f0; text-align: left; }
        th { font-size: 11px; text-transform: uppercase; letter-spacing: .06em; color: #64748b; } td.r, th.r { text-align: right; }
        .cols { display: grid; grid-template-columns: 1fr 1fr; gap: 32px; }
        .net { margin-top: 28px; background: #eef2ff; border-radius: 12px; padding: 18px 22px; display: flex; justify-content: space-between; align-items: center; }
        .net b { font-size: 24px; color: #312e81; }
        .foot { padding: 18px 36px; border-top: 1px solid #e2e8f0; color: #94a3b8; font-size: 12px; display: flex; justify-content: space-between; }
        .actions { max-width: 820px; margin: 0 auto 16px; display: flex; justify-content: flex-end; gap: 8px; }
        .btn { border: 0; border-radius: 10px; padding: 10px 18px; font-weight: 600; cursor: pointer; font-family: inherit; text-decoration: none; font-size: 14px; }
        .btn-p { background: linear-gradient(135deg, #1e1b4b, #312e81); color: #fff; } .btn-l { background: #fff; color: #334155; border: 1px solid #e2e8f0; }
        @media print { body { background: #fff; padding: 0; } .actions { display: none; } .sheet { box-shadow: none; border-radius: 0; }
            .head { -webkit-print-color-adjust: exact; print-color-adjust: exact; } .net { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }
        @media (max-width: 640px) { .grid, .cols { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="actions">
        <a href="javascript:history.back()" class="btn btn-l">← Back</a>
        <button onclick="window.print()" class="btn btn-p">Print / Save PDF</button>
    </div>
    <div class="sheet">
        <div class="head">
            <div><h1>{{ $company }}</h1><small>Salary slip for {{ $payslip->run->label() }}</small></div>
            <div style="text-align:right"><div class="tag">{{ $payslip->run->status === 'paid' ? 'Paid' : ucfirst($payslip->run->status) }}</div>
                <div style="margin-top:10px;font-weight:600">{{ $payslip->number }}</div></div>
        </div>
        <div class="body">
            <div class="grid">
                <div><span>Employee</span><b>{{ $e->name }}</b></div>
                <div><span>Employee code</span>{{ $e->code ?: '—' }}</div>
                <div><span>Designation</span>{{ $e->designation->name ?? '—' }}</div>
                <div><span>Department</span>{{ $e->department->name ?? '—' }}</div>
                <div><span>Working days</span>{{ $payslip->run->working_days }}</div>
                <div><span>Days present</span>{{ $payslip->present_days }}</div>
                <div><span>Bank</span>{{ $e->bank_name ?: '—' }} {{ $e->bank_account_no }}</div>
                <div><span>Pay date</span>{{ $payslip->run->paid_at?->format('d M Y') ?? '—' }}</div>
            </div>
            <div class="cols">
                <table><thead><tr><th>Earnings</th><th class="r">Amount</th></tr></thead><tbody>
                    <tr><td>Basic salary</td><td class="r">{{ $money($payslip->basic_salary) }}</td></tr>
                    <tr><td>Allowances</td><td class="r">{{ $money($payslip->allowances) }}</td></tr>
                    <tr><td>Bonus</td><td class="r">{{ $money($payslip->bonus) }}</td></tr>
                    <tr><th>Gross</th><th class="r">{{ $money($payslip->gross) }}</th></tr></tbody></table>
                <table><thead><tr><th>Deductions</th><th class="r">Amount</th></tr></thead><tbody>
                    <tr><td>Unpaid leave / absence ({{ $payslip->unpaid_days }} days)</td><td class="r">{{ $money($payslip->unpaid_deduction) }}</td></tr>
                    <tr><td>Other deductions</td><td class="r">{{ $money($payslip->other_deductions) }}</td></tr>
                    <tr><td>&nbsp;</td><td></td></tr>
                    <tr><th>Total</th><th class="r">{{ $money($deductions) }}</th></tr></tbody></table>
            </div>
            @if ($payslip->note)<p style="color:#64748b;margin-top:18px">Note: {{ $payslip->note }}</p>@endif
            <div class="net"><span style="font-weight:600">Net pay</span><b>{{ $money($payslip->net) }}</b></div>
        </div>
        <div class="foot"><span>Computer-generated payslip — no signature required.</span><span>{{ now()->format('d M Y') }}</span></div>
    </div>
</body>
</html>
