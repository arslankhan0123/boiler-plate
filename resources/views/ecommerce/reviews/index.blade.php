@extends('layouts.admin.main')
@section('title', 'Reviews')
@section('subtitle', 'E-commerce')
@section('content')
<div class="app-content">
    <x-admin.page-header title="Reviews" :subtitle="$pendingCount.' pending approval'" />
    <x-admin.card flush>
        <x-slot:actions>
            <div class="btn-group btn-group-sm">
                @foreach (['' => 'All', 'pending' => 'Pending', 'approved' => 'Approved'] as $k => $l)
                    <a href="{{ route('ecommerce.reviews.index', ['status' => $k]) }}" class="btn {{ request('status') === $k ? 'btn-primary' : 'btn-light' }}">{{ $l }}</a>
                @endforeach
            </div>
        </x-slot:actions>
        <div class="table-responsive"><table class="table app-table align-middle">
            <thead><tr><th>Product</th><th>Rating</th><th>Review</th><th>By</th><th>Status</th><th class="text-end"></th></tr></thead>
            <tbody>
            @forelse ($reviews as $r)
                <tr>
                    <td class="small fw-semibold">{{ $r->product->name ?? '—' }}</td>
                    <td class="text-warning">{!! str_repeat('★', $r->rating).str_repeat('☆', 5 - $r->rating) !!}</td>
                    <td class="small"><strong>{{ $r->title }}</strong><div class="text-body-secondary">{{ Str::limit($r->body, 80) }}</div></td>
                    <td class="small">{{ $r->customer_name }}</td>
                    <td><x-admin.badge :tone="$r->is_approved ? 'success' : 'warning'">{{ $r->is_approved ? 'Approved' : 'Pending' }}</x-admin.badge></td>
                    <td class="text-end text-nowrap">
                        <form method="POST" action="{{ route('ecommerce.reviews.approve', $r) }}" class="d-inline">@csrf @method('PATCH')<button class="btn btn-action" title="{{ $r->is_approved ? 'Hide' : 'Approve' }}"><i class="bi {{ $r->is_approved ? 'bi-eye-slash' : 'bi-check-lg' }}"></i></button></form>
                        <x-admin.delete-button :action="route('ecommerce.reviews.destroy', $r)" confirm="Delete this review?" />
                    </td>
                </tr>
            @empty
                <tr><td colspan="6"><x-admin.empty icon="star" text="No reviews yet." /></td></tr>
            @endforelse
            </tbody></table></div>
        <x-slot:footer>{{ $reviews->links('pagination::bootstrap-5') }}</x-slot:footer>
    </x-admin.card>
</div>
@endsection
