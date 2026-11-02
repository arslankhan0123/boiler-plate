{{-- Master select with a quick "+ Add" link. Vars: name, label, type (attribute type), options, value, parentField? --}}
@php $type = $type ?? str_replace('_id', '', $name); @endphp
<div class="mb-3">
    <div class="d-flex justify-content-between align-items-center">
        <label for="f_{{ $name }}" class="form-label">{{ $label }} <span class="opt">(optional)</span></label>
        @can('inventory.manage_products')
            <button type="button" class="quick-add" data-quick data-field="{{ $name }}" data-label="{{ $label }}" data-url="{{ route('inventory.masters.store', $type) }}"><i class="bi bi-plus-lg"></i> Add</button>
        @endcan
    </div>
    <select name="{{ $name }}" id="f_{{ $name }}" class="form-select @error($name) is-invalid @enderror" @if (! empty($parentField)) data-parent-field="{{ $parentField }}" @endif>
        <option value="">—</option>
        @foreach ($options as $id => $n)<option value="{{ $id }}" @selected((string) $value === (string) $id)>{{ $n }}</option>@endforeach
    </select>
    @error($name)<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
