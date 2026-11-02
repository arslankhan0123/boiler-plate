@extends('layouts.admin.main')
@php
    $c = $side === 'customer';
    $r = $c ? 'sales.customers' : 'purchase.suppliers';
    $cur = \App\Support\Settings::get('default_currency', 'PKR');
@endphp
@section('title', $c ? 'Customers' : 'Suppliers')
@section('subtitle', $c ? 'Sales' : 'Purchase')

@section('content')
<div class="app-content">
    <x-admin.page-header :title="$c ? 'Customers' : 'Suppliers'" :subtitle="($c ? 'Total receivable: ' : 'Total payable: ').$cur.' '.number_format($totalDue, 2)">
        @if ($canEdit)<x-admin.button icon="plus-lg" :href="route($r.'.create')">New {{ $c ? 'customer' : 'supplier' }}</x-admin.button>@endif
    </x-admin.page-header>
    <x-admin.card flush>
        <x-slot:actions>
            <form method="GET" class="d-flex flex-wrap gap-2">
                <input name="q" value="{{ request('q') }}" class="form-control" style="width:230px;min-height:40px" placeholder="Name, mobile, email…">
                <select name="only" class="form-select" style="width:140px;min-height:40px" onchange="this.form.submit()"><option value="">Everyone</option><option value="due" @selected(request('only') === 'due')>With dues</option></select>
                <select name="status" class="form-select" style="width:130px;min-height:40px" onchange="this.form.submit()"><option value="">Active</option>@foreach (['inactive' => 'Inactive', 'all' => 'All', 'deleted' => 'Deleted'] as $k => $l)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $l }}</option>@endforeach</select>
            </form>
        </x-slot:actions>
        <div class="table-responsive"><table class="table app-table table-hover align-middle">
            <thead><tr><th>Name</th><th>Contact</th><th>City</th>@if ($c)<th class="text-end">Credit limit</th>@endif<th class="text-end">Opening</th><th class="text-end">{{ $c ? 'Receivable' : 'Payable' }}</th><th>Status</th><th class="text-end"></th></tr></thead>
            <tbody>
            @forelse ($parties as $p)
                <tr>
                    <td><a href="{{ $p->trashed() ? '#' : route($r.'.show', $p) }}" class="fw-semibold text-decoration-none">{{ $p->company_name }}</a>
                        @if ($c && $p->is_default)<x-admin.badge tone="primary">Walk-in</x-admin.badge>@endif
                        <div class="small text-body-secondary">{{ $p->code ?? '' }}{{ $p->contact_person ? ' · '.$p->contact_person : '' }}</div></td>
                    <td class="small">{{ $p->mobile ?? $p->phone ?? '—' }}<div class="text-body-secondary">{{ $p->email }}</div></td>
                    <td class="small">{{ $p->city ?: '—' }}</td>
                    @if ($c)<td class="text-end small">{{ $p->credit_limit > 0 ? number_format($p->credit_limit, 2) : '—' }}</td>@endif
                    <td class="text-end small">{{ number_format($p->opening_balance, 2) }}</td>
                    <td class="text-end fw-bold {{ ($dues[$p->id] ?? 0) > 0 ? 'text-danger' : '' }}">{{ number_format($dues[$p->id] ?? 0, 2) }}</td>
                    <td>@if ($p->trashed())<x-admin.badge tone="danger">Deleted</x-admin.badge>@else<x-admin.badge :tone="$p->is_active ? 'success' : 'muted'">{{ $p->is_active ? 'Active' : 'Inactive' }}</x-admin.badge>@endif</td>
                    <td class="text-end text-nowrap">
                        @if ($p->trashed())
                            @if ($canEdit)<form method="POST" action="{{ route($r.'.restore', $p->id) }}" class="d-inline">@csrf @method('PATCH')<button class="btn btn-sm btn-light">Restore</button></form>@endif
                        @else
                            <a href="{{ route($r.'.show', $p) }}" class="btn btn-action"><i class="bi bi-eye"></i></a>
                            <a href="{{ route($r.'.statement', $p) }}" class="btn btn-action" title="Statement"><i class="bi bi-journal-text"></i></a>
                            @if ($canEdit)<a href="{{ route($r.'.edit', $p) }}" class="btn btn-action"><i class="bi bi-pencil-square"></i></a>
                                @unless ($c && $p->is_default)<x-admin.delete-button :action="route($r.'.destroy', $p)" :confirm="'Delete '.$p->company_name.'?'" />@endunless @endif
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8"><x-admin.empty :icon="$c ? 'people' : 'truck'" text="Nobody here yet." /></td></tr>
            @endforelse
            </tbody></table></div>
        @if ($parties->hasPages())
            <x-slot:footer>{{ $parties->links('pagination::bootstrap-5') }}</x-slot:footer>
        @endif
    </x-admin.card>
</div>
@endsection
