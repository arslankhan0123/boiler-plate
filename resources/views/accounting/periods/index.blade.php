@extends('layouts.admin.main')
@section('title', 'Accounting Periods')
@section('subtitle', 'Accounting')
@section('content')
<div class="app-content">
    <x-admin.page-header title="Accounting Periods" subtitle="Close a period to lock its postings" />
    <div class="row g-4">
        <div class="col-lg-8"><x-admin.card flush>
            <div class="table-responsive"><table class="table app-table align-middle">
                <thead><tr><th>Name</th><th>From</th><th>To</th><th>Status</th><th class="text-end"></th></tr></thead>
                <tbody>
                @forelse ($periods as $p)
                    <tr>
                        <td class="fw-semibold">{{ $p->name }}</td>
                        <td class="small">{{ $p->start_date->format('d M Y') }}</td>
                        <td class="small">{{ $p->end_date->format('d M Y') }}</td>
                        <td><x-admin.badge :tone="$p->isClosed() ? 'danger' : 'success'">{{ $p->isClosed() ? 'Closed' : 'Open' }}</x-admin.badge></td>
                        <td class="text-end text-nowrap">
                            @if ($p->isClosed())
                                <form method="POST" action="{{ route('accounting.periods.reopen', $p) }}" class="d-inline">@csrf<button class="btn btn-sm btn-light">Reopen</button></form>
                            @else
                                <form method="POST" action="{{ route('accounting.periods.close', $p) }}" class="d-inline" onsubmit="return confirm('Close this period? Postings in this range will be locked.')">@csrf<button class="btn btn-sm btn-outline-danger">Close</button></form>
                            @endif
                            <x-admin.delete-button :action="route('accounting.periods.destroy', $p)" confirm="Delete this period?" />
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-admin.empty icon="calendar-range" text="No periods yet." /></td></tr>
                @endforelse
                </tbody>
            </table></div>
        </x-admin.card></div>
        <div class="col-lg-4"><x-admin.card title="New period">
            <form method="POST" action="{{ route('accounting.periods.store') }}">@csrf
                <x-admin.input name="name" label="Name" required />
                <x-admin.input name="start_date" label="Start" type="date" required />
                <x-admin.input name="end_date" label="End" type="date" required />
                <div class="d-grid mt-2"><x-admin.button icon="plus-lg">Add period</x-admin.button></div>
            </form>
        </x-admin.card></div>
    </div>
</div>
@endsection
