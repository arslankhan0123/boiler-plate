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
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="{{asset('admin/assets/css/orchid.css')}}" rel="stylesheet">
</head>
<body class="orchid-body">

  <a class="visually-hidden-focusable position-absolute top-0 start-0 p-2 bg-primary text-white" href="#orchid-main">Skip to main content</a>

  <!-- ================== SIDEBAR ================== -->
  <aside class="orchid-sidebar" id="orchidSidebar" aria-label="Primary navigation">
    <div class="orchid-sidebar__brand">
      <a href="#" class="orchid-brand" aria-label="Orchid dashboard home">
        <span class="orchid-brand__logo" aria-hidden="true">
          <svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Orchid logo">
            <defs>
              <linearGradient id="orchidGrad" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0%" stop-color="#6366f1"/>
                <stop offset="100%" stop-color="#22d3ee"/>
              </linearGradient>
            </defs>
            <circle cx="16" cy="16" r="6" fill="url(#orchidGrad)"/>
            <ellipse cx="16" cy="16" rx="14" ry="6" fill="none" stroke="url(#orchidGrad)" stroke-width="2" transform="rotate(-30 16 16)"/>
          </svg>
        </span>
        <span class="orchid-brand__text">Orchid</span>
      </a>
      <button class="btn btn-sm btn-icon orchid-sidebar__close d-lg-none" type="button" data-orchid-sidebar-close aria-label="Close sidebar">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>

    <nav class="orchid-sidebar__nav" aria-label="Main">
      <div class="orchid-nav-section">
        <span class="orchid-nav-section__label">Main</span>
        <ul class="orchid-nav">
          <li>
            <a href="#navDashboard" class="orchid-nav__link orchid-nav__link--has-sub trail-active" data-bs-toggle="collapse" role="button" aria-expanded="true" aria-controls="navDashboard">
              <i class="bi bi-speedometer2"></i><span>Dashboard</span>
              <span class="badge bg-primary-subtle text-primary ms-auto">8</span>
              <i class="bi bi-chevron-down orchid-nav__chevron"></i>
            </a>
            <ul class="collapse show orchid-nav__sub list-unstyled" id="navDashboard">
              <li><a href="index.html" class="orchid-nav__sublink active" aria-current="page">Default</a></li>
              <li><a href="analytics.html" class="orchid-nav__sublink">Analytics</a></li>
              <li><a href="crm.html" class="orchid-nav__sublink">CRM</a></li>
              <li><a href="sales.html" class="orchid-nav__sublink">Sales</a></li>
              <li><a href="finance.html" class="orchid-nav__sublink">Finance</a></li>
              <li><a href="projects.html" class="orchid-nav__sublink">Projects</a></li>
              <li><a href="ecommerce.html" class="orchid-nav__sublink">E-Commerce</a></li>
              <li><a href="hr.html" class="orchid-nav__sublink">HR</a></li>
            </ul>
          </li>
        </ul>
      </div>

      <div class="orchid-nav-section">
        <span class="orchid-nav-section__label">Business</span>
        <ul class="orchid-nav">
          <li>
            <a href="#navCrm" class="orchid-nav__link orchid-nav__link--has-sub collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="navCrm">
              <i class="bi bi-people"></i><span>CRM</span>
              <i class="bi bi-chevron-down orchid-nav__chevron"></i>
            </a>
            <ul class="collapse orchid-nav__sub list-unstyled" id="navCrm">
              <li><a href="leads.html" class="orchid-nav__sublink">Leads</a></li>
              <li><a href="contacts.html" class="orchid-nav__sublink">Contacts</a></li>
              <li><a href="companies.html" class="orchid-nav__sublink">Companies</a></li>
              <li><a href="opportunities.html" class="orchid-nav__sublink">Opportunities</a></li>
              <li><a href="deals.html" class="orchid-nav__sublink">Deals</a></li>
              <li><a href="sales-pipeline.html" class="orchid-nav__sublink">Sales Pipeline</a></li>
            </ul>
          </li>
          <li>
            <a href="#navCustomers" class="orchid-nav__link orchid-nav__link--has-sub collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="navCustomers">
              <i class="bi bi-person-heart"></i><span>Customers</span>
              <i class="bi bi-chevron-down orchid-nav__chevron"></i>
            </a>
            <ul class="collapse orchid-nav__sub list-unstyled" id="navCustomers">
              <li><a href="customer-list.html" class="orchid-nav__sublink">Customer List</a></li>
              <li><a href="customer-details.html" class="orchid-nav__sublink">Details</a></li>
              <li><a href="customer-groups.html" class="orchid-nav__sublink">Groups</a></li>
              <li><a href="customer-feedback.html" class="orchid-nav__sublink">Feedback</a></li>
            </ul>
          </li>
          <li>
            <a href="#navSales" class="orchid-nav__link orchid-nav__link--has-sub collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="navSales">
              <i class="bi bi-cart3"></i><span>Sales</span>
              <i class="bi bi-chevron-down orchid-nav__chevron"></i>
            </a>
            <ul class="collapse orchid-nav__sub list-unstyled" id="navSales">
              <li><a href="quotations.html" class="orchid-nav__sublink">Quotations</a></li>
              <li><a href="orders.html" class="orchid-nav__sublink">Orders</a></li>
              <li><a href="invoice.html" class="orchid-nav__sublink">Invoices</a></li>
              <li><a href="payments.html" class="orchid-nav__sublink">Payments</a></li>
              <li><a href="refunds.html" class="orchid-nav__sublink">Refunds</a></li>
              <li><a href="revenue-analytics.html" class="orchid-nav__sublink">Revenue</a></li>
            </ul>
          </li>
          <li>
            <a href="#navProducts" class="orchid-nav__link orchid-nav__link--has-sub collapsed" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="navProducts">
              <i class="bi bi-box-seam"></i><span>Products</span>
              <i class="bi bi-chevron-down orchid-nav__chevron"></i>
            </a>
            <ul class="collapse orchid-nav__sub list-unstyled" id="navProducts">
              <li><a href="product-list.html" class="orchid-nav__sublink">Product List</a></li>
              <li><a href="product-categories.html" class="orchid-nav__sublink">Categories</a></li>
              <li><a href="product-brands.html" class="orchid-nav__sublink">Brands</a></li>
              <li><a href="inventory.html" class="orchid-nav__sublink">Inventory</a></li>
              <li><a href="stock-transfer.html" class="orchid-nav__sublink">Stock Transfer</a></li>
              <li><a href="price-management.html" class="orchid-nav__sublink">Price Management</a></li>
            </ul>
          </li>
        </ul>
      </div>

      <div class="orchid-nav-section">
        <span class="orchid-nav-section__label">Project Management</span>
        <ul class="orchid-nav">
          <li><a href="projects.html" class="orchid-nav__link"><i class="bi bi-kanban"></i><span>Projects</span></a></li>
          <li><a href="tasks.html" class="orchid-nav__link"><i class="bi bi-check2-square"></i><span>Tasks</span><span class="badge bg-danger ms-auto">8</span></a></li>
          <li><a href="teams.html" class="orchid-nav__link"><i class="bi bi-people-fill"></i><span>Teams</span></a></li>
          <li><a href="calendar.html" class="orchid-nav__link"><i class="bi bi-calendar3"></i><span>Calendar</span></a></li>
          <li><a href="meetings.html" class="orchid-nav__link"><i class="bi bi-camera-video"></i><span>Meetings</span></a></li>
        </ul>
      </div>

      <div class="orchid-nav-section">
        <span class="orchid-nav-section__label">Human Resources</span>
        <ul class="orchid-nav">
          <li><a href="employees.html" class="orchid-nav__link"><i class="bi bi-person-badge"></i><span>Employees</span></a></li>
          <li><a href="attendance.html" class="orchid-nav__link"><i class="bi bi-calendar-check"></i><span>Attendance</span></a></li>
          <li><a href="leave-management.html" class="orchid-nav__link"><i class="bi bi-calendar-x"></i><span>Leave Management</span></a></li>
          <li><a href="payroll.html" class="orchid-nav__link"><i class="bi bi-cash-stack"></i><span>Payroll</span></a></li>
          <li><a href="recruitment.html" class="orchid-nav__link"><i class="bi bi-person-plus"></i><span>Recruitment</span></a></li>
          <li><a href="performance.html" class="orchid-nav__link"><i class="bi bi-graph-up"></i><span>Performance</span></a></li>
        </ul>
      </div>

      <div class="orchid-nav-section">
        <span class="orchid-nav-section__label">Finance</span>
        <ul class="orchid-nav">
          <li><a href="accounting.html" class="orchid-nav__link"><i class="bi bi-calculator"></i><span>Accounting</span></a></li>
          <li><a href="expenses.html" class="orchid-nav__link"><i class="bi bi-cash-coin"></i><span>Expenses</span></a></li>
          <li><a href="income.html" class="orchid-nav__link"><i class="bi bi-arrow-down-circle"></i><span>Income</span></a></li>
          <li><a href="transactions.html" class="orchid-nav__link"><i class="bi bi-arrow-left-right"></i><span>Transactions</span></a></li>
          <li><a href="budgets.html" class="orchid-nav__link"><i class="bi bi-piggy-bank"></i><span>Budgets</span></a></li>
          <li><a href="financial-reports.html" class="orchid-nav__link"><i class="bi bi-file-earmark-bar-graph"></i><span>Financial Reports</span></a></li>
        </ul>
      </div>

      <div class="orchid-nav-section">
        <span class="orchid-nav-section__label">Communication</span>
        <ul class="orchid-nav">
          <li><a href="messages.html" class="orchid-nav__link"><i class="bi bi-chat-dots"></i><span>Messages</span><span class="badge bg-success ms-auto">3</span></a></li>
          <li><a href="chat.html" class="orchid-nav__link"><i class="bi bi-chat-left-text"></i><span>Chat</span></a></li>
          <li><a href="email.html" class="orchid-nav__link"><i class="bi bi-envelope"></i><span>Email</span></a></li>
          <li><a href="notifications.html" class="orchid-nav__link"><i class="bi bi-bell"></i><span>Notifications</span></a></li>
          <li><a href="support-tickets.html" class="orchid-nav__link"><i class="bi bi-life-preserver"></i><span>Support Tickets</span></a></li>
        </ul>
      </div>

      <div class="orchid-nav-section">
        <span class="orchid-nav-section__label">Analytics</span>
        <ul class="orchid-nav">
          <li><a href="reports.html" class="orchid-nav__link"><i class="bi bi-file-earmark-text"></i><span>Reports</span></a></li>
          <li><a href="sales-analytics.html" class="orchid-nav__link"><i class="bi bi-graph-up-arrow"></i><span>Sales Analytics</span></a></li>
          <li><a href="customer-analytics.html" class="orchid-nav__link"><i class="bi bi-people"></i><span>Customer Analytics</span></a></li>
          <li><a href="revenue-analytics.html" class="orchid-nav__link"><i class="bi bi-currency-dollar"></i><span>Revenue Analytics</span></a></li>
          <li><a href="marketing-analytics.html" class="orchid-nav__link"><i class="bi bi-megaphone"></i><span>Marketing Analytics</span></a></li>
        </ul>
      </div>

      <div class="orchid-nav-section">
        <span class="orchid-nav-section__label">User Management</span>
        <ul class="orchid-nav">
          <li><a href="users.html" class="orchid-nav__link"><i class="bi bi-person"></i><span>Users</span></a></li>
          <li><a href="roles.html" class="orchid-nav__link"><i class="bi bi-shield-lock"></i><span>Roles</span></a></li>
          <li><a href="permissions.html" class="orchid-nav__link"><i class="bi bi-key"></i><span>Permissions</span></a></li>
          <li><a href="user-activity.html" class="orchid-nav__link"><i class="bi bi-activity"></i><span>User Activity</span></a></li>
          <li><a href="login-history.html" class="orchid-nav__link"><i class="bi bi-clock-history"></i><span>Login History</span></a></li>
        </ul>
      </div>

      <div class="orchid-nav-section">
        <span class="orchid-nav-section__label">Applications</span>
        <ul class="orchid-nav">
          <li><a href="file-manager.html" class="orchid-nav__link"><i class="bi bi-folder"></i><span>File Manager</span></a></li>
          <li><a href="notes.html" class="orchid-nav__link"><i class="bi bi-sticky"></i><span>Notes</span></a></li>
          <li><a href="documents.html" class="orchid-nav__link"><i class="bi bi-file-earmark"></i><span>Documents</span></a></li>
          <li><a href="knowledge-base.html" class="orchid-nav__link"><i class="bi bi-book"></i><span>Knowledge Base</span></a></li>
          <li><a href="todo.html" class="orchid-nav__link"><i class="bi bi-list-check"></i><span>To-Do</span></a></li>
          <li><a href="bookmarks.html" class="orchid-nav__link"><i class="bi bi-bookmark"></i><span>Bookmarks</span></a></li>
        </ul>
      </div>

      <div class="orchid-nav-section">
        <span class="orchid-nav-section__label">E-Commerce</span>
        <ul class="orchid-nav">
          <li><a href="shop-products.html" class="orchid-nav__link"><i class="bi bi-tag"></i><span>Products</span></a></li>
          <li><a href="orders.html" class="orchid-nav__link"><i class="bi bi-bag"></i><span>Orders</span></a></li>
          <li><a href="shop-customers.html" class="orchid-nav__link"><i class="bi bi-person-check"></i><span>Customers</span></a></li>
          <li><a href="shop-coupons.html" class="orchid-nav__link"><i class="bi bi-ticket-perforated"></i><span>Coupons</span></a></li>
          <li><a href="shop-reviews.html" class="orchid-nav__link"><i class="bi bi-star"></i><span>Reviews</span></a></li>
          <li><a href="shop-shipping.html" class="orchid-nav__link"><i class="bi bi-truck"></i><span>Shipping</span></a></li>
          <li><a href="shop-taxes.html" class="orchid-nav__link"><i class="bi bi-percent"></i><span>Taxes</span></a></li>
        </ul>
      </div>

      <div class="orchid-nav-section">
        <span class="orchid-nav-section__label">Content Management</span>
        <ul class="orchid-nav">
          <li><a href="cms-pages.html" class="orchid-nav__link"><i class="bi bi-file-earmark-richtext"></i><span>Pages</span></a></li>
          <li><a href="cms-blog.html" class="orchid-nav__link"><i class="bi bi-journal-text"></i><span>Blog</span></a></li>
          <li><a href="cms-media.html" class="orchid-nav__link"><i class="bi bi-images"></i><span>Media Library</span></a></li>
          <li><a href="product-categories.html" class="orchid-nav__link"><i class="bi bi-tags"></i><span>Categories</span></a></li>
          <li><a href="cms-comments.html" class="orchid-nav__link"><i class="bi bi-chat-square-text"></i><span>Comments</span></a></li>
          <li><a href="cms-menus.html" class="orchid-nav__link"><i class="bi bi-menu-app"></i><span>Menus</span></a></li>
        </ul>
      </div>

      <div class="orchid-nav-section">
        <span class="orchid-nav-section__label">System</span>
        <ul class="orchid-nav">
          <li><a href="settings.html" class="orchid-nav__link"><i class="bi bi-gear"></i><span>Settings</span></a></li>
          <li><a href="integrations.html" class="orchid-nav__link"><i class="bi bi-puzzle"></i><span>Integrations</span></a></li>
          <li><a href="audit-logs.html" class="orchid-nav__link"><i class="bi bi-shield-check"></i><span>Audit Logs</span></a></li>
          <li><a href="system-logs.html" class="orchid-nav__link"><i class="bi bi-file-earmark-code"></i><span>System Logs</span></a></li>
          <li><a href="maintenance-mode.html" class="orchid-nav__link"><i class="bi bi-tools"></i><span>Maintenance Mode</span></a></li>
        </ul>
      </div>

      <div class="orchid-nav-section">
        <span class="orchid-nav-section__label">UI Components</span>
        <ul class="orchid-nav">
          <li><a href="ui-alerts.html" class="orchid-nav__link"><i class="bi bi-exclamation-triangle"></i><span>Alerts</span></a></li>
          <li><a href="ui-badges.html" class="orchid-nav__link"><i class="bi bi-award"></i><span>Badges</span></a></li>
          <li><a href="ui-buttons.html" class="orchid-nav__link"><i class="bi bi-app"></i><span>Buttons</span></a></li>
          <li><a href="ui-cards.html" class="orchid-nav__link"><i class="bi bi-card-heading"></i><span>Cards</span></a></li>
          <li><a href="ui-dropdowns.html" class="orchid-nav__link"><i class="bi bi-menu-down"></i><span>Dropdowns</span></a></li>
          <li><a href="ui-modals.html" class="orchid-nav__link"><i class="bi bi-window"></i><span>Modals</span></a></li>
          <li><a href="ui-tabs.html" class="orchid-nav__link"><i class="bi bi-window-stack"></i><span>Tabs</span></a></li>
          <li><a href="ui-pagination.html" class="orchid-nav__link"><i class="bi bi-three-dots"></i><span>Pagination</span></a></li>
          <li><a href="ui-progress.html" class="orchid-nav__link"><i class="bi bi-bar-chart-steps"></i><span>Progress</span></a></li>
          <li><a href="ui-toasts.html" class="orchid-nav__link"><i class="bi bi-chat-square-dots"></i><span>Toasts</span></a></li>
          <li><a href="ui-tooltips.html" class="orchid-nav__link"><i class="bi bi-info-circle"></i><span>Tooltips</span></a></li>
        </ul>
      </div>

      <div class="orchid-nav-section">
        <span class="orchid-nav-section__label">Forms</span>
        <ul class="orchid-nav">
          <li><a href="forms-basic.html" class="orchid-nav__link"><i class="bi bi-input-cursor"></i><span>Basic Forms</span></a></li>
          <li><a href="forms-validation.html" class="orchid-nav__link"><i class="bi bi-check-circle"></i><span>Validation</span></a></li>
          <li><a href="forms-wizard.html" class="orchid-nav__link"><i class="bi bi-magic"></i><span>Wizard</span></a></li>
          <li><a href="forms-file-upload.html" class="orchid-nav__link"><i class="bi bi-cloud-upload"></i><span>File Upload</span></a></li>
          <li><a href="forms-editor.html" class="orchid-nav__link"><i class="bi bi-textarea-t"></i><span>Rich Text Editor</span></a></li>
          <li><a href="forms-datepicker.html" class="orchid-nav__link"><i class="bi bi-calendar-date"></i><span>Date Picker</span></a></li>
          <li><a href="forms-timepicker.html" class="orchid-nav__link"><i class="bi bi-clock"></i><span>Time Picker</span></a></li>
          <li><a href="forms-colorpicker.html" class="orchid-nav__link"><i class="bi bi-palette"></i><span>Color Picker</span></a></li>
        </ul>
      </div>

      <div class="orchid-nav-section">
        <span class="orchid-nav-section__label">Tables</span>
        <ul class="orchid-nav">
          <li><a href="tables-basic.html" class="orchid-nav__link"><i class="bi bi-table"></i><span>Basic Table</span></a></li>
          <li><a href="tables-data.html" class="orchid-nav__link"><i class="bi bi-grid"></i><span>Data Table</span></a></li>
          <li><a href="tables-responsive.html" class="orchid-nav__link"><i class="bi bi-phone"></i><span>Responsive Table</span></a></li>
          <li><a href="tables-editable.html" class="orchid-nav__link"><i class="bi bi-pencil-square"></i><span>Editable Table</span></a></li>
          <li><a href="tables-advanced.html" class="orchid-nav__link"><i class="bi bi-grid-3x3"></i><span>Advanced Table</span></a></li>
        </ul>
      </div>

      <div class="orchid-nav-section">
        <span class="orchid-nav-section__label">Charts</span>
        <ul class="orchid-nav">
          <li><a href="charts-line.html" class="orchid-nav__link"><i class="bi bi-graph-up"></i><span>Line</span></a></li>
          <li><a href="charts-bar.html" class="orchid-nav__link"><i class="bi bi-bar-chart"></i><span>Bar</span></a></li>
          <li><a href="charts-area.html" class="orchid-nav__link"><i class="bi bi-graph-up-arrow"></i><span>Area</span></a></li>
          <li><a href="charts-pie.html" class="orchid-nav__link"><i class="bi bi-pie-chart"></i><span>Pie</span></a></li>
          <li><a href="charts-donut.html" class="orchid-nav__link"><i class="bi bi-circle"></i><span>Donut</span></a></li>
          <li><a href="charts-radar.html" class="orchid-nav__link"><i class="bi bi-broadcast"></i><span>Radar</span></a></li>
          <li><a href="charts-polar.html" class="orchid-nav__link"><i class="bi bi-record-circle"></i><span>Polar</span></a></li>
          <li><a href="charts-mixed.html" class="orchid-nav__link"><i class="bi bi-bar-chart-line"></i><span>Mixed</span></a></li>
          <li><a href="charts-chartjs.html" class="orchid-nav__link"><i class="bi bi-bar-chart-fill"></i><span>Chart.js</span></a></li>
          <li><a href="charts-apex.html" class="orchid-nav__link"><i class="bi bi-graph-up"></i><span>ApexCharts</span></a></li>
        </ul>
      </div>

      <div class="orchid-nav-section">
        <span class="orchid-nav-section__label">Icons</span>
        <ul class="orchid-nav">
          <li><a href="icons-bootstrap.html" class="orchid-nav__link"><i class="bi bi-bootstrap"></i><span>Bootstrap Icons</span></a></li>
          <li><a href="icons-fontawesome.html" class="orchid-nav__link"><i class="bi bi-star-fill"></i><span>Font Awesome</span></a></li>
          <li><a href="icons-lucide.html" class="orchid-nav__link"><i class="bi bi-emoji-smile"></i><span>Lucide</span></a></li>
          <li><a href="icons-remix.html" class="orchid-nav__link"><i class="bi bi-square"></i><span>Remix Icons</span></a></li>
        </ul>
      </div>

      <div class="orchid-nav-section">
        <span class="orchid-nav-section__label">Maps</span>
        <ul class="orchid-nav">
          <li><a href="maps-google.html" class="orchid-nav__link"><i class="bi bi-map"></i><span>Google Maps</span></a></li>
          <li><a href="maps-vector.html" class="orchid-nav__link"><i class="bi bi-geo-alt"></i><span>Vector Maps</span></a></li>
        </ul>
      </div>

      <div class="orchid-nav-section">
        <span class="orchid-nav-section__label">Authentication</span>
        <ul class="orchid-nav">
          <li><a href="login.html" class="orchid-nav__link"><i class="bi bi-box-arrow-in-right"></i><span>Login</span></a></li>
          <li><a href="register.html" class="orchid-nav__link"><i class="bi bi-person-plus"></i><span>Register</span></a></li>
          <li><a href="forgot-password.html" class="orchid-nav__link"><i class="bi bi-question-circle"></i><span>Forgot Password</span></a></li>
          <li><a href="reset-password.html" class="orchid-nav__link"><i class="bi bi-arrow-clockwise"></i><span>Reset Password</span></a></li>
          <li><a href="lock-screen.html" class="orchid-nav__link"><i class="bi bi-lock"></i><span>Lock Screen</span></a></li>
          <li><a href="two-factor.html" class="orchid-nav__link"><i class="bi bi-shield-check"></i><span>Two-Factor Auth</span></a></li>
        </ul>
      </div>

      <div class="orchid-nav-section">
        <span class="orchid-nav-section__label">Extra Pages</span>
        <ul class="orchid-nav">
          <li><a href="profile.html" class="orchid-nav__link"><i class="bi bi-person-circle"></i><span>Profile</span></a></li>
          <li><a href="account-settings.html" class="orchid-nav__link"><i class="bi bi-gear-fill"></i><span>Account Settings</span></a></li>
          <li><a href="pricing.html" class="orchid-nav__link"><i class="bi bi-currency-dollar"></i><span>Pricing</span></a></li>
          <li><a href="faq.html" class="orchid-nav__link"><i class="bi bi-question-square"></i><span>FAQ</span></a></li>
          <li><a href="help-center.html" class="orchid-nav__link"><i class="bi bi-headset"></i><span>Help Center</span></a></li>
          <li><a href="timeline.html" class="orchid-nav__link"><i class="bi bi-clock"></i><span>Timeline</span></a></li>
          <li><a href="invoice.html" class="orchid-nav__link"><i class="bi bi-receipt"></i><span>Invoice</span></a></li>
          <li><a href="coming-soon.html" class="orchid-nav__link"><i class="bi bi-hourglass-split"></i><span>Coming Soon</span></a></li>
          <li><a href="maintenance.html" class="orchid-nav__link"><i class="bi bi-cone-striped"></i><span>Maintenance</span></a></li>
          <li><a href="error-404.html" class="orchid-nav__link"><i class="bi bi-bug"></i><span>Error Pages</span></a></li>
        </ul>
      </div>

      <div class="orchid-upgrade-card">
        <div class="orchid-upgrade-card__glow" aria-hidden="true"></div>
        <div class="orchid-upgrade-card__body">
          <i class="bi bi-rocket-takeoff orchid-upgrade-card__icon" aria-hidden="true"></i>
          <h6 class="orchid-upgrade-card__title">Upgrade to Pro</h6>
          <p class="orchid-upgrade-card__text">Unlock premium widgets, charts and priority support.</p>
          <a href="#" class="btn btn-light btn-sm w-100 fw-semibold">Upgrade Now</a>
        </div>
      </div>
    </nav>
  </aside>

  <div class="orchid-backdrop" data-orchid-sidebar-close aria-hidden="true"></div>

  <!-- ================== APP WRAPPER ================== -->
  <div class="orchid-app">

    <!-- ================== HEADER ================== -->
    <header class="orchid-header" role="banner">
      <div class="orchid-header__left">
        <button class="btn btn-icon orchid-header__toggle" type="button" data-orchid-sidebar-toggle aria-label="Toggle sidebar" aria-controls="orchidSidebar" aria-expanded="false">
          <i class="bi bi-list"></i>
        </button>
        <nav aria-label="breadcrumb" class="orchid-header__breadcrumb d-none d-md-block">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="#"><i class="bi bi-house-door"></i></a></li>
            <li class="breadcrumb-item"><a href="#">Overview</a></li>
            <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
          </ol>
        </nav>
      </div>

      <div class="orchid-header__right">
        <form class="orchid-search d-none d-md-flex" role="search" data-orchid-search-form>
          <label for="orchidSearch" class="visually-hidden">Search</label>
          <i class="bi bi-search orchid-search__icon" aria-hidden="true"></i>
          <input type="search" id="orchidSearch" class="form-control" placeholder="Search anything…" autocomplete="off">
          <kbd class="orchid-search__kbd">⌘K</kbd>
        </form>

        <button class="btn btn-icon d-md-none" type="button" data-orchid-search-open aria-label="Open search">
          <i class="bi bi-search"></i>
        </button>

        <button class="btn btn-icon orchid-theme-toggle" type="button" data-orchid-theme-toggle aria-label="Toggle color theme">
          <i class="bi bi-sun-fill orchid-theme-toggle__sun"></i>
          <i class="bi bi-moon-stars-fill orchid-theme-toggle__moon"></i>
        </button>

        <div class="dropdown">
          <button class="btn btn-icon position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications">
            <i class="bi bi-bell"></i>
            <span class="orchid-badge-dot bg-danger"></span>
          </button>
          <div class="dropdown-menu dropdown-menu-end orchid-dropdown">
            <div class="orchid-dropdown__header">
              <h6 class="mb-0">Notifications</h6>
              <span class="badge bg-primary-subtle text-primary">5 New</span>
            </div>
            <ul class="orchid-dropdown__list list-unstyled mb-0">
              <li>
                <a href="#" class="orchid-dropdown__item">
                  <span class="avatar avatar-sm bg-primary-subtle text-primary"><i class="bi bi-person-plus"></i></span>
                  <div>
                    <p class="mb-0">New user registration</p>
                    <small class="text-body-secondary">2 minutes ago</small>
                  </div>
                </a>
              </li>
              <li>
                <a href="#" class="orchid-dropdown__item">
                  <span class="avatar avatar-sm bg-success-subtle text-success"><i class="bi bi-cash-coin"></i></span>
                  <div>
                    <p class="mb-0">Payment received — $2,400</p>
                    <small class="text-body-secondary">14 minutes ago</small>
                  </div>
                </a>
              </li>
              <li>
                <a href="#" class="orchid-dropdown__item">
                  <span class="avatar avatar-sm bg-warning-subtle text-warning"><i class="bi bi-exclamation-triangle"></i></span>
                  <div>
                    <p class="mb-0">Server load reached 78%</p>
                    <small class="text-body-secondary">1 hour ago</small>
                  </div>
                </a>
              </li>
              <li>
                <a href="#" class="orchid-dropdown__item">
                  <span class="avatar avatar-sm bg-info-subtle text-info"><i class="bi bi-chat-left-text"></i></span>
                  <div>
                    <p class="mb-0">New comment on report</p>
                    <small class="text-body-secondary">3 hours ago</small>
                  </div>
                </a>
              </li>
            </ul>
            <div class="orchid-dropdown__footer">
              <a href="#">View all notifications</a>
            </div>
          </div>
        </div>

        <div class="dropdown d-none d-sm-block">
          <button class="btn btn-icon position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Messages">
            <i class="bi bi-chat-dots"></i>
            <span class="orchid-badge-count bg-success">3</span>
          </button>
          <div class="dropdown-menu dropdown-menu-end orchid-dropdown">
            <div class="orchid-dropdown__header">
              <h6 class="mb-0">Messages</h6>
              <a href="#" class="small">Mark all read</a>
            </div>
            <ul class="orchid-dropdown__list list-unstyled mb-0">
              <li>
                <a href="#" class="orchid-dropdown__item">
                  <span class="avatar avatar-sm bg-primary text-white">SM</span>
                  <div>
                    <p class="mb-0"><strong>Sarah Miller</strong></p>
                    <small class="text-body-secondary">Loved your latest design mock…</small>
                  </div>
                </a>
              </li>
              <li>
                <a href="#" class="orchid-dropdown__item">
                  <span class="avatar avatar-sm bg-info text-white">JD</span>
                  <div>
                    <p class="mb-0"><strong>James Doe</strong></p>
                    <small class="text-body-secondary">The report is ready to review.</small>
                  </div>
                </a>
              </li>
              <li>
                <a href="#" class="orchid-dropdown__item">
                  <span class="avatar avatar-sm bg-warning text-white">AL</span>
                  <div>
                    <p class="mb-0"><strong>Ava Lee</strong></p>
                    <small class="text-body-secondary">Meeting rescheduled to Friday.</small>
                  </div>
                </a>
              </li>
            </ul>
            <div class="orchid-dropdown__footer"><a href="#">Open inbox</a></div>
          </div>
        </div>

        <div class="dropdown d-none d-sm-block">
          <button class="btn btn-icon" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Apps">
            <i class="bi bi-grid-3x3-gap"></i>
          </button>
          <div class="dropdown-menu dropdown-menu-end orchid-dropdown orchid-dropdown--apps">
            <div class="orchid-dropdown__header"><h6 class="mb-0">Apps</h6></div>
            <div class="orchid-apps-grid">
              <a href="#" class="orchid-apps-grid__item"><i class="bi bi-envelope text-primary"></i><span>Mail</span></a>
              <a href="#" class="orchid-apps-grid__item"><i class="bi bi-calendar3 text-danger"></i><span>Calendar</span></a>
              <a href="#" class="orchid-apps-grid__item"><i class="bi bi-file-earmark-text text-warning"></i><span>Docs</span></a>
              <a href="#" class="orchid-apps-grid__item"><i class="bi bi-image text-info"></i><span>Photos</span></a>
              <a href="#" class="orchid-apps-grid__item"><i class="bi bi-cloud text-success"></i><span>Drive</span></a>
              <a href="#" class="orchid-apps-grid__item"><i class="bi bi-camera-video text-primary"></i><span>Meet</span></a>
            </div>
          </div>
        </div>

        <button class="btn btn-icon d-none d-lg-inline-flex" type="button" aria-label="Calendar">
          <i class="bi bi-calendar3"></i>
        </button>

        <div class="dropdown">
          <button class="btn orchid-profile-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="User profile">
            <span class="avatar avatar-sm bg-primary-subtle text-primary fw-semibold">AK</span>
            <span class="orchid-profile-btn__meta d-none d-lg-flex">
              <span class="orchid-profile-btn__name">Alex Kim</span>
              <span class="orchid-profile-btn__role">Administrator</span>
            </span>
            <i class="bi bi-chevron-down d-none d-lg-inline"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-end orchid-dropdown orchid-dropdown--profile">
            <li class="orchid-dropdown__header">
              <div class="d-flex align-items-center gap-2">
                <span class="avatar bg-primary-subtle text-primary fw-semibold">AK</span>
                <div>
                  <p class="mb-0 fw-semibold">Alex Kim</p>
                  <small class="text-body-secondary">alex@orchid.io</small>
                </div>
              </div>
            </li>
            <li><a class="dropdown-item" href="#"><i class="bi bi-person"></i>Profile</a></li>
            <li><a class="dropdown-item" href="#"><i class="bi bi-gear"></i>Settings</a></li>
            <li><a class="dropdown-item" href="#"><i class="bi bi-wallet2"></i>Billing</a></li>
            <li><a class="dropdown-item" href="#"><i class="bi bi-life-preserver"></i>Help Center</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="#"><i class="bi bi-box-arrow-right"></i>Sign out</a></li>
          </ul>
        </div>
      </div>
    </header>

    <!-- ================== MAIN ================== -->
    <main class="orchid-main" id="orchid-main" tabindex="-1">
      <div class="container-fluid px-3 px-lg-4 py-4">

        <!-- Page header -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
          <div>
            <h1 class="h3 mb-1">Welcome back, Alex 👋</h1>
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
                  <span class="orchid-stat-card__icon bg-primary-subtle text-primary"><i class="bi bi-people"></i></span>
                </div>
                <span class="badge bg-success-subtle text-success"><i class="bi bi-arrow-up"></i> 12.4% vs last month</span>
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
                  <button class="btn btn-sm btn-icon" type="button" aria-label="More"><i class="bi bi-three-dots"></i></button>
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
                    <span class="badge bg-white text-primary"><i class="bi bi-arrow-up"></i> 8.24%</span>
                  </div>
                  <span class="orchid-stat-card__icon bg-white bg-opacity-25 text-white"><i class="bi bi-currency-dollar"></i></span>
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
                      <label class="form-check-label text-decoration-line-through text-body-secondary" for="task1">Prepare launch checklist</label>
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
                      <label class="form-check-label" for="task5">Publish weekly newsletter</label>
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
                  <div><span class="orchid-dot bg-danger"></span> Lost <strong class="ms-1">6</strong></div>
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
                  <span class="badge bg-success-subtle text-success"><i class="bi bi-arrow-up"></i> 2.4% vs last month</span>
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
                      <small class="text-body-secondary"><i class="bi bi-clock me-1"></i>10:00 – 11:00 AM</small>
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
                      <small class="text-body-secondary"><i class="bi bi-clock me-1"></i>2:00 – 2:45 PM</small>
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
                      <small class="text-body-secondary"><i class="bi bi-clock me-1"></i>9:30 – 10:30 AM</small>
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
                      <small class="text-body-secondary"><i class="bi bi-clock me-1"></i>4:00 – 5:00 PM</small>
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
                    <button class="btn btn-sm btn-icon" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Deals menu">
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
                    <small class="text-body-secondary">Progress across stages · $284,500 total</small>
                  </div>
                  <button class="btn btn-sm btn-outline-primary"><i class="bi bi-plus-lg me-1"></i>Add Stage</button>
                </div>

                <div class="row g-3">
                  <div class="col-6 col-md-4 col-xl-2">
                    <div class="orchid-pipeline">
                      <div class="d-flex justify-content-between mb-1">
                        <span class="fw-medium small">Leads</span>
                        <small class="text-body-secondary">$48k</small>
                      </div>
                      <div class="progress orchid-pipeline__bar" role="progressbar" aria-label="Leads" aria-valuenow="85" aria-valuemin="0" aria-valuemax="100">
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
                      <div class="progress orchid-pipeline__bar" role="progressbar" aria-label="Qualified" aria-valuenow="70" aria-valuemin="0" aria-valuemax="100">
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
                      <div class="progress orchid-pipeline__bar" role="progressbar" aria-label="Proposal" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100">
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
                      <div class="progress orchid-pipeline__bar" role="progressbar" aria-label="Negotiation" aria-valuenow="45" aria-valuemin="0" aria-valuemax="100">
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
                      <div class="progress orchid-pipeline__bar" role="progressbar" aria-label="Won" aria-valuenow="92" aria-valuemin="0" aria-valuemax="100">
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
                      <div class="progress orchid-pipeline__bar" role="progressbar" aria-label="Lost" aria-valuenow="18" aria-valuemin="0" aria-valuemax="100">
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
                    <small class="text-body-secondary">Latest activity from your customer base</small>
                  </div>
                  <div class="d-flex gap-2">
                    <div class="input-group input-group-sm orchid-inline-search">
                      <span class="input-group-text bg-transparent border-end-0"><i class="bi bi-search"></i></span>
                      <input type="search" class="form-control border-start-0" placeholder="Search customers…" aria-label="Search customers">
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
                            <span class="avatar avatar-sm bg-primary-subtle text-primary fw-semibold">EW</span>
                            <div>
                              <p class="mb-0 fw-medium">Emma Watson</p>
                              <small class="text-body-secondary d-md-none">emma@nova.io</small>
                            </div>
                          </div>
                        </td>
                        <td class="d-none d-md-table-cell text-body-secondary">emma@nova.io</td>
                        <td class="d-none d-lg-table-cell text-body-secondary">London, UK</td>
                        <td class="fw-semibold">$4,820</td>
                        <td><span class="badge bg-success-subtle text-success">Active</span></td>
                        <td class="text-end">
                          <button class="btn btn-sm btn-icon" type="button" aria-label="Actions"><i class="bi bi-three-dots-vertical"></i></button>
                        </td>
                      </tr>
                      <tr>
                        <td>
                          <div class="d-flex align-items-center gap-2">
                            <span class="avatar avatar-sm bg-info-subtle text-info fw-semibold">JD</span>
                            <div>
                              <p class="mb-0 fw-medium">James Doe</p>
                              <small class="text-body-secondary d-md-none">james@acme.com</small>
                            </div>
                          </div>
                        </td>
                        <td class="d-none d-md-table-cell text-body-secondary">james@acme.com</td>
                        <td class="d-none d-lg-table-cell text-body-secondary">New York, US</td>
                        <td class="fw-semibold">$2,410</td>
                        <td><span class="badge bg-warning-subtle text-warning">Pending</span></td>
                        <td class="text-end">
                          <button class="btn btn-sm btn-icon" type="button" aria-label="Actions"><i class="bi bi-three-dots-vertical"></i></button>
                        </td>
                      </tr>
                      <tr>
                        <td>
                          <div class="d-flex align-items-center gap-2">
                            <span class="avatar avatar-sm bg-success-subtle text-success fw-semibold">SM</span>
                            <div>
                              <p class="mb-0 fw-medium">Sarah Miller</p>
                              <small class="text-body-secondary d-md-none">sarah@zen.co</small>
                            </div>
                          </div>
                        </td>
                        <td class="d-none d-md-table-cell text-body-secondary">sarah@zen.co</td>
                        <td class="d-none d-lg-table-cell text-body-secondary">Sydney, AU</td>
                        <td class="fw-semibold">$7,920</td>
                        <td><span class="badge bg-success-subtle text-success">Active</span></td>
                        <td class="text-end">
                          <button class="btn btn-sm btn-icon" type="button" aria-label="Actions"><i class="bi bi-three-dots-vertical"></i></button>
                        </td>
                      </tr>
                      <tr>
                        <td>
                          <div class="d-flex align-items-center gap-2">
                            <span class="avatar avatar-sm bg-warning-subtle text-warning fw-semibold">RG</span>
                            <div>
                              <p class="mb-0 fw-medium">Ryan Green</p>
                              <small class="text-body-secondary d-md-none">ryan@peak.io</small>
                            </div>
                          </div>
                        </td>
                        <td class="d-none d-md-table-cell text-body-secondary">ryan@peak.io</td>
                        <td class="d-none d-lg-table-cell text-body-secondary">Toronto, CA</td>
                        <td class="fw-semibold">$1,150</td>
                        <td><span class="badge bg-danger-subtle text-danger">Inactive</span></td>
                        <td class="text-end">
                          <button class="btn btn-sm btn-icon" type="button" aria-label="Actions"><i class="bi bi-three-dots-vertical"></i></button>
                        </td>
                      </tr>
                      <tr>
                        <td>
                          <div class="d-flex align-items-center gap-2">
                            <span class="avatar avatar-sm bg-danger-subtle text-danger fw-semibold">AL</span>
                            <div>
                              <p class="mb-0 fw-medium">Ava Lee</p>
                              <small class="text-body-secondary d-md-none">ava@bold.co</small>
                            </div>
                          </div>
                        </td>
                        <td class="d-none d-md-table-cell text-body-secondary">ava@bold.co</td>
                        <td class="d-none d-lg-table-cell text-body-secondary">Berlin, DE</td>
                        <td class="fw-semibold">$3,640</td>
                        <td><span class="badge bg-success-subtle text-success">Active</span></td>
                        <td class="text-end">
                          <button class="btn btn-sm btn-icon" type="button" aria-label="Actions"><i class="bi bi-three-dots-vertical"></i></button>
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
                      <p class="mb-0"><strong>Alex Kim</strong> completed <a href="#">Design System Audit</a></p>
                      <small class="text-body-secondary">2 mins ago</small>
                    </div>
                  </li>
                  <li class="orchid-timeline__item">
                    <span class="orchid-timeline__dot bg-success"></span>
                    <div>
                      <p class="mb-0"><strong>Sarah Miller</strong> added a comment on <a href="#">Q2 Roadmap</a></p>
                      <small class="text-body-secondary">18 mins ago</small>
                    </div>
                  </li>
                  <li class="orchid-timeline__item">
                    <span class="orchid-timeline__dot bg-warning"></span>
                    <div>
                      <p class="mb-0"><strong>James Doe</strong> moved <a href="#">API Redesign</a> to In Progress</p>
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
                      <p class="mb-0"><strong>Ryan Green</strong> reported an issue on <a href="#">Checkout</a></p>
                      <small class="text-body-secondary">Yesterday</small>
                    </div>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- ================== FOOTER ================== -->
      <footer class="orchid-footer" role="contentinfo">
        <div class="container-fluid px-3 px-lg-4">
          <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <p class="mb-0 small text-body-secondary">© <span data-orchid-year></span> Orchid. Crafted with <i class="bi bi-heart-fill text-danger"></i> for modern teams.</p>
            <ul class="list-inline mb-0 small">
              <li class="list-inline-item"><a class="text-body-secondary" href="#">Privacy</a></li>
              <li class="list-inline-item"><a class="text-body-secondary" href="#">Terms</a></li>
              <li class="list-inline-item"><a class="text-body-secondary" href="#">Support</a></li>
            </ul>
          </div>
        </div>
      </footer>
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
