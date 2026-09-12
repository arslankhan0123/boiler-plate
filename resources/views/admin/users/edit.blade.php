@extends('layouts.admin.main')
@section('title', 'Edit User')

@section('breadcrumbTitle', 'Edit User')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Users</a></li>
    <li class="breadcrumb-item active">Edit User</li>
@endsection

@section('content')
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
            <div>
                <h1 class="users-page-title">Edit User</h1>
                <p class="users-page-sub">Update user account details</p>
            </div>
            <div>
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('users.index') }}">
                    <i class="bi bi-arrow-left me-1"></i>Back to Users
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <form action="{{ route('users.update', $user->id) }}" method="POST" id="usersForm" novalidate>
                    @csrf
                    @method('PUT')
                    <div class="row g-4">
                        <div class="col-12">
                            <label class="form-label">Avatar</label>
                            <div class="d-flex align-items-center gap-3">
                                <span class="avatar avatar-xl bg-light text-secondary rounded-circle d-flex align-items-center justify-content-center" style="width: 64px; height: 64px; font-size: 24px;">?</span>
                                <div>
                                    <label for="usersAvatarInput" class="btn btn-sm btn-outline-secondary"><i class="bi bi-cloud-upload me-1"></i>Upload avatar</label>
                                    <input id="usersAvatarInput" name="avatar" type="file" accept="image/*" class="visually-hidden">
                                    <p class="text-muted small mt-1 mb-0">PNG or JPG, up to 2MB. Optional.</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="usersFirst" class="form-label">First name</label>
                            <input id="usersFirst" name="first" type="text" class="form-control @error('first') is-invalid @enderror" value="{{ old('first', $user->first_name) }}" required>
                            @error('first')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="usersLast" class="form-label">Last name</label>
                            <input id="usersLast" name="last" type="text" class="form-control @error('last') is-invalid @enderror" value="{{ old('last', $user->last_name) }}" required>
                            @error('last')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-12">
                            <label for="usersEmail" class="form-label">Email</label>
                            <input id="usersEmail" name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-12">
                            <label for="usersPhone" class="form-label">Phone <span class="text-body-secondary fw-normal">(optional)</span></label>
                            <input id="usersPhone" name="phone" type="tel" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $user->phone) }}">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="usersRole" class="form-label">Role</label>
                            <select id="usersRole" name="role" class="form-select @error('role') is-invalid @enderror" required>
                                <option value="" disabled>Select Role</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->name }}" {{ (old('role') ?? $user->roles->first()?->name) == $role->name ? 'selected' : '' }}>{{ $role->name }}</option>
                                @endforeach
                            </select>
                            @error('role')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="usersDept" class="form-label">Department</label>
                            <select id="usersDept" name="dept" class="form-select" required>
                                <option selected>Engineering</option>
                                <option>Design</option>
                                <option>Product</option>
                                <option>Marketing</option>
                                <option>Sales</option>
                                <option>Support</option>
                                <option>Finance</option>
                                <option>Executive</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label for="usersTz" class="form-label">Timezone</label>
                            <select id="usersTz" name="tz" class="form-select">
                                <option selected>UTC</option>
                                <option>Asia/Kolkata</option>
                                <option>Asia/Tokyo</option>
                                <option>Asia/Dubai</option>
                                <option>Europe/London</option>
                                <option>America/New_York</option>
                            </select>
                        </div>
                        
                        <div class="col-md-12">
                            <label class="form-label d-block">Status</label>
                            <div class="btn-group" role="group" aria-label="Status">
                                <input type="radio" class="btn-check" name="status" id="usersStAct" value="active" checked>
                                <label class="btn btn-sm btn-outline-success" for="usersStAct">Active</label>
                                <input type="radio" class="btn-check" name="status" id="usersStPen" value="pending">
                                <label class="btn btn-sm btn-outline-warning" for="usersStPen">Pending</label>
                                <input type="radio" class="btn-check" name="status" id="usersStSus" value="suspended">
                                <label class="btn btn-sm btn-outline-danger" for="usersStSus">Suspended</label>
                            </div>
                        </div>

                        <div class="col-12 mt-4 pt-3 border-top">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Update user</button>
                            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
