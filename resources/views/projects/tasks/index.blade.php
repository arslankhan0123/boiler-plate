@extends('layouts.admin.main')
@section('title', 'Task Board')
@section('subtitle', $manage ? 'Projects · All tasks' : 'Projects · My tasks')

@section('content')
<div class="app-content">
    <x-admin.page-header title="Task Board" :subtitle="$manage ? 'Create tasks, assign them to employees and track progress.' : 'Your tasks. Drag a card to another column to update its status.'">
        @if ($manage)
            <x-admin.button type="button" icon="plus-lg" data-bs-toggle="modal" data-bs-target="#taskModal">New Task</x-admin.button>
        @endif
    </x-admin.page-header>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3"><x-admin.stat label="Open tasks" :value="$stats['open']" icon="list-task" tone="primary" /></div>
        <div class="col-6 col-xl-3"><x-admin.stat label="Due today" :value="$stats['due_today']" icon="calendar-event" tone="warning" /></div>
        <div class="col-6 col-xl-3"><x-admin.stat label="Overdue" :value="$stats['overdue']" icon="exclamation-triangle" tone="danger" /></div>
        <div class="col-6 col-xl-3"><x-admin.stat label="Done this week" :value="$stats['done_week']" icon="check2-circle" tone="success" /></div>
    </div>

    <form method="GET" class="d-flex flex-wrap gap-2 mb-3">
        <div class="input-icon" style="width:240px"><i class="bi bi-search"></i>
            <input type="search" name="search" value="{{ request('search') }}" class="form-control" style="min-height:42px" placeholder="Search tasks…"></div>
        @if ($manage)
            <select name="assignee" class="form-select" style="width:220px;min-height:42px" onchange="this.form.submit()">
                <option value="">Everyone</option>
                <option value="none" @selected(request('assignee') === 'none')>Unassigned</option>
                @foreach ($employees as $e)<option value="{{ $e->id }}" @selected(request('assignee') == $e->id)>{{ $e->name }}</option>@endforeach
            </select>
        @endif
        <select name="priority" class="form-select" style="width:160px;min-height:42px" onchange="this.form.submit()">
            <option value="">Any priority</option>
            @foreach (\App\Models\Projects\Task::PRIORITIES as $k => [$l])<option value="{{ $k }}" @selected(request('priority') === $k)>{{ $l }}</option>@endforeach
        </select>
        @if (request()->hasAny(['search', 'assignee', 'priority']))<a href="{{ route('projects.tasks.index') }}" class="btn btn-light">Clear</a>@endif
    </form>

    <div class="kanban">
        @foreach ($columns as $status => [$label, $color, $icon])
            @php $items = $tasks->get($status, collect()); @endphp
            <section class="kanban-col" data-status="{{ $status }}">
                <header class="kanban-col__head" style="--col: {{ $color }}">
                    <i class="bi {{ $icon }}"></i> {{ $label }}
                    <span class="kanban-count">{{ $items->count() }}</span>
                    @if ($manage)
                        <button type="button" class="kanban-add" title="Add task here" data-bs-toggle="modal" data-bs-target="#taskModal" data-status="{{ $status }}"><i class="bi bi-plus-lg"></i></button>
                    @endif
                </header>
                <div class="kanban-list" data-status="{{ $status }}">
                    @foreach ($items as $t)
                        @php [$pl, $pt] = \App\Models\Projects\Task::PRIORITIES[$t->priority] ?? ['', 'muted']; @endphp
                        <article class="kanban-card" draggable="true" data-id="{{ $t->id }}" data-move="{{ route('projects.tasks.move', $t->id) }}">
                            <div class="d-flex justify-content-between gap-2 mb-1">
                                <x-admin.badge :tone="$pt">{{ $pl }}</x-admin.badge>
                                @if ($t->comments_count)<span class="small text-body-secondary"><i class="bi bi-chat"></i> {{ $t->comments_count }}</span>@endif
                            </div>
                            <a href="{{ route('projects.tasks.show', $t->id) }}" class="kanban-card__title">{{ $t->title }}</a>
                            <div class="kanban-card__foot">
                                @if ($t->due_date)
                                    <span class="{{ $t->isOverdue() ? 'text-danger fw-semibold' : 'text-body-secondary' }}"><i class="bi bi-calendar3"></i> {{ $t->due_date->format('d M') }}</span>
                                @else<span></span>@endif
                                @if ($t->assignee)
                                    <span class="app-avatar" style="width:26px;height:26px;font-size:.62rem" title="{{ $t->assignee->name }}">{{ \App\Support\Hrm\Hrm::initials($t->assignee->name) }}</span>
                                @else<span class="small text-body-secondary">Unassigned</span>@endif
                            </div>
                        </article>
                    @endforeach
                    <div class="kanban-empty {{ $items->isEmpty() ? '' : 'd-none' }}">Drop tasks here</div>
                </div>
            </section>
        @endforeach
    </div>
    <p class="form-text mt-2">Completed tasks stay on the board for 30 days.</p>
