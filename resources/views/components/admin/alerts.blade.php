@if (session('success'))
    <div class="app-alert app-alert--success" role="alert"><i class="bi bi-check-circle-fill"></i>{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="app-alert app-alert--danger" role="alert"><i class="bi bi-exclamation-circle-fill"></i>{{ session('error') }}</div>
@endif
