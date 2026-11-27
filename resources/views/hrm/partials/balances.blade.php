{{-- Leave balance cards. $balances from Hrm::balances() --}}
<div class="row g-3">
    @forelse ($balances as $b)
        <div class="col-sm-6 col-xl-3">
            <div class="app-card h-100" style="border-top:3px solid {{ $b['type']->color }}">
                <div class="app-card__body py-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="small fw-semibold text-body-secondary">{{ $b['type']->name }}</div>
                        @unless ($b['type']->is_paid)<x-admin.badge tone="muted">Unpaid</x-admin.badge>@endunless
                    </div>
                    @if ($b['type']->is_paid)
                        <div class="app-stat__value" style="font-size:1.6rem">{{ rtrim(rtrim(number_format($b['remaining'], 1), '0'), '.') }}<span class="fs-6 text-body-secondary fw-normal"> / {{ rtrim(rtrim(number_format($b['allocated'], 1), '0'), '.') }} days left</span></div>
                        @php $pct = $b['allocated'] > 0 ? min(100, ($b['used'] + $b['pending']) / $b['allocated'] * 100) : 0; @endphp
                        <div class="progress mt-2" style="height:6px"><div class="progress-bar" style="width:{{ $pct }}%;background:{{ $b['type']->color }}"></div></div>
                    @else
                        <div class="app-stat__value" style="font-size:1.6rem">{{ $b['used'] }}<span class="fs-6 text-body-secondary fw-normal"> days taken</span></div>
                    @endif
                    <div class="small text-body-secondary mt-2">Used {{ $b['used'] }} · Pending {{ $b['pending'] }}</div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12"><x-admin.empty icon="calendar-x" text="No leave types yet." /></div>
    @endforelse
</div>
