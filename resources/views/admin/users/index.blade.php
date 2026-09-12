@extends('layouts.admin.main')
@section('title', 'Admin Dashboard')

@section('breadcrumbTitle', 'Users List')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Users</a></li>
    <li class="breadcrumb-item active">List</li>
@endsection

@section('content')
    <div class="container-fluid px-3 px-lg-4 py-4">

        <!-- Page header -->
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
            <div>
                <h1 class="users-page-title">Users</h1>
                <p class="users-page-sub">Manage user accounts, roles, and permissions</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-outline-secondary btn-sm" type="button" data-users-import>
                    <i class="bi bi-upload me-1"></i>Import
                </button>
                <input type="file" class="visually-hidden" accept=".csv" data-users-import-input aria-hidden="true"
                    tabindex="-1">
                <div class="dropdown">
                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown"
                        aria-expanded="false">
                        <i class="bi bi-download me-1"></i>Export
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#" data-users-export="CSV"><i
                                    class="bi bi-filetype-csv me-2"></i>CSV</a></li>
                        <li><a class="dropdown-item" href="#" data-users-export="Excel"><i
                                    class="bi bi-file-earmark-spreadsheet me-2"></i>Excel</a></li>
                        <li><a class="dropdown-item" href="#" data-users-export="JSON"><i
                                    class="bi bi-filetype-json me-2"></i>JSON</a></li>
                        <li><a class="dropdown-item" href="#" data-users-export="PDF"><i
                                    class="bi bi-filetype-pdf me-2"></i>PDF</a></li>
                    </ul>
                </div>
                <a class="btn btn-primary btn-sm" href="{{ route('users.create') }}">
                    <i class="bi bi-plus-lg me-1"></i>Add User
                </a>
            </div>
        </div>

        <!-- Stats strip -->
        <div class="row g-3 mb-3">
            <div class="col-6 col-xl-3">
                <div class="users-stat users-stat--indigo">
                    <span class="users-stat__icon"><i class="bi bi-people-fill"></i></span>
                    <div>
                        <p class="users-stat__label">Total Users</p>
                        <p class="users-stat__value" data-users-stat="total">0</p>
                        <span class="users-stat__delta"><i class="bi bi-arrow-up-short"></i>4 this week</span>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="users-stat users-stat--green">
                    <span class="users-stat__icon"><i class="bi bi-check-circle-fill"></i></span>
                    <div>
                        <p class="users-stat__label">Active</p>
                        <p class="users-stat__value" data-users-stat="active">0</p>
                        <span class="users-stat__delta"><i class="bi bi-dot"></i>Online right now</span>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="users-stat users-stat--amber">
                    <span class="users-stat__icon"><i class="bi bi-hourglass-split"></i></span>
                    <div>
                        <p class="users-stat__label">Pending</p>
                        <p class="users-stat__value" data-users-stat="pending">0</p>
                        <span class="users-stat__delta"><i class="bi bi-envelope"></i>Awaiting verification</span>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="users-stat users-stat--red">
                    <span class="users-stat__icon"><i class="bi bi-slash-circle-fill"></i></span>
                    <div>
                        <p class="users-stat__label">Suspended</p>
                        <p class="users-stat__value" data-users-stat="suspended">0</p>
                        <span class="users-stat__delta"><i class="bi bi-shield-exclamation"></i>Review required</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="users-toolbar" role="search">
            <div class="users-toolbar__search">
                <i class="bi bi-search"></i>
                <input type="search" class="form-control" placeholder="Search by name or email…" aria-label="Search users"
                    data-users-search>
            </div>
            <select class="form-select form-select-sm" aria-label="Filter by role" data-users-filter="role">
                <option value="all">All roles</option>
                <option value="admin">Admin</option>
                <option value="editor">Editor</option>
                <option value="viewer">Viewer</option>
                <option value="guest">Guest</option>
            </select>
            <select class="form-select form-select-sm" aria-label="Filter by status" data-users-filter="status">
                <option value="all">All statuses</option>
                <option value="active">Active</option>
                <option value="pending">Pending</option>
                <option value="suspended">Suspended</option>
            </select>
            <select class="form-select form-select-sm" aria-label="Filter by join date" data-users-filter="date">
                <option value="all">All time</option>
                <option value="today">Today</option>
                <option value="7d">Last 7 days</option>
                <option value="30d">Last 30 days</option>
            </select>
            <button type="button" class="users-toolbar__reset ms-auto" data-users-reset>
                <i class="bi bi-arrow-counterclockwise me-1"></i>Reset filters
            </button>
        </div>

        <!-- Table card -->
        <div class="users-card">

            <!-- Bulk action bar -->
            <div class="users-bulkbar" data-users-bulk>
                <span class="users-bulkbar__count"><span data-users-bulk-count>0</span> selected</span>
                <div class="users-bulkbar__actions">
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary" type="button" data-bs-toggle="dropdown"
                            aria-expanded="false">
                            <i class="bi bi-shield me-1"></i>Change role
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" data-users-bulk-action="role:admin"><span
                                        class="users-role users-role--admin me-2">Admin</span>Set as Admin</a></li>
                            <li><a class="dropdown-item" href="#" data-users-bulk-action="role:editor"><span
                                        class="users-role users-role--editor me-2">Editor</span>Set as Editor</a></li>
                            <li><a class="dropdown-item" href="#" data-users-bulk-action="role:viewer"><span
                                        class="users-role users-role--viewer me-2">Viewer</span>Set as Viewer</a></li>
                            <li><a class="dropdown-item" href="#" data-users-bulk-action="role:guest"><span
                                        class="users-role users-role--guest me-2">Guest</span>Set as Guest</a></li>
                        </ul>
                    </div>
                    <button class="btn btn-outline-secondary" type="button" data-users-bulk-action="archive"><i
                            class="bi bi-archive me-1"></i>Archive</button>
                    <button class="btn btn-outline-secondary" type="button" data-users-bulk-action="export"><i
                            class="bi bi-download me-1"></i>Export selected</button>
                    <button class="btn btn-outline-danger" type="button" data-users-bulk-action="delete"><i
                            class="bi bi-trash me-1"></i>Delete</button>
                    <button class="btn btn-link text-decoration-none" type="button" data-users-bulk-action="clear">Clear
                        selection</button>
                </div>
            </div>

            <!-- Table -->
            <div class="users-table-wrap">
                <table class="users-table align-middle mb-0" aria-label="Users list">
                    <thead>
                        <tr>
                            <th class="users-th-check">
                                <div class="form-check m-0">
                                    <input class="form-check-input" type="checkbox"
                                        aria-label="Select all users on this page" data-users-selectall>
                                </div>
                            </th>
                            <th class="users-th-sort" data-users-sort="name">User <span class="users-sort-icon"><i
                                        class="bi bi-arrow-down-up"></i></span></th>
                            <th class="users-th-sort" data-users-sort="role">Role <span class="users-sort-icon"><i
                                        class="bi bi-arrow-down-up"></i></span></th>
                            <th class="users-th-sort" data-users-sort="status">Status <span class="users-sort-icon"><i
                                        class="bi bi-arrow-down-up"></i></span></th>
                            <th class="users-th-sort" data-users-sort="lastActive">Last Active <span
                                    class="users-sort-icon"><i class="bi bi-arrow-down-up"></i></span></th>
                            <th class="users-th-sort" data-users-sort="joined">Joined <span class="users-sort-icon"><i
                                        class="bi bi-arrow-down-up"></i></span></th>
                            <th class="users-th-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td class="users-td-check">
                                    <div class="form-check m-0">
                                        <input class="form-check-input" type="checkbox" name="user_ids[]"
                                            value="{{ $user->id }}">
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <span
                                            class="avatar avatar-md bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold"
                                            style="width: 40px; height: 40px;">
                                            {{ mb_substr($user->name, 0, 1) }}
                                        </span>
                                        <div>
                                            <div class="fw-semibold text-body">{{ $user->name }}</div>
                                            <div class="text-body-secondary small">{{ $user->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($user->roles->count() > 0)
                                        <span
                                            class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">{{ $user->roles->first()->name }}</span>
                                    @else
                                        <span class="text-muted small">No Role</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle"><i
                                            class="bi bi-circle-fill me-1" style="font-size: 0.4rem;"></i>Active</span>
                                </td>
                                <td class="text-body-secondary small">
                                    Just now
                                </td>
                                <td class="text-body-secondary small">
                                    {{ $user->created_at ? $user->created_at->format('M d, Y') : '-' }}
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-icon btn-ghost-secondary border-0" type="button"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                            <li><a class="dropdown-item" href="{{ route('users.edit', $user->id) }}"><i
                                                        class="bi bi-pencil me-2 text-body-secondary"></i>Edit</a></li>
                                            <li>
                                                <hr class="dropdown-divider">
                                            </li>
                                            <li>
                                                <form action="{{ route('users.destroy', $user->id) }}" method="POST"
                                                    class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger"
                                                        onclick="return confirm('Are you sure you want to delete this user?')">
                                                        <i class="bi bi-trash me-2"></i>Delete
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="users-empty">
                                        <div class="users-empty__illus"><i class="bi bi-people"></i></div>
                                        <h6 class="mt-3">No users found</h6>
                                        <p class="text-muted">There are no users in the database.</p>
                                        <a href="{{ route('users.create') }}" class="btn btn-outline-primary btn-sm mt-2"><i
                                                class="bi bi-plus me-1"></i>Add first user</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
            </div>

            <!-- Table footer -->
            <div class="users-footer">
                <div class="users-footer__meta" data-users-meta>Showing 0–0 of 0 users</div>
                <div class="users-footer__perpage">
                    <label for="usersPerPage" class="mb-0">Rows per page</label>
                    <select id="usersPerPage" class="form-select form-select-sm" data-users-perpage
                        aria-label="Rows per page">
                        <option value="10" selected>10</option>
                        <option value="20">20</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
                <nav aria-label="Users pagination">
                    <ul class="pagination users-pager mb-0" data-users-pager></ul>
                </nav>
            </div>
        </div>

    </div>
    @push('modals')
        <!-- ================== DRAWER (create / edit) ================== -->
        <div class="offcanvas offcanvas-end users-drawer" tabindex="-1" id="usersDrawer" aria-labelledby="usersDrawerTitle">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title" id="usersDrawerTitle" data-users-drawer-title>Add User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <form class="offcanvas-body" id="usersForm" novalidate data-users-form>
                <div class="users-avatar-upload">
                    <span class="users-avatar-upload__preview" data-users-preview>?</span>
                    <div class="users-avatar-upload__body">
                        <label for="usersAvatarInput" class="btn btn-sm btn-outline-secondary"><i
                                class="bi bi-cloud-upload me-1"></i>Upload avatar</label>
                        <input id="usersAvatarInput" name="avatar" type="file" accept="image/*" class="visually-hidden"
                            data-users-avatar-input>
                        <p class="users-avatar-upload__hint">PNG or JPG, up to 2MB. Optional.</p>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-6">
                        <label for="usersFirst" class="form-label">First name</label>
                        <input id="usersFirst" name="first" type="text" class="form-control" required>
                        <div class="invalid-feedback">Required.</div>
                    </div>
                    <div class="col-6">
                        <label for="usersLast" class="form-label">Last name</label>
                        <input id="usersLast" name="last" type="text" class="form-control" required>
                        <div class="invalid-feedback">Required.</div>
                    </div>
                    <div class="col-12">
                        <label for="usersEmail" class="form-label">Email</label>
                        <input id="usersEmail" name="email" type="email" class="form-control" required>
                        <div class="invalid-feedback">Enter a valid email.</div>
                    </div>
                    <div class="col-12">
                        <label for="usersPhone" class="form-label">Phone <span
                                class="text-body-secondary fw-normal">(optional)</span></label>
                        <input id="usersPhone" name="phone" type="tel" class="form-control">
                    </div>
                    <div class="col-6">
                        <label for="usersRole" class="form-label">Role</label>
                        <select id="usersRole" name="role" class="form-select" required>
                            <option value="admin">Admin</option>
                            <option value="editor" selected>Editor</option>
                            <option value="viewer">Viewer</option>
                            <option value="guest">Guest</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label for="usersDept" class="form-label">Department</label>
                        <select id="usersDept" name="dept" class="form-select" required>
                            <option>Engineering</option>
                            <option>Design</option>
                            <option>Product</option>
                            <option>Marketing</option>
                            <option>Sales</option>
                            <option>Support</option>
                            <option>Finance</option>
                            <option>Executive</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label for="usersTz" class="form-label">Timezone</label>
                        <select id="usersTz" name="tz" class="form-select">
                            <option>UTC</option>
                            <option>Asia/Kolkata</option>
                            <option>Asia/Tokyo</option>
                            <option>Asia/Seoul</option>
                            <option>Asia/Dubai</option>
                            <option>Asia/Riyadh</option>
                            <option>Europe/London</option>
                            <option>Europe/Berlin</option>
                            <option>Europe/Madrid</option>
                            <option>Europe/Rome</option>
                            <option>Europe/Moscow</option>
                            <option>Europe/Dublin</option>
                            <option>America/New_York</option>
                            <option>America/Los_Angeles</option>
                            <option>America/Toronto</option>
                            <option>America/Sao_Paulo</option>
                            <option>Australia/Sydney</option>
                            <option>Africa/Lagos</option>
                        </select>
                    </div>

                    <div class="col-12">
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

                    <div class="col-12">
                        <label class="form-label">Permissions</label>
                        <div class="users-perm-grid">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="perms" value="view" id="permView" checked>
                                <label class="form-check-label" for="permView">View</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="perms" value="edit" id="permEdit">
                                <label class="form-check-label" for="permEdit">Edit</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="perms" value="delete" id="permDel">
                                <label class="form-check-label" for="permDel">Delete</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="perms" value="manage" id="permManage">
                                <label class="form-check-label" for="permManage">Manage users</label>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="welcome" id="usersWelcome" checked>
                            <label class="form-check-label" for="usersWelcome">Send welcome email with login
                                instructions</label>
                        </div>
                    </div>
                </div>
            </form>
            <div class="users-drawer__footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="offcanvas">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm" form="usersForm" data-users-save><i
                        class="bi bi-check2 me-1"></i>Save user</button>
            </div>
        </div>

        <!-- ================== VIEW MODAL ================== -->
        <div class="modal fade" id="usersViewModal" tabindex="-1" aria-labelledby="usersViewTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="usersViewTitle">User details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="users-view-header">
                            <span class="avatar avatar-lg" data-users-view="avatar">--</span>
                            <div class="flex-grow-1 min-w-0">
                                <h5 data-users-view="name">—</h5>
                                <p data-users-view="sub">—</p>
                            </div>
                            <div class="d-flex flex-column align-items-end gap-1">
                                <span data-users-view="role"></span>
                                <span data-users-view="status"></span>
                            </div>
                        </div>

                        <dl class="users-view-grid">
                            <div>
                                <dt>Phone</dt>
                                <dd data-users-view="phone">—</dd>
                            </div>
                            <div>
                                <dt>Timezone</dt>
                                <dd data-users-view="tz">—</dd>
                            </div>
                            <div>
                                <dt>Joined</dt>
                                <dd data-users-view="joined">—</dd>
                            </div>
                            <div>
                                <dt>Last Active</dt>
                                <dd data-users-view="last">—</dd>
                            </div>
                            <div class="users-view-grid__full">
                                <dt>Permissions</dt>
                                <dd data-users-view="perms">—</dd>
                            </div>
                        </dl>

                        <h6 class="users-view-section-title">Recent activity</h6>
                        <ul class="users-view-activity" data-users-view="activity"></ul>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary btn-sm" data-users-view-edit><i
                                class="bi bi-pencil me-1"></i>Edit</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================== DELETE CONFIRM MODAL ================== -->
        <div class="modal fade" id="usersDeleteModal" tabindex="-1" aria-labelledby="usersDelTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="usersDelTitle"><i
                                class="bi bi-exclamation-triangle text-danger me-2"></i>Delete user</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="users-del-alert">
                            <i class="bi bi-exclamation-octagon-fill"></i>
                            <div>
                                <strong>This action is permanent.</strong>
                                <p class="mb-0" data-users-del-msg>You are about to delete this user.</p>
                            </div>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="usersDelConfirm" data-users-del-confirm>
                            <label class="form-check-label" for="usersDelConfirm">I understand this cannot be undone</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger btn-sm" disabled data-users-del-go><i
                                class="bi bi-trash me-1"></i>Delete forever</button>
                    </div>
                </div>
            </div>
        </div>
    @endpush


    @push('scripts')
        <script src="{{asset('admin/assets/js/pages/users.js')}}" defer></script>
    @endpush

@endsection