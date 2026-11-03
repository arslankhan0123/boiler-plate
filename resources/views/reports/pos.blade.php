@extends('layouts.admin.main')
@section('title', 'POS Report')
@section('subtitle', 'Reports & Analytics')
@section('content')
<div class="app-content">
    <x-admin.page-header title="POS" subtitle="Point-of-sale performance" />

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3"><x-admin.stat label="Today's sales" :value="$cur.' '.number_format($todaySales, 0)" icon="cash-stack" tone="success" /></div>
        <div class="col-6 col-xl-3"><x-admin.stat label="Today's tickets" :value="number_format($todayCount)" icon="receipt" tone="primary" /></div>
        <div class="col-6 col-xl-3"><x-admin.stat label="Total POS sales" :value="$cur.' '.number_format($totalSales, 0)" icon="graph-up" tone="info" /></div>
        <div class="col-6 col-xl-3"><x-admin.stat label="Avg ticket" :value="$cur.' '.number_format($avgTicket, 0)" icon="calculator" tone="muted" /></div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7"><x-admin.card title="Sales — last 7 days">
            <canvas id="posChart" height="150"></canvas>
        </x-admin.card></div>
        <div class="col-lg-5"><x-admin.card title="By cashier">
            <table class="table app-table align-middle mb-0"><thead><tr><th>Cashier</th><th class="text-center">Tickets</th><th class="text-end">Total</th></tr></thead><tbody>
                @forelse ($byCashier as $c)
                    <tr><td>{{ $cashierNames[$c->created_by] ?? 'User #'.$c->created_by }}</td><td class="text-center">{{ $c->n }}</td><td class="text-end">{{ $cur }} {{ number_format($c->total, 0) }}</td></tr>
                @empty<tr><td colspan="3" class="text-body-secondary small">No POS sales yet.</td></tr>@endforelse
            </tbody></table>
        </x-admin.card></div>
    </div>
</div>
@endsection
@push('scripts')
<script>
(function(){
  const el=document.getElementById('posChart'); if(!el||!window.Chart) return;
  new Chart(el,{type:'line',data:{labels:@json(array_column($trend,'label')),datasets:[
    {label:'Sales',data:@json(array_column($trend,'sales')),borderColor:'#6366f1',backgroundColor:'rgba(99,102,241,.15)',fill:true,tension:.35,pointRadius:4}
  ]},options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true}}}});
})();
</script>
@endpush
