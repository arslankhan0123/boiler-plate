@props(['action', 'confirm' => 'Are you sure you want to delete this?'])
<form action="{{ $action }}" method="POST" class="d-inline" onsubmit="return confirm(@js($confirm))">
    @csrf @method('DELETE')
    <button type="submit" class="btn btn-action btn-action--danger" title="Delete"><i class="bi bi-trash3"></i></button>
</form>
