<x-admin.badge :tone="\App\Models\Hrm\LeaveRequest::STATUSES[$status] ?? 'muted'">{{ ucfirst($status) }}</x-admin.badge>
