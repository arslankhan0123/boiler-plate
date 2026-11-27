@extends('layouts.admin.main')
@section('title', 'SEO & Marketing')
@section('subtitle', 'E-commerce')
@section('content')
<div class="app-content">
    <x-admin.page-header title="SEO & Marketing" subtitle="Search metadata for your store and products" />
    <x-admin.card title="Store SEO" class="mb-4">
        <form method="POST" action="{{ route('ecommerce.seo.store') }}">@csrf @method('PUT')
            <div class="row g-3">
                <div class="col-md-6"><x-admin.input name="store_seo_title" label="Store meta title" :value="$storeTitle" /></div>
                <div class="col-md-6"><x-admin.input name="store_seo_description" label="Store meta description" :value="$storeDescription" /></div>
            </div>
            <div class="mt-2"><x-admin.button icon="check-lg">Save store SEO</x-admin.button></div>
        </form>
    </x-admin.card>
    <x-admin.card flush>
        <div class="table-responsive"><table class="table app-table align-middle">
            <thead><tr><th>Product</th><th>Meta title</th><th>Meta description</th><th class="text-end"></th></tr></thead>
            <tbody>
            @forelse ($products as $p)
                <form method="POST" action="{{ route('ecommerce.seo.product', $p) }}">@csrf @method('PUT')
                <tr>
                    <td class="fw-semibold small">{{ $p->name }}</td>
                    <td><input name="seo_title" value="{{ $p->seo_title }}" class="form-control form-control-sm" placeholder="{{ $p->name }}"></td>
                    <td><input name="seo_description" value="{{ $p->seo_description }}" class="form-control form-control-sm"></td>
                    <td class="text-end"><button class="btn btn-sm btn-light">Save</button></td>
                </tr>
                </form>
            @empty
                <tr><td colspan="4"><x-admin.empty icon="search" text="No products to optimize." /></td></tr>
            @endforelse
            </tbody></table></div>
        <x-slot:footer>{{ $products->links('pagination::bootstrap-5') }}</x-slot:footer>
    </x-admin.card>
</div>
@endsection
