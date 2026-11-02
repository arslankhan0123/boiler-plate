@extends('layouts.admin.main')
@section('title', 'Catalog')
@section('subtitle', 'E-commerce')
@section('content')
<div class="app-content">
    <x-admin.page-header title="Catalog" :subtitle="$publishedCount.' published'">
        <x-admin.button variant="light" icon="tags" :href="route('ecommerce.catalog.categories')">Categories</x-admin.button>
        @if ($canEdit)<x-admin.button icon="plus-lg" :href="route('ecommerce.catalog.create')">Add product</x-admin.button>@endif
    </x-admin.page-header>
    <x-admin.card flush>
        <x-slot:actions>
            <form method="GET" class="d-flex gap-2">
                <input name="q" value="{{ request('q') }}" class="form-control" style="width:200px;min-height:40px" placeholder="Product name…">
                <select name="category_id" class="form-select" style="width:150px;min-height:40px" onchange="this.form.submit()"><option value="">All categories</option>@foreach ($categories as $id => $n)<option value="{{ $id }}" @selected((string)request('category_id')===(string)$id)>{{ $n }}</option>@endforeach</select>
                <select name="status" class="form-select" style="width:130px;min-height:40px" onchange="this.form.submit()"><option value="">All</option><option value="published" @selected(request('status')==='published')>Published</option><option value="draft" @selected(request('status')==='draft')>Draft</option></select>
            </form>
        </x-slot:actions>
        <div class="table-responsive"><table class="table app-table table-hover align-middle">
            <thead><tr><th>Product</th><th>Category</th><th class="text-end">Price</th><th class="text-end">Stock</th><th>Status</th><th class="text-end"></th></tr></thead>
            <tbody>
            @forelse ($products as $p)
                <tr>
                    <td class="fw-semibold">{{ $p->name }}@if ($p->is_featured)<x-admin.badge tone="warning">Featured</x-admin.badge>@endif<div class="small text-body-secondary">{{ $p->item->name ?? '—' }}</div></td>
                    <td class="small">{{ $p->category->name ?? '—' }}</td>
                    <td class="text-end">{{ number_format($p->price, 2) }}@if ($p->onSale())<div class="small text-decoration-line-through text-body-secondary">{{ number_format($p->compare_at_price, 2) }}</div>@endif</td>
                    <td class="text-end">{{ $p->item?->is_service ? '∞' : number_format($p->stock(), 0) }}</td>
                    <td><x-admin.badge :tone="$p->is_published ? 'success' : 'muted'">{{ $p->is_published ? 'Published' : 'Draft' }}</x-admin.badge></td>
                    <td class="text-end text-nowrap">
                        @if ($canEdit)
                            <form method="POST" action="{{ route('ecommerce.catalog.publish', $p) }}" class="d-inline">@csrf @method('PATCH')<button class="btn btn-action" title="{{ $p->is_published ? 'Unpublish' : 'Publish' }}"><i class="bi {{ $p->is_published ? 'bi-eye-slash' : 'bi-eye' }}"></i></button></form>
                            <a href="{{ route('ecommerce.catalog.edit', $p) }}" class="btn btn-action"><i class="bi bi-pencil-square"></i></a>
                            <x-admin.delete-button :action="route('ecommerce.catalog.destroy', $p)" :confirm="'Remove '.$p->name.' from store?'" />
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6"><x-admin.empty icon="bag" text="No products yet. Add one from your inventory." /></td></tr>
            @endforelse
            </tbody></table></div>
        @if ($products->hasPages())
        <x-slot:footer>{{ $products->links('pagination::bootstrap-5') }}</x-slot:footer>
        @endif
    </x-admin.card>
</div>
@endsection
