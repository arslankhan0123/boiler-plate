@extends('layouts.admin.main')
@section('title', $product->exists ? 'Edit product' : 'Add product')
@section('subtitle', 'E-commerce')
@section('content')
<div class="app-content">
    <x-admin.page-header :title="$product->exists ? 'Edit product' : 'Add product'" :back="route('ecommerce.catalog.index')" />
    <form method="POST" action="{{ $product->exists ? route('ecommerce.catalog.update', $product) : route('ecommerce.catalog.store') }}">
        @csrf @if ($product->exists)@method('PUT')@endif
        <div class="row g-4">
            <div class="col-lg-8"><x-admin.card title="Product details">
                <div class="row g-3">
                    <div class="col-md-7"><x-admin.select name="item_id" label="Inventory item" :options="$items" :value="$product->item_id" required placeholder="— Select item to publish —" hint="Stock & tax come from this item" /></div>
                    <div class="col-md-5"><x-admin.select name="category_id" label="Category" :options="$categories" :value="$product->category_id" placeholder="— None —" /></div>
                    <div class="col-12"><x-admin.input name="name" label="Display name" :value="$product->name" required /></div>
                    <div class="col-12"><label class="form-label">Description <span class="opt">(optional)</span></label><textarea name="description" rows="4" class="form-control">{{ old('description', $product->description) }}</textarea></div>
                    <div class="col-md-4"><x-admin.input name="price" label="Store price" type="number" step="any" :value="$product->price" required /></div>
                    <div class="col-md-4"><x-admin.input name="compare_at_price" label="Compare-at price" type="number" step="any" :value="$product->compare_at_price" optional hint="Shows a strike-through" /></div>
                    <div class="col-md-4"><x-admin.input name="sort" label="Sort order" type="number" :value="$product->sort" optional /></div>
                    <div class="col-12"><x-admin.input name="image_url" label="Image URL" :value="$product->image_url" optional /></div>
                </div>
            </x-admin.card></div>
            <div class="col-lg-4"><x-admin.card title="Visibility">
                <label class="form-check mb-2"><input type="checkbox" name="is_published" value="1" class="form-check-input" @checked($product->is_published ?? true)> <span class="form-check-label">Published (visible in store)</span></label>
                <label class="form-check mb-3"><input type="checkbox" name="is_featured" value="1" class="form-check-input" @checked($product->is_featured)> <span class="form-check-label">Featured</span></label>
                <div class="d-grid gap-2">
                    <x-admin.button icon="check-lg">{{ $product->exists ? 'Save' : 'Publish' }}</x-admin.button>
                    <x-admin.button variant="light" :href="route('ecommerce.catalog.index')" type="button">Cancel</x-admin.button>
                </div>
            </x-admin.card></div>
        </div>
    </form>
</div>
@endsection
