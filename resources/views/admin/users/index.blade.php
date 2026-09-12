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
                <button class="btn btn-primary btn-sm" type="button" data-users-add>
                    <i class="bi bi-plus-lg me-1"></i>Add User
                </button>
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
                    <tbody data-users-skeleton>
                        <tr>
                            <td class="users-td-check"><span class="users-skel-avatar"></span></td>
                            <td><span class="users-skel-bar users-skel-bar--md"></span><br><span
                                    class="users-skel-bar users-skel-bar--sm mt-2"></span></td>
                            <td><span class="users-skel-bar users-skel-bar--sm"></span></td>
                            <td><span class="users-skel-bar users-skel-bar--sm"></span></td>
                            <td><span class="users-skel-bar users-skel-bar--sm"></span></td>
                            <td><span class="users-skel-bar users-skel-bar--md"></span></td>
                            <td><span class="users-skel-bar users-skel-bar--sm"></span></td>
                        </tr>
                        <tr>
                            <td class="users-td-check"><span class="users-skel-avatar"></span></td>
                            <td><span class="users-skel-bar users-skel-bar--lg"></span><br><span
                                    class="users-skel-bar users-skel-bar--md mt-2"></span></td>
                            <td><span class="users-skel-bar users-skel-bar--sm"></span></td>
                            <td><span class="users-skel-bar users-skel-bar--sm"></span></td>
                            <td><span class="users-skel-bar users-skel-bar--sm"></span></td>
                            <td><span class="users-skel-bar users-skel-bar--md"></span></td>
                            <td><span class="users-skel-bar users-skel-bar--sm"></span></td>
                        </tr>
                        <tr>
                            <td class="users-td-check"><span class="users-skel-avatar"></span></td>
                            <td><span class="users-skel-bar users-skel-bar--md"></span><br><span
                                    class="users-skel-bar users-skel-bar--sm mt-2"></span></td>
                            <td><span class="users-skel-bar users-skel-bar--sm"></span></td>
                            <td><span class="users-skel-bar users-skel-bar--sm"></span></td>
                            <td><span class="users-skel-bar users-skel-bar--sm"></span></td>
                            <td><span class="users-skel-bar users-skel-bar--md"></span></td>
                            <td><span class="users-skel-bar users-skel-bar--sm"></span></td>
                        </tr>
                        <tr>
                            <td class="users-td-check"><span class="users-skel-avatar"></span></td>
                            <td><span class="users-skel-bar users-skel-bar--lg"></span><br><span
                                    class="users-skel-bar users-skel-bar--md mt-2"></span></td>
                            <td><span class="users-skel-bar users-skel-bar--sm"></span></td>
                            <td><span class="users-skel-bar users-skel-bar--sm"></span></td>
                            <td><span class="users-skel-bar users-skel-bar--sm"></span></td>
                            <td><span class="users-skel-bar users-skel-bar--md"></span></td>
                            <td><span class="users-skel-bar users-skel-bar--sm"></span></td>
                        </tr>
                        <tr>
                            <td class="users-td-check"><span class="users-skel-avatar"></span></td>
                            <td><span class="users-skel-bar users-skel-bar--md"></span><br><span
                                    class="users-skel-bar users-skel-bar--sm mt-2"></span></td>
                            <td><span class="users-skel-bar users-skel-bar--sm"></span></td>
                            <td><span class="users-skel-bar users-skel-bar--sm"></span></td>
                            <td><span class="users-skel-bar users-skel-bar--sm"></span></td>
                            <td><span class="users-skel-bar users-skel-bar--md"></span></td>
                            <td><span class="users-skel-bar users-skel-bar--sm"></span></td>
                        </tr>
                    </tbody>
                    <tbody data-users-tbody>
                        <!-- rows injected by JS -->
                    </tbody>
                </table>

                <!-- Empty state -->
                <div class="users-empty" data-users-empty>
                    <div class="users-empty__illus"><i class="bi bi-people"></i></div>
                    <h6>No users match your filters</h6>
                    <p>Try adjusting your search or clearing filters to see all users.</p>
                    <button class="btn btn-outline-primary btn-sm" type="button" data-users-reset><i
                            class="bi bi-arrow-counterclockwise me-1"></i>Clear filters</button>
                </div>
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
@endsection