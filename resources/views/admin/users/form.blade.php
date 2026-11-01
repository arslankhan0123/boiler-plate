@extends('layouts.admin.main')
@php
    $editing = $user->exists;
    $self = $editing && $user->id === auth()->id();
    $currentRole = old('role', $user->roles->first()?->name);
    $master = config('module_permissions.master_role');
@endphp
@section('title', $editing ? 'Edit User' : 'Create User')
@section('subtitle', $editing ? 'Update an existing user account' : 'Add a new user account')

@section('content')
<div class="app-content">
    <x-admin.page-header :back="route('users.index')" :title="$editing ? 'Edit User' : 'Create User'"
        :subtitle="$editing ? 'Update details for '.$user->name : 'Fill in the details to add a new user'">
        <x-admin.button variant="light" :href="route('users.index')" icon="list-ul">All Users</x-admin.button>
    </x-admin.page-header>

    <form method="POST" action="{{ $editing ? route('users.update', $user) : route('users.store') }}" class="app-form-grid">
        @csrf
        @if ($editing) @method('PUT') @endif

        {{-- Main form --}}
        <div class="app-card app-form-card">
            <x-admin.input name="name" label="Full Name" :value="$user->name" placeholder="e.g. Ali Khan" required />
            <x-admin.input name="email" type="email" label="Email Address" :value="$user->email" placeholder="e.g. ali@company.com" required
                hint="Used to sign in. Must be unique." />
            <x-admin.input name="phone" label="Phone Number" optional :value="$user->phone" placeholder="e.g. +92 300 0000000" />

            <div class="row g-4 mt-0">
                <div class="col-md-6">
                    <x-admin.input name="password" type="password" label="Password" :optional="$editing ? '(leave blank to keep)' : null"
                        placeholder="Minimum 8 characters" autocomplete="new-password" :required="! $editing" />
                </div>
                <div class="col-md-6">
                    <x-admin.input name="password_confirmation" type="password" label="Confirm Password"
                        placeholder="Re-enter password" autocomplete="new-password" />
                </div>
            </div>

            <div class="app-form-actions">
                <x-admin.button class="btn-lg-app" icon="check2">{{ $editing ? 'Save Changes' : 'Create User' }}</x-admin.button>
                <x-admin.button variant="light" class="btn-lg-app" :href="route('users.index')">Cancel</x-admin.button>
            </div>
        </div>

        {{-- Side --}}
        <div class="app-aside">
            <div class="app-card"><div class="app-card__body">
                <h3 class="app-aside-title">Role <span class="req text-danger">*</span></h3>
                @if ($self)
                    <input type="hidden" name="role" value="{{ $currentRole }}">
                @endif
                <div class="app-radio-list">
                    @forelse ($roles as $role)
                        <x-admin.radio-card name="role" :value="$role->name" :title="$role->name"
                            :desc="$role->name === $master ? 'Full access to everything' : $role->permissions->count().' permissions'"
                            :checked="$currentRole === $role->name" :disabled="$self"
                            :badge="$role->name === $master ? 'All' : null" tone="warning" />
                    @empty
                        <p class="text-body-secondary small mb-0">No roles yet. <a href="{{ route('roles.create') }}">Create one</a>.</p>
                    @endforelse
                </div>
                @error('role')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                @if ($self)<div class="form-text mt-2">You cannot change your own role.</div>@endif
            </div></div>

            <x-admin.tips :items="[
                'The role decides which sidebar tabs and actions the user can see.',
                'Use a strong password of at least 8 characters.',
                'Create a new role first if none of these fit.',
                'Deleting a user does not delete their role.',
            ]" />
        </div>
    </form>
</div>
@endsection
