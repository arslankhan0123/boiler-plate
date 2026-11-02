@extends('layouts.admin.main')
@section('title', 'Customer History')
@section('subtitle', 'CRM')
@section('content')
<div class="app-content">
    <x-admin.page-header title="Customer History" subtitle="Pick a contact to see their full timeline" />
    <x-admin.card flush>
        <x-slot:actions>
            <form method="GET" class="d-flex gap-2"><input name="q" value="{{ request('q') }}" class="form-control" style="width:240px;min-height:40px" placeholder="Search contacts…"></form>
        </x-slot:actions>
        <div class="table-responsive"><table class="table app-table table-hover align-middle">
            <thead><tr><th>Name</th><th>Company</th><th>Email</th><th>Phone</th><th class="text-end"></th></tr></thead>
            <tbody>
            @forelse ($contacts as $ct)
                <tr>
                    <td><a href="{{ route('crm.history.show', $ct) }}" class="fw-semibold text-decoration-none">{{ $ct->name }}</a></td>
                    <td class="small">{{ $ct->company->name ?? '—' }}</td>
                    <td class="small">{{ $ct->email ?: '—' }}</td>
                    <td class="small">{{ $ct->phone ?: '—' }}</td>
                    <td class="text-end"><a href="{{ route('crm.history.show', $ct) }}" class="btn btn-action"><i class="bi bi-clock-history"></i></a></td>
                </tr>
            @empty
                <tr><td colspan="5"><x-admin.empty icon="clock-history" text="No contacts to show." /></td></tr>
            @endforelse
            </tbody></table></div>
        <x-slot:footer>{{ $contacts->links('pagination::bootstrap-5') }}</x-slot:footer>
    </x-admin.card>
</div>
@endsection
