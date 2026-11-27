{{-- <x-admin.input name="email" label="Email" type="email" required icon="envelope" :value="$user->email" hint="..."/> --}}
@props(['name', 'label' => null, 'type' => 'text', 'value' => null, 'icon' => null, 'hint' => null, 'required' => false, 'optional' => null])
@php $id = $attributes->get('id', 'f_'.$name); @endphp
<div class="app-field">
    @if ($label)<label for="{{ $id }}" class="form-label">{{ $label }}@if ($required)<span class="req">*</span>@endif @if ($optional)<span class="opt">{{ $optional === true ? '(optional)' : $optional }}</span>@endif</label>@endif
    <div @class(['input-icon' => $icon])>
        @if ($icon)<i class="bi bi-{{ $icon }}"></i>@endif
        <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}"
               @if ($type !== 'password') value="{{ old($name, $value) }}" @endif
               @required($required)
               {{ $attributes->except('id')->class(['form-control', 'is-invalid' => $errors->has($name)]) }}>
        @error($name)<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    @if ($hint)<div class="form-text">{{ $hint }}</div>@endif
</div>