</div>

@if ($manage)
    <div class="modal fade" id="taskModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content" style="border-radius:16px">
        <form method="POST" action="{{ route('projects.tasks.store') }}">@csrf
            <div class="modal-header"><h5 class="modal-title fw-bold">New task</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">@include('projects.tasks._fields', ['task' => null])</div>
            <div class="modal-footer"><x-admin.button variant="light" type="button" data-bs-dismiss="modal">Cancel</x-admin.button><x-admin.button icon="check2">Create Task</x-admin.button></div>
        </form>
    </div></div></div>
@endif
@endsection

@push('styles')
<style>
    .kanban { display: grid; grid-template-columns: repeat(4, minmax(260px, 1fr)); gap: 1rem; overflow-x: auto; padding-bottom: .5rem; align-items: start; }
    /* Each column has a fixed height and scrolls on its own. */
    .kanban-col { background: #eef1f6; border-radius: 14px; padding: .75rem .5rem .75rem .75rem; display: flex; flex-direction: column;
        height: clamp(380px, calc(100vh - 300px), 900px); min-height: 0; }
    [data-bs-theme="dark"] .kanban-col { background: var(--app-hover); }
    .kanban-col__head { display: flex; align-items: center; gap: .5rem; font-weight: 700; font-size: .9rem; padding: .25rem .35rem .75rem; border-bottom: 3px solid var(--col); margin-bottom: .75rem; }
    .kanban-col__head > i { color: var(--col); }
    .kanban-count { background: #fff; border-radius: 99px; padding: 0 .55rem; font-size: .75rem; color: var(--app-text-muted); }
    .kanban-add { margin-left: auto; border: 0; background: transparent; color: var(--app-text-muted); border-radius: 8px; padding: .15rem .4rem; }
    .kanban-add:hover { background: #fff; color: var(--app-primary); }
    .kanban-list { flex: 1 1 auto; min-height: 0; overflow-y: auto; overflow-x: hidden; display: grid; gap: .6rem; align-content: start;
        border-radius: 10px; transition: background .15s; padding: 2px .35rem 2px 2px; scrollbar-width: thin; scrollbar-color: #c7d2fe transparent; }
    .kanban-list::-webkit-scrollbar { width: 6px; }
    .kanban-list::-webkit-scrollbar-thumb { background: #c7d2fe; border-radius: 99px; }
    .kanban-list::-webkit-scrollbar-thumb:hover { background: #a5b4fc; }
    .kanban-col__head { flex-shrink: 0; margin-right: .25rem; }
    .kanban-list.is-over { background: rgba(99,102,241,.08); outline: 2px dashed #a5b4fc; outline-offset: 2px; }
    .kanban-card { background: var(--app-surface); border: 1px solid var(--app-border); border-radius: 12px; padding: .65rem .8rem; cursor: grab;
        box-shadow: 0 1px 2px rgba(15,23,42,.05); transition: box-shadow .15s, transform .15s; }
    .kanban-card:hover { box-shadow: 0 6px 18px rgba(15,23,42,.10); }
    .kanban-card.is-dragging { opacity: .45; transform: rotate(1.5deg); }
    .kanban-card.is-saving { opacity: .6; pointer-events: none; }
    .kanban-card__title { display: block; font-weight: 600; color: var(--app-text); text-decoration: none; margin: .2rem 0 .45rem; line-height: 1.35;
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .kanban-card__title:hover { color: var(--app-primary); }
    .kanban-card__foot { display: flex; justify-content: space-between; align-items: center; font-size: .78rem; }
    .kanban-empty { border: 2px dashed var(--app-border); border-radius: 10px; padding: 1.5rem; text-align: center; color: var(--app-text-muted); font-size: .8rem; }
</style>
@endpush

@push('scripts')
<script>
(() => {
    const csrf = document.querySelector('meta[name=csrf-token]')?.content || '{{ csrf_token() }}';
    let dragged = null, fromList = null, fromNext = null;

    const refresh = list => {
        const cards = list.querySelectorAll('.kanban-card');
        list.querySelector('.kanban-empty')?.classList.toggle('d-none', cards.length > 0);
        list.closest('.kanban-col').querySelector('.kanban-count').textContent = cards.length;
    };
    const afterCard = (list, y) => [...list.querySelectorAll('.kanban-card:not(.is-dragging)')]
        .reduce((best, el) => { const box = el.getBoundingClientRect(), off = y - box.top - box.height / 2;
            return off < 0 && off > best.off ? { off, el } : best; }, { off: -Infinity }).el;

    document.querySelectorAll('.kanban-card').forEach(card => {
        card.addEventListener('dragstart', e => {
            dragged = card; fromList = card.parentElement; fromNext = card.nextElementSibling;
            card.classList.add('is-dragging'); e.dataTransfer.effectAllowed = 'move';
        });
        card.addEventListener('dragend', () => card.classList.remove('is-dragging'));
    });

    document.querySelectorAll('.kanban-list').forEach(list => {
        list.addEventListener('dragover', e => {
            if (!dragged) return;
            e.preventDefault(); list.classList.add('is-over');
            // Auto-scroll the column when dragging near its top/bottom edge.
            const r = list.getBoundingClientRect(), edge = 50;
            if (e.clientY < r.top + edge) list.scrollTop -= 12;
            else if (e.clientY > r.bottom - edge) list.scrollTop += 12;
            const next = afterCard(list, e.clientY);
            next ? list.insertBefore(dragged, next) : list.insertBefore(dragged, list.querySelector('.kanban-empty'));
        });
        list.addEventListener('dragleave', e => { if (!list.contains(e.relatedTarget)) list.classList.remove('is-over'); });
        list.addEventListener('drop', async e => {
            e.preventDefault(); list.classList.remove('is-over');
            if (!dragged) return;
            const card = dragged; dragged = null;
            const status = list.dataset.status, moved = fromList !== list;
            refresh(list); refresh(fromList);
            card.classList.add('is-saving');
            try {
                const res = await fetch(card.dataset.move, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({ status, order: [...list.querySelectorAll('.kanban-card')].map(c => +c.dataset.id) }),
                });
                if (!res.ok) throw new Error((await res.json().catch(() => ({}))).message || 'Could not update the task.');
                const data = await res.json();
                if (moved) Toast.success(`Moved to ${data.label}.`);
            } catch (err) {
                fromList.insertBefore(card, fromNext && fromNext.parentElement === fromList ? fromNext : fromList.querySelector('.kanban-empty'));
                refresh(list); refresh(fromList);
                Toast.error(err.message, 'Not saved');
            } finally { card.classList.remove('is-saving'); }
        });
    });

    // "+" on a column preselects that status in the modal.
    document.querySelectorAll('.kanban-add').forEach(b => b.addEventListener('click', () => {
        const sel = document.querySelector('#taskModal select[name=status]'); if (sel) sel.value = b.dataset.status;
    }));
    @if ($errors->any() && $manage)
        document.addEventListener('DOMContentLoaded', () => new bootstrap.Modal('#taskModal').show());
    @endif
})();
</script>
@endpush
