@extends('layouts.admin.main')
@section('title', 'Module Registry')
@section('subtitle', 'Modules, dependencies and how many tenants use them')

@section('content')
<div class="app-content">
    <x-admin.page-header title="Module Registry" subtitle="Defined in config/modules.php and synced to the master database." />

    @foreach ($groups as $group => $groupName)
        @php $mods = collect($modules)->where('group', $group); @endphp
        @continue($mods->isEmpty())
        <x-admin.card flush :title="$groupName" class="mb-4">
            <div class="table-responsive">
                <table class="table app-table">
                    <thead><tr><th>Module</th><th>Scope</th><th>Requires</th><th>Features</th><th>Permissions</th><th>Tenants</th></tr></thead>
                    <tbody>
                    @foreach ($mods as $code => $m)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <span class="app-tile__icon tone-primary"><i class="bi {{ $m['icon'] }}"></i></span>
                                    <div><div class="fw-semibold">{{ $m['name'] }} <code class="small text-body-secondary">{{ $code }}</code></div>
                                        <div class="small text-body-secondary">{{ $m['description'] }}</div></div>
                                </div>
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
