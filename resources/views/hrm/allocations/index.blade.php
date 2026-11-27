@extends('layouts.admin.main')
@section('title', 'Yearly Leave Allocation')
@section('subtitle', 'HRM · Leave')

@section('content')
<div class="app-content">
    <x-admin.page-header :title="'Leave Allocation '.$year" subtitle="Days each employee gets per leave type this year. Edit any cell and save the row.">
        <form method="GET" class="d-flex gap-2">
            <select name="year" class="form-select" style="width:110px" onchange="this.form.submit()">
                @foreach (range(now()->year + 1, now()->year - 3) as $y)<option @selected($y === $year)>{{ $y }}</option>@endforeach
            </select>
        </form>
        <form method="POST" action="{{ route('hrm.allocations.allocate') }}" onsubmit="return confirm('Allocate {{ $year }} leave to all active employees? Existing balances for {{ $year }} will be reset to the leave type defaults (plus carry-forward).')">
            @csrf <input type="hidden" name="year" value="{{ $year }}">
            <x-admin.button icon="magic">{{ $allocated ? 'Re-allocate' : 'Allocate' }} {{ $year }}</x-admin.button>
        </form>
    </x-admin.page-header>

    @unless ($allocated)
        <div class="app-alert app-alert--danger"><i class="bi bi-info-circle-fill"></i>Leave for {{ $year }} hasn't been allocated yet. Click “Allocate {{ $year }}” — new joiners get a pro-rated share and unused days are carried forward where allowed.</div>
    @endunless

    <x-admin.card flush :title="'Employees ('.$employees->total().')'">
        <x-slot:actions>
            <form method="GET" class="input-icon" style="width:240px"><i class="bi bi-search"></i>
                <input type="hidden" name="year" value="{{ $year }}">
                <input type="search" name="search" value="{{ request('search') }}" class="form-control" style="min-height:42px" placeholder="Search employee…"></form>
        </x-slot:actions>
        <div class="table-responsive"><table class="table app-table align-middle">
            <thead><tr><th>Employee</th>@foreach ($types as $t)<th class="text-center">{{ $t->name }}</th>@endforeach<th></th></tr></thead>
            <tbody>
            @forelse ($employees as $e)
                <tr>
                    <td>@include('hrm.partials.person', ['e' => $e, 'sub' => $e->department->name ?? ''])</td>
                    @foreach ($types as $t)
                        @php $a = $allocations->get($e->id)?->get($t->id); @endphp
                        <td class="text-center">
                            <input form="alloc-{{ $e->id }}" type="number" step="0.5" min="0" name="alloc[{{ $t->id }}]" value="{{ $a?->allocated }}" class="form-control text-center mx-auto" style="width:90px;min-height:38px" placeholder="0">
                            @if ($a && $a->carried_forward > 0)<div class="small text-success">+{{ $a->carried_forward }} carried</div>@endif
                        </td>
                    @endforeach
                    <td class="text-end">
                        <form id="alloc-{{ $e->id }}" method="POST" action="{{ route('hrm.allocations.update', $e) }}">@csrf @method('PUT')
                            <input type="hidden" name="year" value="{{ $year }}">
                            <button class="btn btn-sm btn-light"><i class="bi bi-check2"></i> Save</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="{{ $types->count() + 2 }}"><x-admin.empty icon="people" text="No active employees." /></td></tr>
            @endforelse
            </tbody></table></div>
        @if ($employees->hasPages())
            <x-slot:footer>{{ $employees->links('pagination::bootstrap-5') }}</x-slot:footer>
        @endif
    </x-admin.card>
    @if ($types->isEmpty())<p class="text-body-secondary small mt-3">No paid leave types. <a href="{{ route('hrm.leave-types.index') }}">Create leave types</a> first.</p>@endif
</div>
@endsection
