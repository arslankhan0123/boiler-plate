@props(['name', 'label', 'placeholder' => '••••••••', 'autocomplete' => 'current-password'])
<div class="app-field">
    <label for="f_{{ $name }}" class="form-label">{{ $label }}<span class="req">*</span></label>
    <div class="auth-pw">
        <input id="f_{{ $name }}" type="password" name="{{ $name }}" required autocomplete="{{ $autocomplete }}" placeholder="{{ $placeholder }}"
               @class(['form-control', 'is-invalid' => $errors->has($name)])>
        <button type="button" class="toggle" data-toggle-password="f_{{ $name }}" aria-label="Show password"><i class="bi bi-eye"></i></button>
    </div>
    @error($name)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
</div>
