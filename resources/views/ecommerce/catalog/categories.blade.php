@extends('layouts.admin.main')
@section('title', 'Categories')
@section('subtitle', 'E-commerce')
@section('content')
<div class="app-content">
    <x-admin.page-header title="Store Categories" :back="route('ecommerce.catalog.index')" />
    <div class="row g-4">
        <div class="col-lg-8"><x-admin.card flush>
            <div class="table-responsive"><table class="table app-table align-middle">
                <thead><tr><th>Name</th><th class="text-center">Products</th><th class="text-end"></th></tr></thead>
                <tbody>
                @forelse ($categories as $c)
                    <tr><td class="fw-semibold">{{ $c->name }}</td><td class="text-center">{{ $c->products_count }}</td>
                    <td class="text-end"><x-admin.delete-button :action="route('ecommerce.catalog.categories.destroy', $c)" :confirm="'Delete '.$c->name.'?'" /></td></tr>
                @empty
                    <tr><td colspan="3"><x-admin.empty icon="tags" text="No categories yet." /></td></tr>
                @endforelse
                </tbody>
            </table></div>
        </x-admin.card></div>
        <div class="col-lg-4"><x-admin.card title="New category">
            <form method="POST" action="{{ route('ecommerce.catalog.categories.store') }}">@csrf
                <x-admin.input name="name" label="Name" required />
                <x-admin.input name="sort" label="Sort order" type="number" optional />
                <div class="d-grid mt-2"><x-admin.button icon="plus-lg">Add category</x-admin.button></div>
            </form>
        </x-admin.card></div>
    </div>
</div>
@endsection
