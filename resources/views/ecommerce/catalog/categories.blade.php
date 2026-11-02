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
                @forelse ($rows as $row)
                    @php $c = $row['cat']; $depth = $row['depth']; @endphp
                    <tr @class(['table-active' => $editing && $editing->id === $c->id])>
                        <td>
                            <span style="display:inline-block;width:{{ $depth * 1.6 }}rem"></span>
                            @if ($depth > 0)<span class="text-body-secondary">↳ </span>@endif
                            <span class="fw-semibold">{{ $c->name }}</span>
                            @if ($c->parent)<span class="small text-body-secondary"> · under {{ $c->parent->name }}</span>@endif
                        </td>
                        <td class="text-center">{{ $c->products_count }}</td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('ecommerce.catalog.categories.edit', $c) }}" class="btn btn-action" title="Edit"><i class="bi bi-pencil-square"></i></a>
                            <x-admin.delete-button :action="route('ecommerce.catalog.categories.destroy', $c)" :confirm="'Delete '.$c->name.'? Its subcategories will become top-level.'" />
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3"><x-admin.empty icon="tags" text="No categories yet. Add one on the right." /></td></tr>
                @endforelse
                </tbody>
            </table></div>
        </x-admin.card></div>

        <div class="col-lg-4"><x-admin.card :title="$editing ? 'Edit category' : 'New category'">
            <form method="POST" action="{{ $editing ? route('ecommerce.catalog.categories.update', $editing) : route('ecommerce.catalog.categories.store') }}">
                @csrf @if ($editing)@method('PUT')@endif
                <x-admin.input name="name" label="Name" :value="$editing?->name" required />
                <x-admin.select name="parent_id" label="Parent category" :options="$parents" :value="$editing?->parent_id" placeholder="— None (top level) —" hint="Pick a parent to make this a subcategory" />
                <x-admin.input name="sort" label="Sort order" type="number" :value="$editing?->sort" optional />
                <div class="d-grid gap-2 mt-3">
                    <x-admin.button :icon="$editing ? 'check-lg' : 'plus-lg'">{{ $editing ? 'Save changes' : 'Add category' }}</x-admin.button>
                    @if ($editing)<x-admin.button variant="light" :href="route('ecommerce.catalog.categories')" type="button">Cancel</x-admin.button>@endif
                </div>
            </form>
        </x-admin.card></div>
    </div>
</div>
@endsection
