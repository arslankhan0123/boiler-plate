@props(['label', 'value', 'icon', 'tone' => 'primary', 'href' => null])
@php $tag = $href ? 'a' : 'div'; @endphp
<{{ $tag }} @if ($href) href="{{ $href }}" @endif class="app-card d-block text-decoration-none h-100">
    <div class="app-stat">
        <div class="app-stat__icon tone-{{ $tone }}"><i class="bi bi-{{ $icon }}"></i></div>
        <div>
            <p class="app-stat__label">{{ $label }}</p>
            <p class="app-stat__value">{{ $value }}</p>
        </div>
    </div>
</{{ $tag }}>
