@props(['title' => 'Tips', 'items' => []])
<div class="app-card app-tips"><div class="app-card__body">
    <h3><i class="bi bi-info-circle"></i>{{ $title }}</h3>
    <ul>@foreach ($items as $tip)<li>{{ $tip }}</li>@endforeach</ul>
</div></div>
