@props(['title' => null, 'subtitle' => null, 'flush' => false])
<div {{ $attributes->merge(['class' => 'app-card']) }}>
    @if ($title || isset($actions))
        <div class="app-card__header">
            <div>
                @if ($title)<h2 class="app-card__title">{{ $title }}</h2>@endif
                @if ($subtitle)<p class="app-card__subtitle">{{ $subtitle }}</p>@endif
            </div>
            @isset($actions)<div class="d-flex gap-2 align-items-center">{{ $actions }}</div>@endisset
        </div>
    @endif
    @if ($flush){{ $slot }}@else<div class="app-card__body">{{ $slot }}</div>@endif
    @isset($footer)<div class="app-card__footer">{{ $footer }}</div>@endisset
</div>
