@extends('layouts.admin.main')
@php $isExp = $type === 'expense'; @endphp
@section('title', $isExp ? 'Record expense' : 'Receive money')
@section('subtitle', 'Accounting')
@section('content')
<div class="app-content">
    <x-admin.page-header :title="$isExp ? 'Record expense' : 'Receive money'" :back="route('accounting.vouchers.index')" />
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('accounting.vouchers.store') }}">@csrf
        <input type="hidden" name="type" value="{{ $type }}">
        <div class="row g-4">
            <div class="col-lg-7"><x-admin.card :title="$isExp ? 'Expense details' : 'Receipt details'">
                <div class="row g-3">
                    <div class="col-md-6"><x-admin.input name="amount" label="Amount" type="number" step="any" min="0" required autofocus /></div>
                    <div class="col-md-6"><x-admin.input name="date" label="Date" type="date" :value="now()->format('Y-m-d')" required /></div>
                    <div class="col-md-6"><x-admin.select name="category_id" :label="$isExp ? 'Expense account' : 'Income account'" :options="$categories" required placeholder="— Select —" /></div>
                    <div class="col-md-6"><x-admin.select name="paid_through_id" :label="$isExp ? 'Paid from (bank/cash)' : 'Received into (bank/cash)'" :options="$banks" required placeholder="— Select —" /></div>
                    <div class="col-12"><x-admin.input name="memo" label="Memo / description" optional /></div>
                </div>
                <div class="d-flex gap-2 mt-3">
                    <x-admin.button :icon="$isExp ? 'arrow-up-circle' : 'arrow-down-circle'">{{ $isExp ? 'Post expense' : 'Post receipt' }}</x-admin.button>
                    <x-admin.button variant="light" :href="route('accounting.vouchers.index')" type="button">Cancel</x-admin.button>
                </div>
            </x-admin.card></div>
            <div class="col-lg-5"><x-admin.card title="How this posts">
                <p class="small text-body-secondary mb-2">A balanced journal entry is created and posted automatically:</p>
                @if ($isExp)
                    <div class="small"><span class="badge bg-danger-subtle text-danger-emphasis">Dr</span> Expense account<br><span class="badge bg-secondary-subtle">Cr</span> Bank / cash</div>
                @else
                    <div class="small"><span class="badge bg-success-subtle text-success-emphasis">Dr</span> Bank / cash<br><span class="badge bg-secondary-subtle">Cr</span> Income account</div>
                @endif
                <p class="small text-body-secondary mt-3 mb-0">It will appear in the General Ledger, statements and bank register straight away.</p>
            </x-admin.card></div>
        </div>
    </form>
</div>
@endsection
