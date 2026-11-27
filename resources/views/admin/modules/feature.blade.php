@extends('layouts.admin.main')
@section('title', $feature['name'])
@section('subtitle', $module['name'])

@section('content')
<div class="app-content">
    <x-admin.page-header :title="$feature['name']" :subtitle="$module['name'].' · '.$module['description']" :back="route('app.module', $code)" />

    <div class="app-form-grid">
        <x-admin.card>
            <div class="app-empty">
                <i class="bi {{ $module['icon'] }}"></i>
                <div class="fw-semibold text-body mb-1">{{ $feature['name'] }}</div>
                This screen is ready to be built. Access to it is already protected by the
                <strong>{{ $module['name'] }}</strong> module and the <code>{{ $feature['permission'] }}</code> permission.
            </div>
        </x-admin.card>

        <div class="app-aside">
            <x-admin.card title="Your permissions">
                <ul class="list-unstyled mb-0 d-grid gap-2">
                    @foreach ($module['permissions'] as $perm => $label)
                        <li class="d-flex justify-content-between align-items-center gap-2">
                            <span class="small">{{ $label }}</span>
                            @can($perm)
                                <x-admin.badge tone="success" icon="check-lg">Allowed</x-admin.badge>
                            @else
                                <x-admin.badge tone="muted" icon="x-lg">No</x-admin.badge>
                            @endcan
                        </li>
                    @endforeach
                </ul>
            </x-admin.card>

            @if (count($siblings) > 1)
                <x-admin.card title="More in {{ $module['name'] }}">
                    <div class="d-grid gap-1">
                        @foreach ($siblings as $key => $f)
                            <a href="{{ \App\Support\ModuleRegistry::url($code, $key) }}" class="small py-1 {{ $key === $featureCode ? 'fw-bold' : '' }}">{{ $f['name'] }}</a>
                        @endforeach
                    </div>
                </x-admin.card>
            @endif
        </div>
    </div>
</div>
@endsection
