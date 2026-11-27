@props(['name', 'value', 'title', 'desc' => null, 'checked' => false, 'badge' => null, 'tone' => 'primary'])
<label class="app-radio-card">
    <input type="radio" name="{{ $name }}" value="{{ $value }}" @checked($checked) {{ $attributes }}>
    <span>
        <span class="app-radio-card__title">{{ $title }}</span>
        @if ($desc)<span class="app-radio-card__desc">{{ $desc }}</span>@endif
    </span>
    @if ($badge)<x-admin.badge :tone="$tone">{{ $badge }}</x-admin.badge>@endif
</label>
