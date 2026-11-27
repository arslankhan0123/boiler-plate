@extends('layouts.admin.main')
@section('title', $module['name'])
@section('subtitle', $module['description'])

@section('content')
<div class="app-content">
    <x-admin.page-header :title="$module['name']" :subtitle="$module['description']" :back="route('dashboard')" />

    @if ($module['visible_features'])
        <div class="row g-3">
            @foreach ($module['visible_features'] as $key => $f)
                <div class="col-sm-6 col-lg-4 col-xxl-3">
                    <a href="{{ \App\Support\ModuleRegistry::url($code, $key) }}" class="app-tile">
                        <span class="app-tile__icon tone-primary"><i class="bi {{ $module['icon'] }}"></i></span>
                        <div><div class="fw-semibold">{{ $f['name'] }}</div><div class="small text-body-secondary">{{ $module['name'] }}</div></div>
                        <i class="bi bi-chevron-right app-tile__arrow"></i>
                    </a>
                </div>
            @endforeach
        </div>
    @else
        <x-admin.card><x-admin.empty icon="lock" text="You can see this module, but no screens are assigned to your role yet." /></x-admin.card>
    @endif
</div>
@endsection
