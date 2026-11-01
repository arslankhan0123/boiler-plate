@extends('layouts.admin.main')
@section('title', 'Audit Logs')
@section('subtitle', 'Settings')

@php
    $eventTone = ['created' => 'success', 'updated' => 'primary', 'deleted' => 'danger', 'hardDeleted' => 'danger', 'restored' => 'warning'];
    $fmt = fn ($v) => is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : (is_bool($v) ? ($v ? 'true' : 'false') : (string) $v);
@endphp

@section('content')
<div class="app-content">
    <x-admin.page-header title="Audit Logs" subtitle="Who changed what, and when." />

    <x-admin.card flush>
        <x-slot:actions>
            <form method="GET" class="d-flex flex-wrap gap-2">
                <select name="type" class="form-select" style="width:190px;min-height:40px" onchange="this.form.submit()">
                    <option value="">All records</option>
                    @foreach ($types as $k => $l)<option value="{{ $k }}" @selected(request('type') === $k)>{{ $l }}</option>@endforeach
                </select>
                <select name="event" class="form-select" style="width:140px;min-height:40px" onchange="this.form.submit()">
                    <option value="">Any action</option>
                    @foreach (['created', 'updated', 'deleted', 'restored'] as $e)<option value="{{ $e }}" @selected(request('event') === $e)>{{ ucfirst($e) }}</option>@endforeach
                </select>
                <select name="user" class="form-select" style="width:170px;min-height:40px" onchange="this.form.submit()">
                    <option value="">Any user</option>
                    @foreach ($allUsers as $id => $n)<option value="{{ $id }}" @selected(request('user') == $id)>{{ $n }}</option>@endforeach
                </select>
                <input type="date" name="from" value="{{ request('from') }}" class="form-control" style="width:150px;min-height:40px" onchange="this.form.submit()">
                <input type="date" name="to" value="{{ request('to') }}" class="form-control" style="width:150px;min-height:40px" onchange="this.form.submit()">
            </form>
        </x-slot:actions>
        <div class="table-responsive"><table class="table app-table align-middle">
            <thead><tr><th>When</th><th>User</th><th>Action</th><th>Record</th><th>Changes</th></tr></thead>
            <tbody>
            @forelse ($logs as $log)
                @php $changed = array_unique(array_merge(array_keys($log->old_values ?? []), array_keys($log->new_values ?? []))); @endphp
                <tr>
                    <td class="text-nowrap small">{{ $log->created_at?->format('d M Y') }}<div class="text-body-secondary">{{ $log->created_at?->format('h:i A') }}</div></td>
                    <td class="small">{{ $users[$log->user_id] ?? ($log->user_id ? 'User #'.$log->user_id : 'System') }}</td>
                    <td><x-admin.badge :tone="$eventTone[$log->event] ?? 'muted'">{{ \Illuminate\Support\Str::headline($log->event) }}</x-admin.badge></td>
                    <td class="small"><b>{{ \Illuminate\Support\Str::headline(class_basename($log->auditable_type)) }}</b> #{{ $log->auditable_id }}</td>
                    @php
                        $fields = array_values(array_diff($changed, ['id']));
                        $line = fn ($f) => $f.': '.(array_key_exists($f, $log->old_values ?? []) ? \Illuminate\Support\Str::limit($fmt($log->old_values[$f]), 30).' → ' : '').\Illuminate\Support\Str::limit($fmt($log->new_values[$f] ?? '—'), 40);
                        $full = implode("\n", array_map($line, $fields));
                    @endphp
                    <td class="small audit-changes">
                        @if ($fields)
                            <div class="audit-summary" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-custom-class="audit-tip" title="{{ $full }}">
                                @foreach (array_slice($fields, 0, 2) as $f)
                                    <div class="text-truncate"><span class="text-body-secondary">{{ $f }}:</span>
                                        @if (array_key_exists($f, $log->old_values ?? []))<span class="text-danger text-decoration-line-through">{{ \Illuminate\Support\Str::limit($fmt($log->old_values[$f]), 20) }}</span> → @endif
                                        <span class="text-success">{{ \Illuminate\Support\Str::limit($fmt($log->new_values[$f] ?? '—'), 28) }}</span></div>
                                @endforeach
                                @if (count($fields) > 2)<span class="audit-more">+{{ count($fields) - 2 }} more…</span>@endif
                            </div>
                        @else<span class="text-body-secondary">—</span>@endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5"><x-admin.empty icon="journal-text" text="No changes recorded yet." /></td></tr>
            @endforelse
            </tbody></table></div>
        @if ($logs->hasPages())
            <x-slot:footer>{{ $logs->links('pagination::bootstrap-5') }}</x-slot:footer>
        @endif
    </x-admin.card>
    <p class="form-text mt-3">Changes are recorded in the background — make sure the queue worker is running (<code>php artisan queue:work</code>).</p>
</div>
@endsection

@push('styles')
<style>
    .audit-changes { max-width: 380px; }
    .audit-summary { cursor: help; line-height: 1.5; }
    .audit-more { display: inline-block; margin-top: 2px; font-size: .75rem; font-weight: 600; color: var(--app-primary, #4f46e5); background: #eef2ff; border-radius: 6px; padding: 1px 8px; }
    .audit-tip .tooltip-inner { max-width: 420px; text-align: left; white-space: pre-line; font-size: .78rem; }
</style>
@endpush

@push('scripts')
<script>document.querySelectorAll('.audit-summary[data-bs-toggle="tooltip"]').forEach(el => window.bootstrap && new bootstrap.Tooltip(el));</script>
@endpush
