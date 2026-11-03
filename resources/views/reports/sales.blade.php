@extends('layouts.admin.main')
@section('title', 'Sales & Purchase Report')
@section('subtitle', 'Reports & Analytics')
@section('content')
<div class="app-content">
    <x-admin.page-header title="Sales & Purchase" subtitle="Revenue, cost and profit across your sales and purchases" />

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3"><x-admin.stat label="Sales revenue" :value="$cur.' '.number_format($salesRevenue, 0)" icon="cash-stack" tone="success" /></div>
        <div class="col-6 col-xl-3"><x-admin.stat label="Purchases" :value="$cur.' '.number_format($purchaseCost, 0)" icon="cart-plus" tone="info" /></div>
        <div class="col-6 col-xl-3"><x-admin.stat label="Returns" :value="$cur.' '.number_format($returns, 0)" icon="arrow-return-left" tone="warning" /></div>
        <div class="col-6 col-xl-3"><x-admin.stat label="Gross profit" :value="$cur.' '.number_format($grossProfit, 0)" icon="graph-up-arrow" tone="primary" /></div>
        <div class="col-6 col-xl-3"><x-admin.stat label="Invoices" :value="number_format($salesCount)" icon="receipt" tone="primary" /></div>
        <div class="col-6 col-xl-3"><x-admin.stat label="Avg invoice" :value="$cur.' '.number_format($avgSale, 0)" icon="calculator" tone="info" /></div>
        <div class="col-6 col-xl-3"><x-admin.stat label="Purchase orders" :value="number_format($purchaseCount)" icon="box-seam" tone="muted" /></div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7"><x-admin.card title="Sales vs purchases — last 6 months">
            <canvas id="spChart" height="150"></canvas>
        </x-admin.card></div>
        <div class="col-lg-5"><x-admin.card title="Top customers">
            <table class="table app-table align-middle mb-0"><thead><tr><th>Customer</th><th class="text-center">Invoices</th><th class="text-end">Total</th></tr></thead><tbody>
                @forelse ($topCustomers as $c)
                    <tr><td>{{ $c->customer->company_name ?? 'Walk-in' }}</td><td class="text-center">{{ $c->n }}</td><td class="text-end">{{ $cur }} {{ number_format($c->total, 0) }}</td></tr>
                @empty<tr><td colspan="3" class="text-body-secondary small">No sales yet.</td></tr>@endforelse
            </tbody></table>
        </x-admin.card></div>
    </div>
</div>
@endsection
@push('scripts')
<script>
(function(){
  const el=document.getElementById('spChart'); if(!el||!window.Chart) return;
  new Chart(el,{type:'bar',data:{labels:@json(array_column($trend,'label')),datasets:[
    {label:'Sales',data:@json(array_column($trend,'sales')),backgroundColor:'#6366f1',borderRadius:6},
    {label:'Purchases',data:@json(array_column($trend,'purchases')),backgroundColor:'#22d3ee',borderRadius:6}
  ]},options:{responsive:true,plugins:{legend:{position:'bottom'}},scales:{y:{beginAtZero:true}}}});
})();
</script>
@endpush
