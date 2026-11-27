{{-- Linked activities for a record. Expects: $activities, $canLog, $relatedType, $relatedId --}}
<x-admin.card title="Activities & follow-ups">
    @if ($canLog)
        <form method="POST" action="{{ route('crm.activities.store') }}" class="mb-3">@csrf
            <input type="hidden" name="related_type" value="{{ $relatedType }}">
            <input type="hidden" name="related_id" value="{{ $relatedId }}">
            <input type="hidden" name="owner_id" value="{{ auth()->id() }}">
            <div class="row g-2 align-items-end">
                <div class="col-md-5"><input name="subject" class="form-control form-control-sm" placeholder="Log a call, email, meeting…" required></div>
                <div class="col-md-3"><select name="type" class="form-select form-select-sm">@foreach (\App\Models\Crm\Activity::TYPES as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
                <div class="col-md-3"><input name="due_at" type="datetime-local" class="form-control form-control-sm"></div>
                <div class="col-md-1 d-grid"><button class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i></button></div>
            </div>
        </form>
    @endif
    @forelse ($activities as $a)
        <div class="d-flex align-items-start gap-2 py-2 border-bottom">
            @if ($canLog)
                <form method="POST" action="{{ route('crm.activities.toggle', $a) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-link p-0"><i class="bi {{ $a->done ? 'bi-check-circle-fill text-success' : 'bi-circle' }}"></i></button></form>
            @else
                <i class="bi {{ $a->done ? 'bi-check-circle-fill text-success' : 'bi-circle' }} mt-1"></i>
            @endif
            <div class="flex-grow-1">
                <div class="{{ $a->done ? 'text-decoration-line-through text-body-secondary' : 'fw-semibold' }}"><i class="bi bi-{{ $a->icon() }} me-1 small"></i>{{ $a->subject }}</div>
                <div class="small {{ $a->isOverdue() ? 'text-danger' : 'text-body-secondary' }}">{{ $a->typeLabel() }}@if ($a->due_at) · {{ $a->due_at->format('d M, H:i') }}@endif</div>
            </div>
        </div>
    @empty
        <x-admin.empty icon="check2-square" text="No activities logged yet." />
    @endforelse
</x-admin.card>
