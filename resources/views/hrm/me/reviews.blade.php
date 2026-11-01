@extends('layouts.admin.main')
@section('title', 'My Reviews')
@section('subtitle', 'My HR')

@section('content')
<div class="app-content">
    <x-admin.page-header title="My Reviews" subtitle="Performance reviews shared with you." />
    <div class="d-grid gap-4">
        @forelse ($reviews as $r)
            <div class="app-card">
                <div class="app-card__header">
                    <div><h2 class="app-card__title">{{ $r->period }}</h2><p class="app-card__subtitle">By {{ $r->reviewer_name }} · {{ $r->shared_at?->format('d M Y') }}</p></div>
                    <div class="d-flex align-items-center gap-3">
                        <span class="fs-4 fw-bold">{{ number_format($r->overall ?? 0, 1) }}<span class="fs-6 text-body-secondary">/5</span></span>
                        <x-admin.badge :tone="\App\Models\Hrm\Review::STATUSES[$r->status]">{{ $r->status === 'shared' ? 'Please acknowledge' : 'Acknowledged' }}</x-admin.badge>
                    </div>
                </div>
                <div class="app-card__body">
                    <div class="row g-4">
                        <div class="col-lg-5">@include('hrm.reviews._scores', ['review' => $r])</div>
                        <div class="col-lg-7 d-grid gap-3">
                            @foreach (['strengths' => 'Strengths', 'improvements' => 'Areas to improve', 'goals' => 'Goals'] as $f => $l)
                                @if ($r->{$f})<div><div class="small fw-semibold text-body-secondary">{{ $l }}</div><div style="white-space:pre-line">{{ $r->{$f} }}</div></div>@endif
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="app-card__footer" style="justify-content:stretch">
                    @if ($r->status === 'shared')
                        <form method="POST" action="{{ route('hrm.me.reviews.acknowledge', $r) }}" class="d-flex gap-2 w-100">@csrf
                            <input name="employee_comment" class="form-control" style="min-height:42px" placeholder="Your comment (optional)">
                            <x-admin.button icon="check2">Acknowledge</x-admin.button>
                        </form>
                    @else
                        <div class="small text-body-secondary w-100">Acknowledged {{ $r->acknowledged_at?->format('d M Y') }}@if ($r->employee_comment) — “{{ $r->employee_comment }}”@endif</div>
                    @endif
                </div>
            </div>
        @empty
            <x-admin.card><x-admin.empty icon="graph-up-arrow" text="No reviews have been shared with you yet." /></x-admin.card>
        @endforelse
    </div>
</div>
@endsection
