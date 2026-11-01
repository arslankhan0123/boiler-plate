@extends('layouts.admin.main')
@section('title', 'Review — '.$review->employee->name)
@section('subtitle', 'HRM · Performance')

@section('content')
<div class="app-content">
    <x-admin.page-header :back="route('hrm.reviews.index')" :title="$review->employee->name.' — '.$review->period" :subtitle="'Reviewed by '.($review->reviewer_name ?? '—')">
        @if ($canEdit)
            <form method="POST" action="{{ route('hrm.reviews.destroy', $review) }}" onsubmit="return confirm('Delete this draft?')">@csrf @method('DELETE')
                <x-admin.button variant="light" icon="trash3" class="text-danger">Delete</x-admin.button></form>
            <x-admin.button :href="route('hrm.reviews.edit', $review)" icon="pencil">Edit</x-admin.button>
        @endif
    </x-admin.page-header>

    <div class="app-form-grid">
        <div class="d-grid gap-4">
            <x-admin.card title="Scores">@include('hrm.reviews._scores', ['review' => $review])</x-admin.card>
            @foreach (['strengths' => 'Strengths', 'improvements' => 'Areas to improve', 'goals' => 'Goals for next period'] as $f => $l)
                @if ($review->{$f})<x-admin.card :title="$l"><div style="white-space:pre-line">{{ $review->{$f} }}</div></x-admin.card>@endif
            @endforeach
            @if ($review->employee_comment)
                <x-admin.card title="Employee's comment"><div style="white-space:pre-line">{{ $review->employee_comment }}</div></x-admin.card>
            @endif
        </div>
        <div class="app-aside">
            <x-admin.card title="Summary">
                <div class="display-6 fw-bold">{{ number_format($review->overall ?? 0, 1) }}<span class="fs-6 text-body-secondary"> / 5</span></div>
                <div class="text-warning mb-3">{!! str_repeat('<i class="bi bi-star-fill"></i>', (int) round($review->overall ?? 0)) !!}</div>
                <x-admin.badge :tone="\App\Models\Hrm\Review::STATUSES[$review->status]">{{ ucfirst($review->status) }}</x-admin.badge>
                @if ($review->shared_at)<div class="small text-body-secondary mt-2">Shared {{ $review->shared_at->format('d M Y') }}</div>@endif
                @if ($review->acknowledged_at)<div class="small text-body-secondary">Acknowledged {{ $review->acknowledged_at->format('d M Y') }}</div>@endif
            </x-admin.card>
        </div>
    </div>
</div>
@endsection
