@extends('layouts.admin.main')
@php [$label, , $back, $cols] = $meta; @endphp
@section('title', 'Import '.$label)
@section('subtitle', 'Import')

@section('content')
<div class="app-content">
    <x-admin.page-header :title="'Import '.$label" subtitle="Upload a CSV file (Excel → Save as → CSV)." :back="route($back)">
        <x-admin.button variant="light" icon="download" :href="route('imports.sample', $type)">Download sample CSV</x-admin.button>
    </x-admin.page-header>
    <div class="app-form-grid">
        <div class="app-card app-form-card">
            <form method="POST" action="{{ route('imports.store', $type) }}" enctype="multipart/form-data">@csrf
                <label class="form-label">CSV file</label>
                <input type="file" name="file" accept=".csv,text/csv" class="form-control" required>
                <div class="form-text">First row = column headings. Max 5 MB.</div>
                <div class="app-form-actions"><x-admin.button icon="upload" class="btn-lg-app">Import</x-admin.button></div>
            </form>
            @if (session('importErrors'))
                <div class="alert alert-warning small mt-4 mb-0"><b>Skipped rows</b><ul class="mb-0 mt-1">@foreach (session('importErrors') as $e)<li>{{ $e }}</li>@endforeach</ul></div>
            @endif
        </div>
        <div class="app-aside">
            <x-admin.card title="Columns">
                <div class="d-flex flex-wrap gap-1">@foreach ($cols as $c)<code class="small px-2 py-1 rounded" style="background:var(--app-hover)">{{ $c }}</code>@endforeach</div>
                <p class="small text-body-secondary mt-3 mb-0">Only <b>name</b> is required. @if ($type === 'items')Category, brand and unit are created if missing; tax is matched by rate. Opening qty goes to the default warehouse.@endif</p>
            </x-admin.card>
        </div>
    </div>
</div>
@endsection
