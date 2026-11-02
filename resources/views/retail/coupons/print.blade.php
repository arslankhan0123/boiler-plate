@php $S = \App\Support\Settings::class; @endphp
<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><title>Coupons</title>
<style>
    body { font-family: Arial, sans-serif; margin: 0; padding: 16px; } .grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
    .cp { border: 2px dashed #4f46e5; border-radius: 12px; padding: 14px; text-align: center; page-break-inside: avoid; } .cp h3 { margin: 0; color: #4f46e5; }
    .val { font-size: 28px; font-weight: 800; margin: 6px 0; } .code { font-family: monospace; font-size: 18px; letter-spacing: .1em; background: #eef2ff; display: inline-block; padding: 4px 10px; border-radius: 6px; }
    .muted { color: #64748b; font-size: 12px; } button { margin-bottom: 12px; padding: 8px 16px; border: 0; border-radius: 8px; background: #4f46e5; color: #fff; } @media print { button { display: none; } }
</style></head>
<body><button onclick="window.print()">Print</button>
<div class="grid">
    @forelse ($rows as $r)
        <div class="cp"><h3>{{ $S::companyName() }}</h3><div class="muted">{{ $r->coupon->name ?? '' }}</div>
            <div class="val">{{ $r->coupon?->label() }} OFF</div><div class="code">{{ $r->code }}</div>
            <div class="muted" style="margin-top:6px">For {{ $r->customer->company_name ?? '' }} · {{ $r->expire_date ? 'Valid till '.$r->expire_date->format('d M Y') : 'No expiry' }}@if (($r->coupon->min_amount ?? 0) > 0) · Min bill {{ number_format($r->coupon->min_amount, 2) }}@endif · One-time use</div></div>
    @empty
        <p>Select coupons to print.</p>
    @endforelse
</div></body></html>
