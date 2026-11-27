@extends('layouts.admin.main')
@section('title', $contact->exists ? 'Edit contact' : 'New contact')
@section('subtitle', 'CRM')
@section('content')
<div class="app-content">
    <x-admin.page-header :title="$contact->exists ? 'Edit contact' : 'New contact'" :back="route('crm.contacts.index')" />
    <form method="POST" action="{{ $contact->exists ? route('crm.contacts.update', $contact) : route('crm.contacts.store') }}">
        @csrf @if ($contact->exists)@method('PUT')@endif
        <div class="row g-4">
            <div class="col-lg-8"><x-admin.card title="Contact details">
                <div class="row g-3">
                    <div class="col-md-6"><x-admin.input name="name" label="Full name" :value="$contact->name" required /></div>
                    <div class="col-md-6"><x-admin.input name="title" label="Job title" :value="$contact->title" optional /></div>
                    <div class="col-md-6"><x-admin.input name="email" label="Email" type="email" :value="$contact->email" optional /></div>
                    <div class="col-md-6"><x-admin.input name="phone" label="Phone" :value="$contact->phone" optional /></div>
                    <div class="col-12"><label class="form-label">Notes <span class="opt">(optional)</span></label><textarea name="notes" rows="3" class="form-control">{{ old('notes', $contact->notes) }}</textarea></div>
                </div>
            </x-admin.card></div>
            <div class="col-lg-4"><x-admin.card title="Links">
                <x-admin.select name="company_id" label="Company" :options="$companies" :value="$contact->company_id" placeholder="— None —" />
                <x-admin.select name="owner_id" label="Owner" :options="$owners" :value="$contact->owner_id" placeholder="Unassigned" />
                <div class="d-grid gap-2 mt-3">
                    <x-admin.button icon="check-lg">{{ $contact->exists ? 'Save' : 'Create' }}</x-admin.button>
                    <x-admin.button variant="light" :href="route('crm.contacts.index')" type="button">Cancel</x-admin.button>
                </div>
            </x-admin.card></div>
        </div>
    </form>
</div>
@endsection
