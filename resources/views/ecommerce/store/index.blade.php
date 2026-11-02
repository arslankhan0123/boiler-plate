@extends('ecommerce.store.layout')
@section('content')
    <div class="mb-4">
        <h1 class="fw-bold mb-1">{{ $storeName }}</h1>
        @if ($tagline)<p class="text-body-secondary">{{ $tagline }}</p>@endif
    </div>
    <form method="GET" class="row g-2 mb-4">
        <div class="col-md-6"><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Search products…"></div>
        <div class="col-md-4"><select name="category" class="form-select" onchange="this.form.submit()"><option value="">All categories</option>@foreach ($categories as $c)<option value="{{ $c->slug }}" @selected(request('category')===$c->slug)>{{ $c->name }}</option>@endforeach</select></div>
        <div class="col-md-2 d-grid"><button class="btn btn-dark">Search</button></div>
    </form>
    <div class="row g-3">
        @forelse ($products as $p)
            <div class="col-6 col-md-4 col-lg-3">
                <a href="{{ route('store.product', [$tenant->id, $p->slug]) }}" class="text-decoration-none text-reset">
                    <div class="p-card">
                        <div class="p-thumb" @if ($p->image_url) style="background-image:url('{{ $p->image_url }}')" @endif>@unless ($p->image_url)<i class="bi bi-image"></i>@endunless</div>
                        <div class="p-3">
                            <div class="fw-semibold text-truncate">{{ $p->name }}</div>
                            <div class="d-flex align-items-center gap-2 mt-1">
                                <span class="p-price">{{ $currency }} {{ number_format($p->price, 0) }}</span>
                                @if ($p->onSale())<span class="p-was">{{ number_format($p->compare_at_price, 0) }}</span><span class="badge-sale">SALE</span>@endif
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        @empty
            <div class="col-12 text-center text-body-secondary py-5"><i class="bi bi-bag" style="font-size:2.5rem"></i><p class="mt-2">No products available right now.</p></div>
        @endforelse
    </div>
    <div class="mt-4">{{ $products->links('pagination::bootstrap-5') }}</div>
@endsection
