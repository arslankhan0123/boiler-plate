<!doctype html>
<html lang="en" data-bs-theme="light">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Orchid - Premium Bootstrap 5.3 Admin Dashboard Template">
    <meta name="theme-color" content="#4f46e5">
    <title>Orchid - Premium Admin Dashboard</title>

    <link rel="icon" type="image/svg+xml" href="{{asset('admin/assets/icons/favicon.svg')}}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{asset('admin/assets/css/orchid.css')}}" rel="stylesheet">

    <link href="{{asset('admin/assets/css/pages/users.css')}}" rel="stylesheet">
</head>

<body class="orchid-body">

    <a class="visually-hidden-focusable position-absolute top-0 start-0 p-2 bg-primary text-white"
        href="#orchid-main">Skip to main content</a>

    <!-- ================== SIDEBAR ================== -->
    @include('layouts.admin.sidebar')

    <div class="orchid-backdrop" data-orchid-sidebar-close aria-hidden="true"></div>

    <!-- ================== APP WRAPPER ================== -->
    <div class="orchid-app">

        <!-- ================== HEADER ================== -->
        @include('layouts.admin.header')

        <!-- ================== MAIN ================== -->
        <main class="orchid-main" id="orchid-main" tabindex="-1">
            @yield('content')
            <!-- ================== FOOTER ================== -->
            @include('layouts.admin.footer')
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="{{asset('admin/assets/js/theme.js')}}" defer></script>
    <script src="{{asset('admin/assets/js/sidebar.js')}}" defer></script>
    <script src="{{asset('admin/assets/js/charts.js')}}" defer></script>
    <script src="{{asset('admin/assets/js/orchid.js')}}" defer></script>
</body>

</html>