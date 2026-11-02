@extends('layouts.admin.main')
@section('title', 'Activities')
@section('subtitle', 'CRM')
@section('content')
<style>
    .seg{display:inline-flex;gap:3px;padding:4px;background:rgba(130,140,160,.12);border-radius:12px;flex-wrap:wrap}
    .seg__item{display:inline-flex;align-items:center;gap:.4rem;padding:.42rem .85rem;border-radius:9px;font-size:.84rem;font-weight:600;line-height:1;color:var(--bs-secondary-color,#64748b);text-decoration:none;transition:background .15s,color .15s,box-shadow .15s;white-space:nowrap}
    .seg__item:hover{color:var(--bs-body-color,#1e293b)}
    .seg__item.is-active{background:var(--app-surface,#fff);color:var(--app-primary,#4f46e5);box-shadow:0 1px 4px rgba(16,24,40,.12)}
    .seg__count{display:inline-flex;align-items:center;justify-content:center;min-width:18px;height:18px;padding:0 5px;border-radius:999px;font-size:.7rem;font-weight:700;background:rgba(130,140,160,.22);color:inherit}
    .seg__item.is-active .seg__count{background:color-mix(in srgb,var(--app-primary,#4f46e5) 16%,transparent)}
    .seg__count--danger{background:rgba(239,68,68,.16);color:#ef4444}
</style>
<div class="app-content">
    <x-admin.page-header title="Activities & Follow-ups" :subtitle="$openCount.' open · '.$overdueCount.' overdue'" />
    <div class="row g-4">
        <div class="col-lg-8">
            <x-admin.card flush>
                <x-slot:actions>
                    @php $counts = ['open' => $openCount, 'overdue' => $overdueCount, 'done' => $doneCount]; @endphp
                    <div class="seg" role="tablist">
                        @foreach (['open' => 'Open', 'today' => 'Today', 'overdue' => 'Overdue', 'done' => 'Done', 'all' => 'All'] as $k => $l)
                            <a href="{{ route('crm.activities.index', ['filter' => $k]) }}" class="seg__item {{ $filter === $k ? 'is-active' : '' }}" role="tab" aria-selected="{{ $filter === $k ? 'true' : 'false' }}">
                                {{ $l }}
                                @if (isset($counts[$k]) && $counts[$k] > 0)
                                    <span class="seg__count {{ $k === 'overdue' ? 'seg__count--danger' : '' }}">{{ $counts[$k] }}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </x-slot:actions>
                <div class="table-responsive"><table class="table app-table table-hover align-middle">
                    <thead><tr><th></th><th>Subject</th><th>Type</th><th>Due</th><th>Owner</th><th class="text-end"></th></tr></thead>
                    <tbody>
                    @forelse ($activities as $a)
                        <tr class="{{ $a->isOverdue() ? 'table-danger' : '' }}">
                            <td>
                                @if ($canEdit)<form method="POST" action="{{ route('crm.activities.toggle', $a) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-link p-0"><i class="bi {{ $a->done ? 'bi-check-circle-fill text-success' : 'bi-circle' }}"></i></button></form>
                                @else<i class="bi {{ $a->done ? 'bi-check-circle-fill text-success' : 'bi-circle' }}"></i>@endif
                            </td>
                            <td class="{{ $a->done ? 'text-decoration-line-through text-body-secondary' : 'fw-semibold' }}">{{ $a->subject }}</td>
                            <td class="small"><i class="bi bi-{{ $a->icon() }} me-1"></i>{{ $a->typeLabel() }}</td>
                            <td class="small">{{ $a->due_at?->format('d M, H:i') ?? '—' }}</td>
                            <td class="small">{{ $a->owner->name ?? '—' }}</td>
                            <td class="text-end">@if ($canEdit)<x-admin.delete-button :action="route('crm.activities.destroy', $a)" confirm="Delete this activity?" />@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-admin.empty icon="check2-square" text="No activities here." /></td></tr>
                    @endforelse
                    </tbody></table></div>
                <x-slot:footer>{{ $activities->links('pagination::bootstrap-5') }}</x-slot:footer>
            </x-admin.card>
        </div>
        <div class="col-lg-4">
            @if ($canEdit)
            <x-admin.card title="Quick add">
                <form method="POST" action="{{ route('crm.activities.store') }}">@csrf
                    <x-admin.input name="subject" label="Subject" required />
                    <x-admin.select name="type" label="Type" :options="$types" value="task" required />
                    <x-admin.input name="due_at" label="Due" type="datetime-local" optional />
                    <x-admin.select name="owner_id" label="Owner" :options="$owners" :value="auth()->id()" placeholder="Unassigned" />
                    <div class="d-grid mt-2"><x-admin.button icon="plus-lg">Add activity</x-admin.button></div>
                </form>
            </x-admin.card>
            @endif
        </div>
    </div>
</div>
@endsection
