@extends('layouts.admin.main')
@section('title', $config['label'])
@section('subtitle', "Module workspace")

@section('content')
<div class="app-content">
    <x-admin.page-header :title="$config['label']" subtitle="Module workspace">
        @can("$module.create")
            <x-admin.button icon="plus-lg" type="button" disabled>New</x-admin.button>
        @endcan
    </x-admin.page-header>

    <div class="row g-4">
        <div class="col-lg-8">
            <x-admin.card>
                <div class="app-empty">
                    <i class="bi {{ $config['icon'] }}"></i>
                    <div class="fw-semibold text-body mb-1">{{ $config['label'] }} screens coming soon</div>
                    This module's pages will appear here.
                </div>
            </x-admin.card>
        </div>
        <div class="col-lg-4">
            <x-admin.card title="Your access">
                <ul class="list-unstyled mb-0 d-grid gap-2">
                    @foreach (config('module_permissions.actions') as $a)
                        <li class="d-flex justify-content-between align-items-center">
                            <span class="text-capitalize">{{ $a }}</span>
                            @can("$module.$a")
                                <x-admin.badge tone="success" icon="check-lg">Allowed</x-admin.badge>
                            @else
                                <x-admin.badge tone="muted" icon="x-lg">No access</x-admin.badge>
                            @endcan
                        </li>
                    @endforeach
                </ul>
            </x-admin.card>
        </div>
    </div>
</div>
@endsection
