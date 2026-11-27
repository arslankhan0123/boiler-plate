@extends('layouts.admin.main')
@section('title', 'Holidays')
@section('subtitle', 'HRM · Leave')

@section('content')
<div class="app-content">
    <x-admin.page-header title="Holidays" subtitle="Public holidays aren't counted as leave or working days.">
        <form method="GET"><select name="year" class="form-select" style="width:120px" onchange="this.form.submit()">
            @foreach (range(now()->year + 1, now()->year - 3) as $y)<option @selected($y === $year)>{{ $y }}</option>@endforeach
        </select></form>
    </x-admin.page-header>

    <div class="app-form-grid">
        <x-admin.card flush :title="$year.' holidays ('.$holidays->count().')'">
            <div class="table-responsive"><table class="table app-table">
                <thead><tr><th>Date</th><th>Holiday</th><th class="text-end"></th></tr></thead>
                <tbody>
                @forelse ($holidays as $h)
                    <tr><td><div class="fw-semibold">{{ $h->date->format('d M Y') }}</div><div class="small text-body-secondary">{{ $h->date->format('l') }}</div></td>
                        <td>{{ $h->name }}</td>
                        <td class="text-end"><x-admin.delete-button :action="route('hrm.holidays.destroy', $h)" confirm="Remove this holiday?" /></td></tr>
                @empty
                    <tr><td colspan="3"><x-admin.empty icon="calendar-heart" text="No holidays added for this year." /></td></tr>
                @endforelse
                </tbody></table></div>
        </x-admin.card>
        <div class="app-aside">
            <div class="app-card"><div class="app-card__body">
                <h3 class="app-aside-title">Add holiday</h3>
                <form method="POST" action="{{ route('hrm.holidays.store') }}">@csrf
                    <x-admin.input name="name" label="Name" placeholder="e.g. Independence Day" required />
                    <x-admin.input name="date" type="date" label="Date" required />
                    <x-admin.button class="mt-4" icon="plus-lg">Add Holiday</x-admin.button>
                </form>
            </div></div>
        </div>
    </div>
</div>
@endsection
