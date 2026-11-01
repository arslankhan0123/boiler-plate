@extends('layouts.admin.main')
@php $d = fn ($v) => $v ? \Illuminate\Support\Carbon::parse($v)->format('d M Y') : '—'; $e = $employee; @endphp
@section('title', 'My Profile')
@section('subtitle', 'My HR')

@section('content')
<div class="app-content">
    <div class="app-page-header">
        <div class="app-page-header__lead">
            <span class="app-avatar" style="width:60px;height:60px;font-size:1.2rem">{{ \App\Support\Hrm\Hrm::initials($e->name) }}</span>
            <div><h1 class="app-page-header__title">{{ $e->name }}</h1>
                <p class="app-page-header__subtitle">{{ $e->designation->name ?? '—' }} · {{ $e->department->name ?? '—' }} @if ($e->code)· {{ $e->code }}@endif</p></div>
        </div>
        <div class="app-page-header__actions">
            <x-admin.button variant="light" :href="route('hrm.me.leaves')" icon="calendar-plus">Apply Leave</x-admin.button>
            <x-admin.button variant="light" :href="route('hrm.me.payslips')" icon="receipt">My Payslips</x-admin.button>
        </div>
    </div>

    <div class="mt-4">@include('hrm.me._checkin', ['today' => $today])</div>

    @php $notices = \App\Models\Hrm\Announcement::query()->visibleTo($employee)->orderByDesc('is_pinned')->latest()->limit(5)->get(); @endphp
    @if ($notices->isNotEmpty())
        <div class="mt-4">@include('hrm.announcements._feed', ['notices' => $notices])</div>
    @endif

    <div class="app-form-grid mt-4">
        <div class="d-grid gap-4">
            <x-admin.card :title="'My leave balance '.now()->year">@include('hrm.partials.balances', ['balances' => $balances])</x-admin.card>

            <x-admin.card title="My contact details" subtitle="You can keep these up to date. Ask HR to change anything else.">
                <form method="POST" action="{{ route('hrm.me.profile.update') }}">@csrf @method('PATCH')
                    <div class="row g-4">
                        <div class="col-md-6"><x-admin.input name="phone" label="Phone" :value="$e->phone" /></div>
                        <div class="col-md-6"><x-admin.input name="blood_group" label="Blood group" :value="$e->blood_group" /></div>
                        <div class="col-md-6"><x-admin.input name="street" label="Address" :value="$e->street" /></div>
                        <div class="col-md-6"><x-admin.input name="city" label="City" :value="$e->city" /></div>
                    </div>
                    <x-admin.button class="mt-4" icon="check2">Save</x-admin.button>
                </form>
            </x-admin.card>

            <x-admin.card flush title="My documents">
                <div class="table-responsive"><table class="table app-table"><tbody>
                    @forelse ($e->documents as $doc)
                        <tr><td class="fw-semibold">{{ $doc->name }}</td><td class="text-body-secondary">Expires {{ $d($doc->expiry_date) }}</td>
                            <td class="text-end">@if ($doc->file)<a href="{{ route('hrm.employees.documents.download', [$e, $doc]) }}" class="btn btn-action"><i class="bi bi-download"></i></a>@endif</td></tr>
                    @empty
                        <tr><td><x-admin.empty icon="folder" text="No documents on file." /></td></tr>
                    @endforelse
                </tbody></table></div>
            </x-admin.card>
        </div>

        <div class="app-aside">
            <x-admin.card title="Employment">
                <dl class="row small mb-0 g-2">
                    <dt class="col-5 text-body-secondary fw-normal">Joined</dt><dd class="col-7 mb-0">{{ $d($e->join_date) }}</dd>
                    <dt class="col-5 text-body-secondary fw-normal">Type</dt><dd class="col-7 mb-0">{{ config('hrm.employment_types')[$e->employment_type] ?? '—' }}</dd>
                    @php $myShift = \App\Models\Hrm\Shift::forEmployee($e); @endphp
                    <dt class="col-5 text-body-secondary fw-normal">Shift</dt><dd class="col-7 mb-0">{{ $myShift?->label() ?? config('hrm.office_start').' start' }}</dd>
                    <dt class="col-5 text-body-secondary fw-normal">Email</dt><dd class="col-7 mb-0 text-break">{{ $e->email ?: '—' }}</dd>
                    <dt class="col-5 text-body-secondary fw-normal">Bank</dt><dd class="col-7 mb-0">{{ $e->bank_name ?: '—' }}</dd>
                </dl>
            </x-admin.card>
            <x-admin.card title="My team">
                <div class="small text-body-secondary mb-2">I report to</div>
                @if ($manager)@include('hrm.partials.person', ['e' => $manager])@else<div class="small text-body-secondary">— Top level —</div>@endif
                <div class="small text-body-secondary mt-3 mb-2">Reporting to me ({{ $team->count() }})</div>
                <div class="d-grid gap-2">@forelse ($team as $t)@include('hrm.partials.person', ['e' => $t])@empty<div class="small text-body-secondary">None</div>@endforelse</div>
            </x-admin.card>
        </div>
    </div>
</div>
@endsection
