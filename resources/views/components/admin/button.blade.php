{{-- <x-admin.button variant="primary|light|danger|outline-primary" icon="plus-lg" href="..." size="sm"> --}}
@props(['variant' => 'primary', 'icon' => null, 'href' => null, 'size' => null, 'type' => 'submit'])
@php $classes = 'btn btn-'.$variant.($size ? ' btn-'.$size : ''); @endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>@if ($icon)<i class="bi bi-{{ $icon }}"></i>@endif{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>@if ($icon)<i class="bi bi-{{ $icon }}"></i>@endif{{ $slot }}</button>
@endif
