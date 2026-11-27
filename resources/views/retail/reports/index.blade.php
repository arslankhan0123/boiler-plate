@extends('layouts.admin.main')
@section('title', 'Reports')
@section('subtitle', 'Sales · Purchase · Inventory')

@section('content')
<div class="app-content">
    <x-admin.page-header title="Reports" subtitle="Every report can be filtered, printed and exported to CSV / Excel." />
    @foreach ($groups as $group => $reports)
        <h3 class="app-aside-title mt-2 mb-3">{{ $group }}</h3>
        <div class="row g-3 mb-4">
            @foreach ($reports as $key => [$title, , , $desc])
                <div class="col-md-6 col-xl-4"><a href="{{ route('retail.reports.show', $key) }}" class="app-card d-block text-decoration-none h-100 report-tile"><div class="app-card__body">
                    <div class="fw-bold text-body">{{ $title }}</div><div class="small text-body-secondary">{{ $desc }}</div></div></a></div>
            @endforeach
        </div>
    @endforeach
    @if ($groups->isEmpty())<x-admin.empty icon="bar-chart" text="You don't have access to any reports." />@endif
</div>
@endsection
@push('styles')<style>.report-tile:hover { border-color: #c7d2fe; transform: translateY(-1px); transition: .15s; }</style>@endpush
