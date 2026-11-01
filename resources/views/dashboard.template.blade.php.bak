@extends('layouts.admin.main')
@section('title', 'Admin Dashboard')

@section('breadcrumbTitle', 'Dashboard Overview')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Operational Hub</li>
@endsection

@section('content')
    <div class="container-fluid px-3 px-lg-4 py-4">

        <!-- Page header -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="h3 mb-1">Welcome back, {{ explode(' ', auth()->user()->name ?? 'User')[0] }} 👋</h1>
                <p class="text-body-secondary mb-0">Here's what's happening with your business today.</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary btn-sm"><i class="bi bi-download me-1"></i>Export</button>
                <button class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Create Report</button>
            </div>
        </div>

        <!-- ================== ROW 1 ================== -->
        <div class="row g-3 g-xl-4 mb-3 mb-xl-4">
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card orchid-card orchid-stat-card h-100 overflow-hidden">
                    <div class="card-body pb-2">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <p class="text-body-secondary mb-1 small">Total Contacts</p>
                                <h3 class="mb-0 fw-bold">12,480</h3>
                            </div>
                            <span class="orchid-stat-card__icon bg-primary-subtle text-primary"><i
                                    class="bi bi-people"></i></span>
                        </div>
                        <span class="badge bg-success-subtle text-success"><i class="bi bi-arrow-up"></i> 12.4%
                            vs last month</span>
                    </div>
                    <div class="orchid-stat-card__spark">
                        <canvas data-orchid-chart="sparkContacts"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card orchid-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0">Lead Analytics</h6>
                            <span class="badge bg-info-subtle text-info">Weekly</span>
                        </div>
                        <div class="orchid-chart-wrap orchid-chart-wrap--sm">
                            <canvas data-orchid-chart="leadAnalytics"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card orchid-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0">Traffic Sources</h6>
                            <button class="btn btn-sm btn-icon" type="button" aria-label="More"><i
                                    class="bi bi-three-dots"></i></button>
                        </div>
                        <div class="orchid-chart-wrap orchid-chart-wrap--sm">
                            <canvas data-orchid-chart="trafficSources"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-3">
                <div class="card orchid-card orchid-earnings-card h-100 text-white">
                    <div class="card-body position-relative pb-0">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="mb-1 opacity-75 small">Total Earnings</p>
                                <h3 class="fw-bold mb-1">$84,120.50</h3>
                                <span class="badge bg-white text-primary"><i class="bi bi-arrow-up"></i>
                                    8.24%</span>
                            </div>
                            <span class="orchid-stat-card__icon bg-white bg-opacity-25 text-white"><i
                                    class="bi bi-currency-dollar"></i></span>
                        </div>
                    </div>
                    <div class="orchid-earnings-card__chart">
                        <canvas data-orchid-chart="earnings"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================== ROW 2 ================== -->
        <div class="row g-3 g-xl-4 mb-3 mb-xl-4">
            <div class="col-12 col-lg-6 col-xxl-3">
                <div class="card orchid-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">Tasks Overview</h6>
                            <a href="#" class="small">View all</a>
                        </div>
                        <ul class="orchid-task-list list-unstyled mb-0">
                            <li class="orchid-task-list__item">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="task1" checked>
                                    <label class="form-check-label text-decoration-line-through text-body-secondary"
                                        for="task1">Prepare launch checklist</label>
                                </div>
                                <span class="badge bg-success-subtle text-success">Done</span>
                            </li>
                            <li class="orchid-task-list__item">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="task2">
                                    <label class="form-check-label" for="task2">Design system audit</label>
                                </div>
                                <span class="badge bg-warning-subtle text-warning">Today</span>
                            </li>
                            <li class="orchid-task-list__item">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="task3">
                                    <label class="form-check-label" for="task3">Review PR #482</label>
                                </div>
                                <span class="badge bg-primary-subtle text-primary">2h</span>
                            </li>
                            <li class="orchid-task-list__item">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="task4">
                                    <label class="form-check-label" for="task4">Sync with sales team</label>
                                </div>
                                <span class="badge bg-info-subtle text-info">Tomorrow</span>
                            </li>
                            <li class="orchid-task-list__item">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="task5">
                                    <label class="form-check-label" for="task5">Publish weekly
                                        newsletter</label>
                                </div>
                                <span class="badge bg-danger-subtle text-danger">Overdue</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-6 col-xxl-3">
                <div class="card orchid-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">Active Deals</h6>
                            <span class="badge bg-primary-subtle text-primary">48 total</span>
                        </div>
                        <div class="orchid-chart-wrap orchid-chart-wrap--md">
                            <canvas data-orchid-chart="activeDeals"></canvas>
                        </div>
                        <div class="d-flex justify-content-between mt-3 small">
                            <div><span class="orchid-dot bg-primary"></span> Won <strong class="ms-1">28</strong></div>
                            <div><span class="orchid-dot bg-warning"></span> Pending <strong class="ms-1">14</strong></div>
                            <div><span class="orchid-dot bg-danger"></span> Lost <strong class="ms-1">6</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xxl-4">
                <div class="card orchid-card h-100">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                            <div>
                                <h6 class="mb-0">Revenue Analytics</h6>
                                <small class="text-body-secondary">Monthly performance</small>
                            </div>
                            <div class="btn-group btn-group-sm" role="group" aria-label="Revenue range">
                                <input type="radio" class="btn-check" name="revRange" id="revW" autocomplete="off">
                                <label class="btn btn-outline-secondary" for="revW">W</label>
                                <input type="radio" class="btn-check" name="revRange" id="revM" autocomplete="off" checked>
                                <label class="btn btn-outline-secondary" for="revM">M</label>
                                <input type="radio" class="btn-check" name="revRange" id="revY" autocomplete="off">
                                <label class="btn btn-outline-secondary" for="revY">Y</label>
                            </div>
                        </div>
                        <div class="orchid-chart-wrap orchid-chart-wrap--lg">
                            <canvas data-orchid-chart="revenue"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xxl-2">
                <div class="card orchid-card h-100">
                    <div class="card-body d-flex flex-column">
                        <h6 class="mb-1">Retention Rate</h6>
                        <small class="text-body-secondary mb-2">Last 30 days</small>
                        <div class="orchid-chart-wrap orchid-chart-wrap--radial flex-grow-1">
                            <canvas data-orchid-chart="retention"></canvas>
                        </div>
                        <div class="text-center mt-2">
                            <span class="badge bg-success-subtle text-success"><i class="bi bi-arrow-up"></i>
                                2.4% vs last month</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================== ROW 3 ================== -->
        <div class="row g-3 g-xl-4 mb-3 mb-xl-4">
            <div class="col-12 col-lg-5">
                <div class="card orchid-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">Upcoming Meetings</h6>
                            <a href="#" class="small">Calendar</a>
                        </div>
                        <ul class="orchid-meeting-list list-unstyled mb-0">
                            <li class="orchid-meeting-list__item">
                                <div class="orchid-meeting-list__date">
                                    <span class="orchid-meeting-list__day">14</span>
                                    <span class="orchid-meeting-list__month">JAN</span>
                                </div>
                                <div class="flex-grow-1">
                                    <p class="mb-0 fw-semibold">Product Roadmap Q2</p>
                                    <small class="text-body-secondary"><i class="bi bi-clock me-1"></i>10:00 –
                                        11:00 AM</small>
                                </div>
                                <div class="avatar-group">
                                    <span class="avatar avatar-xs bg-primary text-white">AK</span>
                                    <span class="avatar avatar-xs bg-info text-white">JD</span>
                                    <span class="avatar avatar-xs bg-warning text-white">+3</span>
                                </div>
                            </li>
                            <li class="orchid-meeting-list__item">
                                <div class="orchid-meeting-list__date">
                                    <span class="orchid-meeting-list__day">14</span>
                                    <span class="orchid-meeting-list__month">JAN</span>
                                </div>
                                <div class="flex-grow-1">
                                    <p class="mb-0 fw-semibold">Client Onboarding – Nova</p>
                                    <small class="text-body-secondary"><i class="bi bi-clock me-1"></i>2:00 –
                                        2:45 PM</small>
                                </div>
                                <div class="avatar-group">
                                    <span class="avatar avatar-xs bg-success text-white">SM</span>
                                    <span class="avatar avatar-xs bg-danger text-white">EL</span>
                                </div>
                            </li>
                            <li class="orchid-meeting-list__item">
                                <div class="orchid-meeting-list__date">
                                    <span class="orchid-meeting-list__day">15</span>
                                    <span class="orchid-meeting-list__month">JAN</span>
                                </div>
                                <div class="flex-grow-1">
                                    <p class="mb-0 fw-semibold">Design Review</p>
                                    <small class="text-body-secondary"><i class="bi bi-clock me-1"></i>9:30 –
                                        10:30 AM</small>
                                </div>
                                <div class="avatar-group">
                                    <span class="avatar avatar-xs bg-primary text-white">AK</span>
                                    <span class="avatar avatar-xs bg-warning text-white">+2</span>
                                </div>
                            </li>
                            <li class="orchid-meeting-list__item">
                                <div class="orchid-meeting-list__date">
                                    <span class="orchid-meeting-list__day">16</span>
                                    <span class="orchid-meeting-list__month">JAN</span>
                                </div>
                                <div class="flex-grow-1">
                                    <p class="mb-0 fw-semibold">Investor Sync</p>
                                    <small class="text-body-secondary"><i class="bi bi-clock me-1"></i>4:00 –
                                        5:00 PM</small>
                                </div>
                                <div class="avatar-group">
                                    <span class="avatar avatar-xs bg-info text-white">RG</span>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-7">
                <div class="card orchid-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h6 class="mb-0">Deals Overview</h6>
                                <small class="text-body-secondary">Stacked by stage</small>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-icon" type="button" data-bs-toggle="dropdown"
                                    aria-expanded="false" aria-label="Deals menu">
                                    <i class="bi bi-three-dots"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="#">Refresh</a></li>
                                    <li><a class="dropdown-item" href="#">Export CSV</a></li>
                                    <li><a class="dropdown-item" href="#">Settings</a></li>
                                </ul>
                            </div>
                        </div>
                        <div class="orchid-chart-wrap orchid-chart-wrap--lg">
                            <canvas data-orchid-chart="dealsOverview"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================== ROW 4 ================== -->
        <div class="row g-3 g-xl-4 mb-3 mb-xl-4">
            <div class="col-12">
                <div class="card orchid-card">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                            <div>
                                <h6 class="mb-0">Sales Pipeline</h6>
                                <small class="text-body-secondary">Progress across stages · $284,500
                                    total</small>
                            </div>
                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-plus-lg me-1"></i>Add
                                Stage</button>
                        </div>

                        <div class="row g-3">
                            <div class="col-6 col-md-4 col-xl-2">
                                <div class="orchid-pipeline">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="fw-medium small">Leads</span>
                                        <small class="text-body-secondary">$48k</small>
                                    </div>
                                    <div class="progress orchid-pipeline__bar" role="progressbar" aria-label="Leads"
                                        aria-valuenow="85" aria-valuemin="0" aria-valuemax="100">
                                        <div class="progress-bar bg-primary" data-orchid-progress="85"></div>
                                    </div>
                                    <small class="text-body-secondary">142 deals</small>
                                </div>
                            </div>
                            <div class="col-6 col-md-4 col-xl-2">
                                <div class="orchid-pipeline">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="fw-medium small">Qualified</span>
                                        <small class="text-body-secondary">$62k</small>
                                    </div>
                                    <div class="progress orchid-pipeline__bar" role="progressbar" aria-label="Qualified"
                                        aria-valuenow="70" aria-valuemin="0" aria-valuemax="100">
                                        <div class="progress-bar bg-info" data-orchid-progress="70"></div>
                                    </div>
                                    <small class="text-body-secondary">98 deals</small>
                                </div>
                            </div>
                            <div class="col-6 col-md-4 col-xl-2">
                                <div class="orchid-pipeline">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="fw-medium small">Proposal</span>
                                        <small class="text-body-secondary">$54k</small>
                                    </div>
                                    <div class="progress orchid-pipeline__bar" role="progressbar" aria-label="Proposal"
                                        aria-valuenow="60" aria-valuemin="0" aria-valuemax="100">
                                        <div class="progress-bar bg-warning" data-orchid-progress="60"></div>
                                    </div>
                                    <small class="text-body-secondary">64 deals</small>
                                </div>
                            </div>
                            <div class="col-6 col-md-4 col-xl-2">
                                <div class="orchid-pipeline">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="fw-medium small">Negotiation</span>
                                        <small class="text-body-secondary">$46k</small>
                                    </div>
                                    <div class="progress orchid-pipeline__bar" role="progressbar" aria-label="Negotiation"
                                        aria-valuenow="45" aria-valuemin="0" aria-valuemax="100">
                                        <div class="progress-bar bg-purple" data-orchid-progress="45"></div>
                                    </div>
                                    <small class="text-body-secondary">32 deals</small>
                                </div>
                            </div>
                            <div class="col-6 col-md-4 col-xl-2">
                                <div class="orchid-pipeline">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="fw-medium small">Won</span>
                                        <small class="text-body-secondary">$62k</small>
                                    </div>
                                    <div class="progress orchid-pipeline__bar" role="progressbar" aria-label="Won"
                                        aria-valuenow="92" aria-valuemin="0" aria-valuemax="100">
                                        <div class="progress-bar bg-success" data-orchid-progress="92"></div>
                                    </div>
                                    <small class="text-body-secondary">48 deals</small>
                                </div>
                            </div>
                            <div class="col-6 col-md-4 col-xl-2">
                                <div class="orchid-pipeline">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="fw-medium small">Lost</span>
                                        <small class="text-body-secondary">$12k</small>
                                    </div>
                                    <div class="progress orchid-pipeline__bar" role="progressbar" aria-label="Lost"
                                        aria-valuenow="18" aria-valuemin="0" aria-valuemax="100">
                                        <div class="progress-bar bg-danger" data-orchid-progress="18"></div>
                                    </div>
                                    <small class="text-body-secondary">14 deals</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================== ROW 5 ================== -->
        <div class="row g-3 g-xl-4 mb-3 mb-xl-4">
            <div class="col-12 col-xl-8">
                <div class="card orchid-card h-100">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                            <div>
                                <h6 class="mb-0">Recent Customers</h6>
                                <small class="text-body-secondary">Latest activity from your customer
                                    base</small>
                            </div>
                            <div class="d-flex gap-2">
                                <div class="input-group input-group-sm orchid-inline-search">
                                    <span class="input-group-text bg-transparent border-end-0"><i
                                            class="bi bi-search"></i></span>
                                    <input type="search" class="form-control border-start-0" placeholder="Search customers…"
                                        aria-label="Search customers">
                                </div>
                                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-funnel"></i></button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table orchid-table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col">Customer</th>
                                        <th scope="col" class="d-none d-md-table-cell">Email</th>
                                        <th scope="col" class="d-none d-lg-table-cell">Location</th>
                                        <th scope="col">Spend</th>
                                        <th scope="col">Status</th>
                                        <th scope="col" class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <span
                                                    class="avatar avatar-sm bg-primary-subtle text-primary fw-semibold">EW</span>
                                                <div>
                                                    <p class="mb-0 fw-medium">Emma Watson</p>
                                                    <small class="text-body-secondary d-md-none">emma@nova.io</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="d-none d-md-table-cell text-body-secondary">emma@nova.io</td>
                                        <td class="d-none d-lg-table-cell text-body-secondary">London, UK</td>
                                        <td class="fw-semibold">$4,820</td>
                                        <td><span class="badge bg-success-subtle text-success">Active</span>
                                        </td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-icon" type="button" aria-label="Actions"><i
                                                    class="bi bi-three-dots-vertical"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <span
                                                    class="avatar avatar-sm bg-info-subtle text-info fw-semibold">JD</span>
                                                <div>
                                                    <p class="mb-0 fw-medium">James Doe</p>
                                                    <small class="text-body-secondary d-md-none">james@acme.com</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="d-none d-md-table-cell text-body-secondary">james@acme.com
                                        </td>
                                        <td class="d-none d-lg-table-cell text-body-secondary">New York, US</td>
                                        <td class="fw-semibold">$2,410</td>
                                        <td><span class="badge bg-warning-subtle text-warning">Pending</span>
                                        </td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-icon" type="button" aria-label="Actions"><i
                                                    class="bi bi-three-dots-vertical"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <span
                                                    class="avatar avatar-sm bg-success-subtle text-success fw-semibold">SM</span>
                                                <div>
                                                    <p class="mb-0 fw-medium">Sarah Miller</p>
                                                    <small class="text-body-secondary d-md-none">sarah@zen.co</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="d-none d-md-table-cell text-body-secondary">sarah@zen.co</td>
                                        <td class="d-none d-lg-table-cell text-body-secondary">Sydney, AU</td>
                                        <td class="fw-semibold">$7,920</td>
                                        <td><span class="badge bg-success-subtle text-success">Active</span>
                                        </td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-icon" type="button" aria-label="Actions"><i
                                                    class="bi bi-three-dots-vertical"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <span
                                                    class="avatar avatar-sm bg-warning-subtle text-warning fw-semibold">RG</span>
                                                <div>
                                                    <p class="mb-0 fw-medium">Ryan Green</p>
                                                    <small class="text-body-secondary d-md-none">ryan@peak.io</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="d-none d-md-table-cell text-body-secondary">ryan@peak.io</td>
                                        <td class="d-none d-lg-table-cell text-body-secondary">Toronto, CA</td>
                                        <td class="fw-semibold">$1,150</td>
                                        <td><span class="badge bg-danger-subtle text-danger">Inactive</span>
                                        </td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-icon" type="button" aria-label="Actions"><i
                                                    class="bi bi-three-dots-vertical"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <span
                                                    class="avatar avatar-sm bg-danger-subtle text-danger fw-semibold">AL</span>
                                                <div>
                                                    <p class="mb-0 fw-medium">Ava Lee</p>
                                                    <small class="text-body-secondary d-md-none">ava@bold.co</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="d-none d-md-table-cell text-body-secondary">ava@bold.co</td>
                                        <td class="d-none d-lg-table-cell text-body-secondary">Berlin, DE</td>
                                        <td class="fw-semibold">$3,640</td>
                                        <td><span class="badge bg-success-subtle text-success">Active</span>
                                        </td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-icon" type="button" aria-label="Actions"><i
                                                    class="bi bi-three-dots-vertical"></i></button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-4">
                <div class="card orchid-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">Task Updates</h6>
                            <a href="#" class="small">View all</a>
                        </div>
                        <ul class="orchid-timeline list-unstyled mb-0">
                            <li class="orchid-timeline__item">
                                <span class="orchid-timeline__dot bg-primary"></span>
                                <div>
                                    <p class="mb-0"><strong>Alex Kim</strong> completed <a href="#">Design
                                            System Audit</a></p>
                                    <small class="text-body-secondary">2 mins ago</small>
                                </div>
                            </li>
                            <li class="orchid-timeline__item">
                                <span class="orchid-timeline__dot bg-success"></span>
                                <div>
                                    <p class="mb-0"><strong>Sarah Miller</strong> added a comment on <a href="#">Q2
                                            Roadmap</a></p>
                                    <small class="text-body-secondary">18 mins ago</small>
                                </div>
                            </li>
                            <li class="orchid-timeline__item">
                                <span class="orchid-timeline__dot bg-warning"></span>
                                <div>
                                    <p class="mb-0"><strong>James Doe</strong> moved <a href="#">API
                                            Redesign</a> to In Progress</p>
                                    <small class="text-body-secondary">1 hour ago</small>
                                </div>
                            </li>
                            <li class="orchid-timeline__item">
                                <span class="orchid-timeline__dot bg-info"></span>
                                <div>
                                    <p class="mb-0"><strong>Ava Lee</strong> shared the file <a href="#">Brand.pdf</a></p>
                                    <small class="text-body-secondary">3 hours ago</small>
                                </div>
                            </li>
                            <li class="orchid-timeline__item">
                                <span class="orchid-timeline__dot bg-danger"></span>
                                <div>
                                    <p class="mb-0"><strong>Ryan Green</strong> reported an issue on <a
                                            href="#">Checkout</a></p>
                                    <small class="text-body-secondary">Yesterday</small>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection