{{-- <x-admin.select name="role" label="Role" :options="['a' => 'A']" :value="..." placeholder="Select"/> --}}
@props(['name', 'label' => null, 'options' => [], 'value' => null, 'placeholder' => null, 'hint' => null, 'required' => false, 'optional' => null])
@php $id = 'f_'.$name; $selected = old($name, $value); @endphp
<div class="app-field">
    @if ($label)<label for="{{ $id }}" class="form-label">{{ $label }}@if ($required)<span class="req">*</span>@endif @if ($optional)<span class="opt">{{ $optional === true ? '(optional)' : $optional }}</span>@endif</label>@endif
    <select id="{{ $id }}" name="{{ $name }}" @required($required)
            {{ $attributes->class(['form-select', 'is-invalid' => $errors->has($name)]) }}>
        @if ($placeholder)<option value="">{{ $placeholder }}</option>@endif
        @foreach ($options as $key => $text)
            <option value="{{ $key }}" @selected((string) $selected === (string) $key)>{{ $text }}</option>
        @endforeach
    </select>
    @error($name)<div class="invalid-feedback">{{ $message }}</div>@enderror
    @if ($hint)<div class="form-text">{{ $hint }}</div>@endif
</div>
