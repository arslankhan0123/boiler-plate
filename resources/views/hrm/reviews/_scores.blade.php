{{-- Read-only score bars. $review --}}
<div class="d-grid gap-2">
    @foreach (\App\Models\Hrm\Review::CRITERIA as $k => $label)
        @php $v = (int) ($review->scores[$k] ?? 0); @endphp
        <div class="d-flex align-items-center gap-3">
            <span class="small" style="width:150px">{{ $label }}</span>
            <div class="progress flex-grow-1" style="height:8px"><div class="progress-bar" style="width:{{ $v * 20 }}%;background:var(--app-btn-grad)"></div></div>
            <b class="small" style="width:30px">{{ $v }}/5</b>
        </div>
    @endforeach
</div>
