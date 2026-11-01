{{-- Picks admin-panel version for logged-in users, standalone page for guests. --}}
@php
    $tones = ['danger' => ['#ef4444', '#fee2e2'], 'warning' => ['#f59e0b', '#fef3c7'], 'primary' => ['#2563eb', '#dbeafe']];
    [$c1, $c2] = $tones[$tone ?? 'primary'];
@endphp
@include(auth()->check() ? 'errors.partials.panel' : 'errors.partials.guest', compact('code', 'title', 'icon', 'message', 'c1', 'c2'))
