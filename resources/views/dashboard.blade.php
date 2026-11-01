@extends('layouts.admin.main')
@section('title', 'Dashboard')
@section('subtitle', "Here's what's happening today")

@php
    $me = auth()->user();
    $modules = collect(config('module_permissions.modules'))->filter(fn ($m, $k) => $me->can("$k.view"));
    $linkFor = fn ($k) => match ($k) { 'users' => route('users.index'), 'roles' => route('roles.index'), 'tenants' => route('tenants.index'), default => route('modules.show', $k) };
    $hour = now()->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
@endphp

@section('content')
<div class="app-content">
    <x-admin.page-header :title="$greeting.', '.explode(' ', $me->name)[0]" subtitle="Here's an overview of your workspace.">
        @if ($me->tenant_id)
            <x-admin.badge tone="success" icon="building">{{ tenant('name') }}</x-admin.badge>
        @endif
        <x-admin.badge tone="primary" icon="shield-check">{{ $me->roles->pluck('name')->join(', ') ?: 'No role assigned' }}</x-admin.badge>
    </x-admin.page-header>

    <div class="row g-4 mb-4">
        @can('users.view')
            <div class="col-sm-6 col-xl-3">
                <x-admin.stat label="Total users" :value="\App\Models\User::where('tenant_id', $me->tenant_id)->count()" icon="people" tone="primary" :href="route('users.index')" />
            </div>
        @endcan
        @can('roles.view')
            <div class="col-sm-6 col-xl-3">
                <x-admin.stat label="Roles" :value="\App\Models\Role::where('guard_name', 'api')->count()" icon="shield-lock" tone="warning" :href="route('roles.index')" />
            </div>
        @endcan
        @can('tenants.view')
            <div class="col-sm-6 col-xl-3">
                <x-admin.stat label="Tenants" :value="\App\Models\Tenant::count()" icon="buildings" tone="info" :href="route('tenants.index')" />
            </div>
        @endcan
        <div class="col-sm-6 col-xl-3">
            <x-admin.stat label="Your modules" :value="$modules->count()" icon="grid" tone="success" />
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-admin.stat label="Member since" :value="$me->created_at?->format('M Y')" icon="calendar3" tone="info" />
        </div>
    </div>

    <x-admin.card title="Quick access" subtitle="Modules available to your role">
        @if ($modules->isEmpty())
            <x-admin.empty icon="lock" text="No modules assigned yet. Ask your administrator to grant your role permissions." />
        @else
            <div class="row g-3">
                @foreach ($modules as $key => $m)
                    <div class="col-sm-6 col-lg-4 col-xxl-3">
                        <a href="{{ $linkFor($key) }}" class="app-tile">
                            <span class="app-tile__icon tone-primary"><i class="bi {{ $m['icon'] }}"></i></span>
                            <div>
                                <div class="fw-semibold">{{ $m['label'] }}</div>
                                <div class="small text-body-secondary">{{ $m['group'] }}</div>
                            </div>
                            <i class="bi bi-chevron-right app-tile__arrow"></i>
                        </a>
                    </div>
                @endforeach
            </div>
        @endif
    </x-admin.card>
</div>
@endsection
