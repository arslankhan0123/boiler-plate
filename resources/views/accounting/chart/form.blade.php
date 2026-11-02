@extends('layouts.admin.main')
@section('title', $account->exists ? 'Edit account' : 'New account')
@section('subtitle', 'Accounting')
@section('content')
<div class="app-content">
    <x-admin.page-header :title="$account->exists ? 'Edit account' : 'New account'" :back="route('accounting.chart.index')" />
    <form method="POST" action="{{ $account->exists ? route('accounting.chart.update', $account) : route('accounting.chart.store') }}">
        @csrf @if ($account->exists)@method('PUT')@endif
        <div class="row g-4">
            <div class="col-lg-8"><x-admin.card title="Account">
                <div class="row g-3">
                    <div class="col-md-3"><x-admin.input name="code" label="Code" :value="$account->code" required /></div>
                    <div class="col-md-6"><x-admin.input name="name" label="Name" :value="$account->name" required /></div>
                    <div class="col-md-3"><x-admin.select name="type" label="Type" :options="\App\Models\Accounting\Account::TYPES" :value="$account->type" required /></div>
                    <div class="col-md-6"><x-admin.select name="parent_id" label="Parent account" :options="$parents" :value="$account->parent_id" placeholder="— None —" /></div>
                    <div class="col-md-3"><x-admin.input name="opening_balance" label="Opening balance" type="number" step="any" :value="$account->opening_balance" /></div>
                    <div class="col-md-3"><x-admin.select name="control_type" label="Control" :options="['receivable' => 'Receivable', 'payable' => 'Payable']" :value="$account->control_type" placeholder="— None —" /></div>
                    <div class="col-12"><x-admin.input name="description" label="Description" :value="$account->description" optional /></div>
                    <div class="col-12 d-flex gap-4">
                        <label class="form-check"><input type="checkbox" name="is_bank" value="1" class="form-check-input" @checked($account->is_bank)> <span class="form-check-label">Bank / cash account</span></label>
                        <label class="form-check"><input type="checkbox" name="is_group" value="1" class="form-check-input" @checked($account->is_group)> <span class="form-check-label">Group / header (no postings)</span></label>
                        <label class="form-check"><input type="checkbox" name="is_active" value="1" class="form-check-input" @checked($account->is_active ?? true)> <span class="form-check-label">Active</span></label>
                    </div>
                </div>
                <div class="d-flex gap-2 mt-3">
                    <x-admin.button icon="check-lg">{{ $account->exists ? 'Save' : 'Create' }}</x-admin.button>
                    <x-admin.button variant="light" :href="route('accounting.chart.index')" type="button">Cancel</x-admin.button>
                </div>
            </x-admin.card></div>
        </div>
    </form>
</div>
@endsection
