{{-- @include('hrm.partials.person', ['e' => $employee, 'link' => true, 'sub' => '...']) --}}
@php $sub = $sub ?? trim(($e->designation->name ?? '').($e->department ? ' · '.$e->department->name : ''), ' ·'); @endphp
<div class="d-flex align-items-center gap-3">
    <span class="app-avatar">{{ \App\Support\Hrm\Hrm::initials($e->name) }}</span>
    <div class="min-w-0">
        @if (($link ?? false) && auth()->user()->can('hrm.view_employees'))
            <a href="{{ route('hrm.employees.show', $e) }}" class="fw-semibold text-body text-decoration-none">{{ $e->name }}</a>
        @else
            <div class="fw-semibold">{{ $e->name }}</div>
        @endif
        @if ($sub)<div class="small text-body-secondary text-truncate">{{ $sub }}</div>@endif
    </div>
</div>
