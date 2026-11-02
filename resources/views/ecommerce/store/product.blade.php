@extends('ecommerce.store.layout')
@section('title', $product->name)
@section('content')
    <a href="{{ route('store.index', $tenant->id) }}" class="text-decoration-none small"><i class="bi bi-chevron-left"></i> Back to store</a>
    <div class="row g-4 mt-1">
        <div class="col-md-5">
            <div class="p-card"><div class="p-thumb" @if ($product->image_url) style="background-image:url('{{ $product->image_url }}')" @endif>@unless ($product->image_url)<i class="bi bi-image"></i>@endunless</div></div>
        </div>
        <div class="col-md-7">
            <h1 class="fw-bold">{{ $product->name }}</h1>
            @if ($avg > 0)<div class="text-warning mb-2">{!! str_repeat('★', (int) round($avg)) !!} <span class="text-body-secondary small">{{ $avg }} ({{ $reviews->count() }})</span></div>@endif
            <div class="d-flex align-items-center gap-2 mb-3">
                <span class="h4 mb-0 p-price">{{ $currency }} {{ number_format($product->price, 2) }}</span>
                @if ($product->onSale())<span class="p-was">{{ number_format($product->compare_at_price, 2) }}</span><span class="badge-sale">SALE</span>@endif
            </div>
            <p class="text-body-secondary" style="white-space:pre-line">{{ $product->description }}</p>
            @if ($inStock)
                <form method="POST" action="{{ route('store.cart.add', $tenant->id) }}" class="d-flex gap-2 mt-3" style="max-width:320px">@csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <input type="number" name="qty" value="1" min="1" class="form-control" style="width:90px">
                    <button class="btn btn-dark flex-grow-1"><i class="bi bi-bag-plus me-1"></i>Add to cart</button>
                </form>
            @else
                <span class="badge bg-secondary">Out of stock</span>
            @endif
        </div>
    </div>

    <div class="row g-4 mt-2">
        <div class="col-md-7">
            <h4 class="fw-bold mt-3">Reviews</h4>
            @forelse ($reviews as $r)
                <div class="s-card p-3 mb-2">
                    <div class="text-warning">{!! str_repeat('★', $r->rating).str_repeat('☆', 5 - $r->rating) !!}</div>
                    <strong>{{ $r->title }}</strong>
                    <p class="mb-1 small">{{ $r->body }}</p>
                    <span class="small text-body-secondary">— {{ $r->customer_name }}</span>
                </div>
            @empty
                <p class="text-body-secondary small">No reviews yet. Be the first!</p>
            @endforelse
        </div>
        <div class="col-md-5">
            <div class="s-card p-3 mt-3">
                <h5 class="fw-bold">Write a review</h5>
                <form method="POST" action="{{ route('store.review', [$tenant->id, $product->id]) }}">@csrf
                    <input name="customer_name" class="form-control mb-2" placeholder="Your name" required>
                    <select name="rating" class="form-select mb-2">@for ($i = 5; $i >= 1; $i--)<option value="{{ $i }}">{{ str_repeat('★', $i) }}</option>@endfor</select>
                    <input name="title" class="form-control mb-2" placeholder="Title">
                    <textarea name="body" rows="3" class="form-control mb-2" placeholder="Your review"></textarea>
                    <button class="btn btn-dark w-100">Submit review</button>
                </form>
            </div>
        </div>
    </div>
@endsection
