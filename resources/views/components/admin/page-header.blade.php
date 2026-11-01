@props(['title', 'subtitle' => null, 'back' => null])
<div class="app-page-header">
    <div class="app-page-header__lead">
        @if ($back)
            <a href="{{ $back }}" class="app-back" title="Back"><i class="bi bi-chevron-left"></i></a>
        @endif
        <div>
            <h1 class="app-page-header__title">{{ $title }}</h1>
            @if ($subtitle)<p class="app-page-header__subtitle">{{ $subtitle }}</p>@endif
        </div>
    </div>
    @if (trim($slot))
        <div class="app-page-header__actions">{{ $slot }}</div>
    @endif
</div>
