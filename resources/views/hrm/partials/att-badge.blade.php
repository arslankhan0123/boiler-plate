@php [$label, $tone] = \App\Models\Hrm\Attendance::STATUSES[$status] ?? [ucfirst($status), 'muted']; @endphp
<x-admin.badge :tone="$tone">{{ $label }}</x-admin.badge>
