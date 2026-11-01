{{-- Notice board feed. $notices = collection of Announcement --}}
<div class="app-card">
    <div class="app-card__header"><h2 class="app-card__title"><i class="bi bi-megaphone me-2 text-primary"></i>Announcements</h2></div>
    <div class="app-card__body d-grid gap-3">
        @foreach ($notices as $n)
            <div class="d-flex gap-3 {{ ! $loop->last ? 'pb-3 border-bottom' : '' }}">
                <span class="app-tile__icon {{ $n->is_pinned ? 'tone-warning' : 'tone-primary' }}" style="width:38px;height:38px"><i class="bi {{ $n->is_pinned ? 'bi-pin-angle-fill' : 'bi-megaphone' }}"></i></span>
                <div class="flex-grow-1 min-w-0">
                    <div class="fw-semibold">{{ $n->title }}</div>
                    <div class="small text-body-secondary mb-1">{{ $n->author_name }} · {{ $n->created_at->diffForHumans() }}@if ($n->department) · {{ $n->department->name }}@endif</div>
                    <div class="small" style="white-space:pre-line">{{ \Illuminate\Support\Str::limit($n->body, 400) }}</div>
                </div>
            </div>
        @endforeach
    </div>
</div>
