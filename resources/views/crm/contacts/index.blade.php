@extends('layouts.admin.main')
@section('title', 'Contacts')
@section('subtitle', 'CRM')
@section('content')
<div class="app-content">
    <x-admin.page-header title="Contacts" subtitle="People in your CRM">
        <x-admin.button variant="light" icon="building" :href="route('crm.companies.index')">Companies</x-admin.button>
        @if ($canEdit)<x-admin.button icon="plus-lg" :href="route('crm.contacts.create')">New contact</x-admin.button>@endif
    </x-admin.page-header>
    <x-admin.card flush>
        <x-slot:actions>
            <form method="GET" class="d-flex gap-2">
                <input name="q" value="{{ request('q') }}" class="form-control" style="width:220px;min-height:40px" placeholder="Name, email, phone…">
                <select name="company_id" class="form-select" style="width:170px;min-height:40px" onchange="this.form.submit()"><option value="">All companies</option>@foreach ($companies as $id => $n)<option value="{{ $id }}" @selected((string)request('company_id')===(string)$id)>{{ $n }}</option>@endforeach</select>
            </form>
        </x-slot:actions>
        <div class="table-responsive"><table class="table app-table table-hover align-middle">
            <thead><tr><th>Name</th><th>Title</th><th>Company</th><th>Email</th><th>Phone</th><th>Owner</th><th class="text-end"></th></tr></thead>
            <tbody>
            @forelse ($contacts as $ct)
                <tr>
                    <td class="fw-semibold">{{ $ct->name }}</td>
                    <td class="small">{{ $ct->title ?: '—' }}</td>
                    <td class="small">{{ $ct->company->name ?? '—' }}</td>
                    <td class="small">{{ $ct->email ?: '—' }}</td>
                    <td class="small">{{ $ct->phone ?: '—' }}</td>
                    <td class="small">{{ $ct->owner->name ?? '—' }}</td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('crm.history.show', $ct) }}" class="btn btn-action" title="History"><i class="bi bi-clock-history"></i></a>
                        @if ($canEdit)<a href="{{ route('crm.contacts.edit', $ct) }}" class="btn btn-action"><i class="bi bi-pencil-square"></i></a>
                        <x-admin.delete-button :action="route('crm.contacts.destroy', $ct)" :confirm="'Delete '.$ct->name.'?'" />@endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7"><x-admin.empty icon="person-lines-fill" text="No contacts yet." /></td></tr>
            @endforelse
            </tbody></table></div>
        <x-slot:footer>{{ $contacts->links('pagination::bootstrap-5') }}</x-slot:footer>
    </x-admin.card>
</div>
@endsection
