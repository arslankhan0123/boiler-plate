@props(['tone' => 'primary', 'icon' => null])
<span {{ $attributes->merge(['class' => 'app-badge app-badge--'.$tone]) }}>@if ($icon)<i class="bi bi-{{ $icon }}"></i>@endif{{ $slot }}</span>
