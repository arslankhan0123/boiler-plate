@extends('layouts.admin.main')
@section('title', 'Companies')
@section('subtitle', 'CRM')
@section('content')
<div class="app-content">
    <x-admin.page-header title="Companies" subtitle="Accounts in your CRM">
        <x-admin.button variant="light" icon="person-lines-fill" :href="route('crm.contacts.index')">Contacts</x-admin.button>
        @if ($canEdit)<x-admin.button icon="plus-lg" :href="route('crm.companies.create')">New company</x-admin.button>@endif
    </x-admin.page-header>
    <x-admin.card flush>
        <x-slot:actions>
            <form method="GET" class="d-flex gap-2">
                <input name="q" value="{{ request('q') }}" class="form-control" style="width:240px;min-height:40px" placeholder="Name, email, city…">
            </form>
        </x-slot:actions>
        <div class="table-responsive"><table class="table app-table table-hover align-middle">
            <thead><tr><th>Name</th><th>Industry</th><th>City</th><th>Contact</th><th class="text-center">Contacts</th><th>Owner</th><th class="text-end"></th></tr></thead>
            <tbody>
            @forelse ($companies as $c)
                <tr>
                    <td><a href="{{ route('crm.companies.show', $c) }}" class="fw-semibold text-decoration-none">{{ $c->name }}</a></td>
                    <td class="small">{{ $c->industry ?: '—' }}</td>
                    <td class="small">{{ $c->city ?: '—' }}</td>
                    <td class="small">{{ $c->phone ?: $c->email ?: '—' }}</td>
                    <td class="text-center">{{ $c->contacts_count }}</td>
                    <td class="small">{{ $c->owner->name ?? '—' }}</td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('crm.companies.show', $c) }}" class="btn btn-action"><i class="bi bi-eye"></i></a>
                        @if ($canEdit)<a href="{{ route('crm.companies.edit', $c) }}" class="btn btn-action"><i class="bi bi-pencil-square"></i></a>
                        <x-admin.delete-button :action="route('crm.companies.destroy', $c)" :confirm="'Delete '.$c->name.'?'" />@endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7"><x-admin.empty icon="building" text="No companies yet." /></td></tr>
            @endforelse
            </tbody></table></div>
        <x-slot:footer>{{ $companies->links('pagination::bootstrap-5') }}</x-slot:footer>
    </x-admin.card>
</div>
@endsection
