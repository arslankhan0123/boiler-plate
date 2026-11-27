@extends('layouts.admin.main')
@section('title', 'Announcements')
@section('subtitle', 'HRM · Notice board')

@section('content')
<div class="app-content">
    <x-admin.page-header title="Announcements" subtitle="Company or department notices — shown on every employee's My Profile and the HR dashboard." />
    <div class="app-form-grid">
        <x-admin.card flush :title="'All announcements ('.$announcements->total().')'">
            <div class="table-responsive"><table class="table app-table table-hover">
                <thead><tr><th>Announcement</th><th>Audience</th><th>Expires</th><th class="text-end"></th></tr></thead>
                <tbody>
                @forelse ($announcements as $a)
                    <tr>
                        <td>@if ($a->is_pinned)<i class="bi bi-pin-angle-fill text-warning"></i>@endif <span class="fw-semibold">{{ $a->title }}</span>
                            <div class="small text-body-secondary">{{ \Illuminate\Support\Str::limit($a->body, 90) }}</div>
                            <div class="small text-body-secondary">{{ $a->author_name }} · {{ $a->created_at->format('d M Y') }}</div></td>
                        <td>{{ $a->department->name ?? 'Everyone' }}</td>
                        <td>@if ($a->expires_at)<x-admin.badge :tone="$a->expires_at->isPast() ? 'muted' : 'primary'">{{ $a->expires_at->format('d M Y') }}</x-admin.badge>@else — @endif</td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('hrm.announcements.index', ['edit' => $a->id]) }}" class="btn btn-action"><i class="bi bi-pencil-square"></i></a>
                            <x-admin.delete-button :action="route('hrm.announcements.destroy', $a)" confirm="Remove this announcement?" />
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4"><x-admin.empty icon="megaphone" text="No announcements yet." /></td></tr>
                @endforelse
                </tbody></table></div>
            @if ($announcements->hasPages())
                <x-slot:footer>{{ $announcements->links('pagination::bootstrap-5') }}</x-slot:footer>
            @endif
        </x-admin.card>
        <div class="app-aside">
            <div class="app-card"><div class="app-card__body">
                <h3 class="app-aside-title">{{ $editing ? 'Edit announcement' : 'New announcement' }}</h3>
                <form method="POST" action="{{ $editing ? route('hrm.announcements.update', $editing) : route('hrm.announcements.store') }}">@csrf @if ($editing) @method('PUT') @endif
                    <x-admin.input name="title" label="Title" :value="$editing?->title" required />
                    <div class="app-field"><label class="form-label">Message<span class="req">*</span></label>
                        <textarea name="body" rows="6" required @class(['form-control', 'is-invalid' => $errors->has('body')])>{{ old('body', $editing?->body) }}</textarea>
                        @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <x-admin.select name="department_id" label="Audience" optional :options="$departments" :value="$editing?->department_id" placeholder="Everyone" />
                    <x-admin.input name="expires_at" type="date" label="Show until" optional :value="$editing?->expires_at?->format('Y-m-d')" />
                    <label class="d-flex align-items-center gap-2 small mt-3"><input type="checkbox" class="form-check-input mt-0" name="is_pinned" value="1" @checked(old('is_pinned', $editing?->is_pinned))> Pin to top</label>
                    <div class="d-flex gap-2 mt-4">
                        <x-admin.button icon="send">{{ $editing ? 'Save' : 'Publish' }}</x-admin.button>
                        @if ($editing)<x-admin.button variant="light" :href="route('hrm.announcements.index')">Cancel</x-admin.button>@endif
                    </div>
                </form>
            </div></div>
        </div>
    </div>
</div>
@endsection
