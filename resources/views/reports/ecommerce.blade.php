@extends('layouts.admin.main')
@section('title', 'E-commerce Report')
@section('subtitle', 'Reports & Analytics')
@section('content')
<div class="app-content">
    <x-admin.page-header title="E-commerce" subtitle="Online store orders and revenue" />

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3"><x-admin.stat label="Orders" :value="number_format($orderCount)" icon="cart-check" tone="primary" /></div>
        <div class="col-6 col-xl-3"><x-admin.stat label="Revenue" :value="$cur.' '.number_format($revenue, 0)" icon="cash-stack" tone="success" /></div>
        <div class="col-6 col-xl-3"><x-admin.stat label="Avg order" :value="$cur.' '.number_format($avgOrder, 0)" icon="graph-up" tone="info" /></div>
        <div class="col-6 col-xl-3"><x-admin.stat label="Published products" :value="number_format($published)" icon="bag-check" tone="primary" /></div>
        <div class="col-6 col-xl-3"><x-admin.stat label="Pending" :value="number_format($pending)" icon="hourglass-split" tone="warning" /></div>
        <div class="col-6 col-xl-3"><x-admin.stat label="Paid" :value="number_format($paid)" icon="check2-circle" tone="success" /></div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7"><x-admin.card title="Revenue — last 6 months">
            <canvas id="ecChart" height="150"></canvas>
        </x-admin.card></div>
        <div class="col-lg-5"><x-admin.card title="Orders by status">
            <table class="table app-table align-middle mb-0"><tbody>
                @forelse ($byStatus as $s)
                    <tr><td>{{ \App\Models\Ecommerce\Order::STATUSES[$s->status] ?? $s->status }}</td><td class="text-center">{{ $s->n }}</td><td class="text-end">{{ $cur }} {{ number_format($s->total, 0) }}</td></tr>
                @empty<tr><td class="text-body-secondary small">No orders yet.</td></tr>@endforelse
            </tbody></table>
        </x-admin.card></div>
        <div class="col-12"><x-admin.card title="Top products">
            <table class="table app-table align-middle mb-0"><thead><tr><th>Product</th><th class="text-center">Sold</th><th class="text-end">Revenue</th></tr></thead><tbody>
                @forelse ($topProducts as $p)
                    <tr><td>{{ $p->name }}</td><td class="text-center">{{ (int) $p->qty }}</td><td class="text-end">{{ $cur }} {{ number_format($p->total, 0) }}</td></tr>
                @empty<tr><td colspan="3" class="text-body-secondary small">No sales yet.</td></tr>@endforelse
            </tbody></table>
        </x-admin.card></div>
    </div>
</div>
@endsection
@push('scripts')
<script>
(function(){
  const el=document.getElementById('ecChart'); if(!el||!window.Chart) return;
  new Chart(el,{type:'line',data:{labels:@json(array_column($trend,'label')),datasets:[
    {label:'Revenue',data:@json(array_column($trend,'sales')),borderColor:'#22d3ee',backgroundColor:'rgba(34,211,238,.15)',fill:true,tension:.35,pointRadius:4}
  ]},options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true}}}});
})();
</script>
@endpush
