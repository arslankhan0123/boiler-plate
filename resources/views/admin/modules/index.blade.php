@extends('layouts.admin.main')
@section('title', 'Module Registry')
@section('subtitle', 'Modules, dependencies and how many tenants use them')

@section('content')
<div class="app-content">
    <x-admin.page-header title="Module Registry" subtitle="Defined in config/modules.php and synced to the master database." />

    @php
        $statusOf = collect($modules)->map(fn ($m, $code) => \App\Support\ModuleRegistry::buildStatus($code));
        $done = $statusOf->where('status', 'complete')->count();
        $wip = $statusOf->where('status', 'in_progress')->count();
        $planned = $statusOf->where('status', 'planned')->count();
        $screens = $statusOf->sum('built'); $screensTotal = $statusOf->sum('total');
        $statusUi = [
            'complete' => ['Complete', 'success', 'check-circle-fill'],
            'in_progress' => ['In progress', 'warning', 'hourglass-split'],
            'planned' => ['Planned', 'muted', 'circle'],
        ];
    @endphp

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3"><x-admin.stat label="Complete modules" :value="$done.' / '.count($modules)" icon="check2-circle" tone="success" /></div>
        <div class="col-sm-6 col-xl-3"><x-admin.stat label="In progress" :value="$wip" icon="hourglass-split" tone="warning" /></div>
        <div class="col-sm-6 col-xl-3"><x-admin.stat label="Planned" :value="$planned" icon="calendar2-week" tone="info" /></div>
        <div class="col-sm-6 col-xl-3"><x-admin.stat label="Screens built" :value="$screens.' / '.$screensTotal" icon="window-stack" tone="primary" /></div>
    </div>

    @foreach ($groups as $group => $groupName)
        @php $mods = collect($modules)->where('group', $group); @endphp
        @continue($mods->isEmpty())
        <x-admin.card flush :title="$groupName" class="mb-4">
            <div class="table-responsive">
                <table class="table app-table">
                    <thead><tr><th>Module</th><th>Status</th><th>Scope</th><th>Requires</th><th>Features</th><th>Permissions</th><th>Tenants</th></tr></thead>
                    <tbody>
                    @foreach ($mods as $code => $m)
                        <tr @class(['row-complete' => $statusOf[$code]['status'] === 'complete'])>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <span class="app-tile__icon tone-primary"><i class="bi {{ $m['icon'] }}"></i></span>
                                    <div><div class="fw-semibold">{{ $m['name'] }}
                                        @if ($statusOf[$code]['status'] === 'complete')<i class="bi bi-check-circle-fill text-success" title="Complete"></i>@endif
                                        <code class="small text-body-secondary">{{ $code }}</code></div>
                                        <div class="small text-body-secondary">{{ $m['description'] }}</div></div>
                                </div>
                            </td>
                            <td class="text-nowrap">
                                @php $st = $statusOf[$code]; [$sl, $stone, $sicon] = $statusUi[$st['status']]; @endphp
                                <x-admin.badge :tone="$stone" :icon="$sicon">{{ $sl }}</x-admin.badge>
                                @if ($st['total'] > 1 && $st['status'] !== 'complete')<div class="small text-body-secondary mt-1">{{ $st['built'] }}/{{ $st['total'] }} screens</div>@endif
                            </td>
                            <td>
                                @php $tone = ['platform' => 'warning', 'core' => 'success', 'tenant' => 'primary'][$m['scope']] ?? 'muted'; @endphp
                                <x-admin.badge :tone="$tone">{{ ucfirst($m['scope']) }}</x-admin.badge>
                            </td>
                            <td>
                                @forelse ($m['requires'] as $r)
                                    <x-admin.badge tone="muted" class="mb-1">{{ $modules[$r]['name'] ?? $r }}</x-admin.badge>
                                @empty
                                    @if ($m['requires_any'])<span class="small text-body-secondary">Any business module</span>@else<span class="text-body-secondary">—</span>@endif
                                @endforelse
                            </td>
                            <td>{{ count($m['features']) }}</td>
                            <td>{{ count($m['permissions']) }}</td>
                            <td>
                                @if ($m['scope'] === 'tenant'){{ $usage[$code] ?? 0 }} / {{ $tenantCount }}
                                @elseif ($m['scope'] === 'core')<span class="small text-body-secondary">All</span>
                                @else<span class="text-body-secondary">—</span>@endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </x-admin.card>
    @endforeach
</div>
@endsection

@push('styles')
<style>
    .app-table tr.row-complete td { background: rgba(5,150,105,.045); }
    .app-table tr.row-complete td:first-child { box-shadow: inset 3px 0 0 #059669; }
</style>
@endpush
