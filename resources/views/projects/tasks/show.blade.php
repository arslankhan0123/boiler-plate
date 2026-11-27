@extends('layouts.admin.main')
@php
    [$sl, $sc] = \App\Models\Projects\Task::STATUSES[$task->status];
    [$pl, $pt] = \App\Models\Projects\Task::PRIORITIES[$task->priority] ?? ['', 'muted'];
@endphp
@section('title', $task->title)
@section('subtitle', 'Projects · Task #'.$task->id)

@section('content')
<div class="app-content">
    <x-admin.page-header :back="route('projects.tasks.index')" :title="$task->title" :subtitle="'Created by '.($task->created_by_name ?? '—').' · '.$task->created_at->diffForHumans()">
        @if ($manage)
            <form method="POST" action="{{ route('projects.tasks.destroy', $task->id) }}" onsubmit="return confirm('Delete this task?')">@csrf @method('DELETE')
                <x-admin.button variant="light" icon="trash3" class="text-danger">Delete</x-admin.button></form>
        @endif
    </x-admin.page-header>

    <div class="app-form-grid">
        <div class="d-grid gap-4">
            @if ($manage)
                <div class="app-card app-form-card">
                    <form method="POST" action="{{ route('projects.tasks.update', $task->id) }}">@csrf @method('PUT')
                        @include('projects.tasks._fields', ['task' => $task])
                        <div class="app-form-actions"><x-admin.button class="btn-lg-app" icon="check2">Save Changes</x-admin.button></div>
                    </form>
                </div>
            @else
                <x-admin.card title="Description">
                    <div style="white-space:pre-line">{{ $task->description ?: 'No description.' }}</div>
                </x-admin.card>
            @endif

            <x-admin.card :title="'Comments ('.$task->comments->count().')'">
                <div class="d-grid gap-3 mb-4">
                    @forelse ($task->comments as $c)
                        <div class="d-flex gap-3">
                            <span class="app-avatar">{{ \App\Support\Hrm\Hrm::initials($c->user_name) }}</span>
                            <div class="flex-grow-1">
                                <div class="small"><b>{{ $c->user_name }}</b> <span class="text-body-secondary">· {{ $c->created_at->diffForHumans() }}</span></div>
                                <div style="white-space:pre-line">{{ $c->body }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="text-body-secondary small">No comments yet.</div>
                    @endforelse
                </div>
                <form method="POST" action="{{ route('projects.tasks.comments.store', $task->id) }}">@csrf
                    <textarea name="body" rows="3" class="form-control" placeholder="Write an update or question…" required></textarea>
                    <x-admin.button class="mt-3" icon="send" size="sm">Comment</x-admin.button>
                </form>
            </x-admin.card>
        </div>

        <div class="app-aside">
            <x-admin.card title="Details">
                <dl class="row small mb-0 g-2">
                    <dt class="col-5 text-body-secondary fw-normal">Status</dt><dd class="col-7 mb-0"><span class="fw-semibold" style="color:{{ $sc }}">{{ $sl }}</span></dd>
                    <dt class="col-5 text-body-secondary fw-normal">Priority</dt><dd class="col-7 mb-0"><x-admin.badge :tone="$pt">{{ $pl }}</x-admin.badge></dd>
                    <dt class="col-5 text-body-secondary fw-normal">Due</dt><dd class="col-7 mb-0 {{ $task->isOverdue() ? 'text-danger fw-semibold' : '' }}">{{ $task->due_date?->format('d M Y') ?? '—' }}{{ $task->isOverdue() ? ' · overdue' : '' }}</dd>
                    <dt class="col-5 text-body-secondary fw-normal">Completed</dt><dd class="col-7 mb-0">{{ $task->completed_at?->format('d M Y, h:i A') ?? '—' }}</dd>
                </dl>
                <hr>
                <div class="small text-body-secondary mb-2">Assigned to</div>
                @if ($task->assignee)@include('hrm.partials.person', ['e' => $task->assignee, 'sub' => $task->assignee->designation->name ?? ''])@else<span class="text-body-secondary small">Unassigned</span>@endif
            </x-admin.card>

            @unless ($manage)
                <div class="app-card"><div class="app-card__body">
                    <h3 class="app-aside-title">Update status</h3>
                    <div class="d-grid gap-2">
                        @foreach (\App\Models\Projects\Task::STATUSES as $k => [$label, $color, $icon])
                            <button type="button" class="btn {{ $task->status === $k ? 'btn-primary' : 'btn-light' }} justify-content-start js-status" data-status="{{ $k }}">
                                <i class="bi {{ $icon }}"></i> {{ $label }}</button>
                        @endforeach
                    </div>
                </div></div>
            @endunless
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.js-status').forEach(btn => btn.addEventListener('click', async () => {
    const res = await fetch(@json(route('projects.tasks.move', $task->id)), {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token()) },
        body: JSON.stringify({ status: btn.dataset.status }),
    });
    if (res.ok) { location.reload(); } else { Toast.error('Could not update the task.'); }
}));
</script>
@endpush
