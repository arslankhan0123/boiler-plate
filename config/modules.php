<?php

/*
|--------------------------------------------------------------------------
| Module registry — single source of truth
|--------------------------------------------------------------------------
| Tenant subscription → enabled modules → user roles → permissions.
|
| scope:
|   platform  Master Admin only (central). Never available to tenants.
|   core      Always on for every tenant (tenant admin manages team/settings).
|   tenant    Business module. Master Admin enables it per tenant.
|
| requires     Modules that must also be enabled (dependency matrix).
| permissions  Granular actions => label. "<code>.view" shows the module.
| features     Submodules (menu items). Each needs a permission to be visible;
|              Master Admin can switch features off per tenant.
|
| The registry is synced into the master DB tables `modules` and
| `module_dependencies` automatically (App\Support\ModuleRegistry::sync()).
*/

return [
    'master_role' => 'Master Admin',

    'groups' => [
        'platform' => 'Platform',
        'core' => 'Administration',
        'operations' => 'Operations',
        'business' => 'Business Management',
    ],

    'modules' => [

        /* ───────────── Platform (Master Admin) ───────────── */
        'tenants' => [
            'route' => 'tenants.index',
            'name' => 'Tenants', 'icon' => 'bi-buildings', 'scope' => 'platform', 'group' => 'platform',
            'description' => 'Tenants, their databases, status and module assignments.',
            'permissions' => ['tenants.view' => 'View tenants', 'tenants.create' => 'Create tenants', 'tenants.update' => 'Edit tenants & modules', 'tenants.delete' => 'Delete tenants'],
            'features' => [],
        ],
        'modules' => [
            'route' => 'modules.index',
            'name' => 'Module Registry', 'icon' => 'bi-boxes', 'scope' => 'platform', 'group' => 'platform',
            'description' => 'All available modules and their dependencies.',
            'permissions' => ['modules.view' => 'View module registry'],
            'features' => [],
        ],

        /* ───────────── Core (every tenant) ───────────── */
        'users' => [
            'route' => 'users.index',
            'name' => 'Users', 'icon' => 'bi-people', 'scope' => 'core', 'group' => 'core',
            'description' => 'User accounts.',
            'permissions' => ['users.view' => 'View users', 'users.create' => 'Create users', 'users.update' => 'Edit users', 'users.delete' => 'Delete users'],
            'features' => [],
        ],
        'roles' => [
            'route' => 'roles.index',
            'name' => 'Roles & Permissions', 'icon' => 'bi-shield-lock', 'scope' => 'core', 'group' => 'core',
            'description' => 'Roles and what each role may do.',
            'permissions' => ['roles.view' => 'View roles', 'roles.create' => 'Create roles', 'roles.update' => 'Edit roles', 'roles.delete' => 'Delete roles'],
            'features' => [],
        ],
        'settings' => [
            'name' => 'Settings', 'icon' => 'bi-sliders', 'scope' => 'core', 'group' => 'core',
            'description' => 'Company profile, branches, taxes, currencies and other settings.',
            'permissions' => ['settings.view' => 'View settings', 'settings.update' => 'Change settings', 'settings.audit_logs' => 'View audit logs'],
            'features' => [
                'company' => ['name' => 'Company Profile', 'permission' => 'settings.view'],
                'branches' => ['name' => 'Branches / Locations', 'permission' => 'settings.view'],
                'taxes' => ['name' => 'Taxes & Currencies', 'permission' => 'settings.view'],
                'payments' => ['name' => 'Payment & Shipping', 'permission' => 'settings.view'],
                'notifications' => ['name' => 'Notifications', 'permission' => 'settings.view'],
                'audit-logs' => ['name' => 'Audit Logs', 'permission' => 'settings.audit_logs'],
            ],
        ],

        /* ───────────── Operations ───────────── */
        'sales' => [
            'name' => 'Sales', 'icon' => 'bi-receipt', 'scope' => 'tenant', 'group' => 'operations',
            'description' => 'Quotations, orders, invoices, payments and returns.',
            'requires' => [],
            'permissions' => [
                'sales.view' => 'View sales', 'sales.create' => 'Create', 'sales.update' => 'Edit', 'sales.delete' => 'Delete',
                'sales.approve' => 'Approve', 'sales.invoice' => 'Issue invoices', 'sales.receive_payment' => 'Receive payments',
                'sales.refund' => 'Returns & credit notes', 'sales.reports' => 'Sales reports',
            ],
            'features' => [
                'quotations' => ['name' => 'Quotations', 'permission' => 'sales.view'],
                'orders' => ['name' => 'Sales Orders', 'permission' => 'sales.view'],
                'invoices' => ['name' => 'Invoices', 'permission' => 'sales.invoice'],
                'payments' => ['name' => 'Customer Payments', 'permission' => 'sales.receive_payment'],
                'returns' => ['name' => 'Returns & Credit Notes', 'permission' => 'sales.refund'],
                'commissions' => ['name' => 'Sales Reps & Commissions', 'permission' => 'sales.view'],
                'reports' => ['name' => 'Sales Reports', 'permission' => 'sales.reports'],
            ],
        ],
        'purchase' => [
            'name' => 'Purchase', 'icon' => 'bi-cart-plus', 'scope' => 'tenant', 'group' => 'operations',
            'description' => 'Requests, RFQs, purchase orders, GRN, bills and supplier payments.',
            'requires' => ['inventory'],
            'permissions' => [
                'purchase.view' => 'View purchases', 'purchase.create' => 'Create', 'purchase.update' => 'Edit', 'purchase.delete' => 'Delete',
                'purchase.approve' => 'Approve', 'purchase.receive' => 'Receive goods (GRN)', 'purchase.pay' => 'Pay suppliers',
                'purchase.return' => 'Purchase returns', 'purchase.reports' => 'Purchase reports',
            ],
            'features' => [
                'requests' => ['name' => 'Purchase Requests', 'permission' => 'purchase.view'],
                'rfq' => ['name' => 'Requests for Quotation', 'permission' => 'purchase.view'],
                'orders' => ['name' => 'Purchase Orders', 'permission' => 'purchase.view'],
                'grn' => ['name' => 'Goods Received Notes', 'permission' => 'purchase.receive'],
                'bills' => ['name' => 'Supplier Bills', 'permission' => 'purchase.view'],
                'payments' => ['name' => 'Supplier Payments', 'permission' => 'purchase.pay'],
                'returns' => ['name' => 'Purchase Returns', 'permission' => 'purchase.return'],
                'reports' => ['name' => 'Purchase Reports', 'permission' => 'purchase.reports'],
            ],
        ],
        'inventory' => [
            'name' => 'Inventory', 'icon' => 'bi-box-seam', 'scope' => 'tenant', 'group' => 'operations',
            'description' => 'Products, warehouses, stock levels, transfers and counts.',
            'requires' => [],
            'permissions' => [
                'inventory.view' => 'View inventory', 'inventory.manage_products' => 'Manage products', 'inventory.manage_warehouses' => 'Manage warehouses',
                'inventory.adjust' => 'Stock adjustments', 'inventory.transfer' => 'Stock transfers', 'inventory.stock_count' => 'Stock counts',
                'inventory.reports' => 'Inventory reports',
            ],
            'features' => [
                'products' => ['name' => 'Products & Variants', 'permission' => 'inventory.view'],
                'categories' => ['name' => 'Categories & Brands', 'permission' => 'inventory.manage_products'],
                'warehouses' => ['name' => 'Warehouses & Locations', 'permission' => 'inventory.manage_warehouses'],
                'stock' => ['name' => 'Stock Levels', 'permission' => 'inventory.view'],
                'transfers' => ['name' => 'Stock Transfers', 'permission' => 'inventory.transfer'],
                'adjustments' => ['name' => 'Stock Adjustments', 'permission' => 'inventory.adjust'],
                'tracking' => ['name' => 'Batch / Serial / Expiry', 'permission' => 'inventory.view'],
                'counts' => ['name' => 'Stock Counts', 'permission' => 'inventory.stock_count'],
                'reorder' => ['name' => 'Reorder Levels', 'permission' => 'inventory.manage_products'],
                'reports' => ['name' => 'Valuation & Reports', 'permission' => 'inventory.reports'],
            ],
        ],
        'pos' => [
            'name' => 'POS', 'icon' => 'bi-shop', 'scope' => 'tenant', 'group' => 'operations',
            'description' => 'Point of sale — a sales channel using Sales and Inventory.',
            'requires' => ['sales', 'inventory'],
            'permissions' => [
                'pos.view' => 'Access POS', 'pos.open' => 'Open register', 'pos.sell' => 'Sell / checkout', 'pos.print_receipt' => 'Print receipts',
                'pos.hold' => 'Hold orders', 'pos.refund' => 'Refunds', 'pos.close_register' => 'Close register', 'pos.manage_register' => 'Manage registers',
                'pos.reports' => 'Register reports',
            ],
            'features' => [
                'terminal' => ['name' => 'POS Terminal', 'permission' => 'pos.sell'],
                'registers' => ['name' => 'Registers & Shifts', 'permission' => 'pos.open'],
                'held' => ['name' => 'Held Orders', 'permission' => 'pos.hold'],
                'returns' => ['name' => 'Returns & Refunds', 'permission' => 'pos.refund'],
                'reports' => ['name' => 'Register Reports', 'permission' => 'pos.reports'],
            ],
        ],
        'ecommerce' => [
            'name' => 'E-commerce', 'icon' => 'bi-bag', 'scope' => 'tenant', 'group' => 'operations',
            'description' => 'Online store — orders flow into Sales.',
            'requires' => ['sales', 'inventory'],
            'permissions' => [
                'ecommerce.view' => 'View store', 'ecommerce.manage_products' => 'Manage catalog', 'ecommerce.manage_orders' => 'Manage online orders',
                'ecommerce.manage_promotions' => 'Coupons & promotions', 'ecommerce.manage_shipping' => 'Shipping & delivery',
                'ecommerce.manage_reviews' => 'Reviews', 'ecommerce.manage_seo' => 'SEO & marketing', 'ecommerce.settings' => 'Store settings',
                'ecommerce.reports' => 'Store analytics',
            ],
            'features' => [
                'catalog' => ['name' => 'Catalog', 'permission' => 'ecommerce.manage_products'],
                'orders' => ['name' => 'Online Orders', 'permission' => 'ecommerce.manage_orders'],
                'promotions' => ['name' => 'Coupons & Promotions', 'permission' => 'ecommerce.manage_promotions'],
                'shipping' => ['name' => 'Shipping & Delivery', 'permission' => 'ecommerce.manage_shipping'],
                'customers' => ['name' => 'Customer Accounts', 'permission' => 'ecommerce.view'],
                'reviews' => ['name' => 'Reviews & Wishlists', 'permission' => 'ecommerce.manage_reviews'],
                'seo' => ['name' => 'SEO & Marketing', 'permission' => 'ecommerce.manage_seo'],
                'abandoned' => ['name' => 'Abandoned Carts', 'permission' => 'ecommerce.manage_orders'],
                'storefront' => ['name' => 'Storefront & Payments', 'permission' => 'ecommerce.settings'],
                'analytics' => ['name' => 'Store Analytics', 'permission' => 'ecommerce.reports'],
            ],
        ],
        'crm' => [
            'name' => 'CRM', 'icon' => 'bi-person-lines-fill', 'scope' => 'tenant', 'group' => 'operations',
            'description' => 'Leads, contacts, pipeline and follow-ups.',
            'requires' => [],
            'permissions' => [
                'crm.view' => 'View CRM', 'crm.manage_leads' => 'Manage leads', 'crm.manage_contacts' => 'Manage contacts',
                'crm.manage_pipeline' => 'Manage pipeline', 'crm.manage_activities' => 'Activities & follow-ups', 'crm.manage_campaigns' => 'Campaigns',
            ],
            'features' => [
                'leads' => ['name' => 'Leads', 'permission' => 'crm.manage_leads'],
                'contacts' => ['name' => 'Contacts & Companies', 'permission' => 'crm.manage_contacts'],
                'pipeline' => ['name' => 'Opportunities / Pipeline', 'permission' => 'crm.manage_pipeline'],
                'activities' => ['name' => 'Activities & Follow-ups', 'permission' => 'crm.manage_activities'],
                'campaigns' => ['name' => 'Campaigns', 'permission' => 'crm.manage_campaigns'],
                'history' => ['name' => 'Customer History', 'permission' => 'crm.view'],
            ],
        ],

        /* ───────────── Business management ───────────── */
        'accounting' => [
            'name' => 'Accounting', 'icon' => 'bi-calculator', 'scope' => 'tenant', 'group' => 'business',
            'description' => 'Chart of accounts, journals, ledger, AR/AP, banking and statements.',
            'requires' => [],
            'permissions' => [
                'accounting.view' => 'View accounting', 'accounting.manage_accounts' => 'Chart of accounts', 'accounting.post_journal' => 'Post journal entries',
                'accounting.reconcile' => 'Bank reconciliation', 'accounting.manage_tax' => 'Tax management', 'accounting.close_period' => 'Close periods',
                'accounting.reports' => 'Financial statements',
            ],
            'features' => [
                'chart' => ['name' => 'Chart of Accounts', 'permission' => 'accounting.manage_accounts'],
                'journals' => ['name' => 'Journal Entries', 'permission' => 'accounting.view'],
                'ledger' => ['name' => 'General Ledger', 'permission' => 'accounting.view'],
                'receivable' => ['name' => 'Accounts Receivable', 'permission' => 'accounting.view'],
                'payable' => ['name' => 'Accounts Payable', 'permission' => 'accounting.view'],
                'banking' => ['name' => 'Bank & Cash', 'permission' => 'accounting.view'],
                'reconciliation' => ['name' => 'Reconciliation', 'permission' => 'accounting.reconcile'],
                'tax' => ['name' => 'Tax Management', 'permission' => 'accounting.manage_tax'],
                'statements' => ['name' => 'Financial Statements', 'permission' => 'accounting.reports'],
                'periods' => ['name' => 'Accounting Periods', 'permission' => 'accounting.close_period'],
            ],
        ],
        'hrm' => [
            'name' => 'HRM', 'icon' => 'bi-person-badge', 'scope' => 'tenant', 'group' => 'business',
            'description' => 'Employees, org chart, attendance, leave, payroll and recruitment.',
            'requires' => [],
            'permissions' => [
                'hrm.self_service' => 'My HR (own profile, leaves, attendance, payslips)',
                'hrm.view_employees' => 'View all employees & org chart',
                'hrm.manage_employees' => 'Add / edit employees, logins & access',
                'hrm.manage_org' => 'Departments & designations',
                'hrm.view_attendance' => 'View everyone\'s attendance',
                'hrm.manage_attendance' => 'Mark / edit attendance',
                'hrm.approve_leave' => 'Approve / reject leave',
                'hrm.manage_leave' => 'Leave types, yearly allocation & holidays',
                'hrm.process_payroll' => 'Run payroll & payslips',
                'hrm.manage_recruitment' => 'Jobs & applications',
                'hrm.reports' => 'HR reports',
            ],
            'view_permission' => 'hrm.self_service',
            'features' => [
                // Self-service — every employee with a login
                'my-profile' => ['employee_only' => true, 'name' => 'My Profile', 'permission' => 'hrm.self_service', 'route' => 'hrm.me.profile'],
                'my-attendance' => ['employee_only' => true, 'name' => 'My Attendance', 'permission' => 'hrm.self_service', 'route' => 'hrm.me.attendance'],
                'my-leaves' => ['employee_only' => true, 'name' => 'My Leaves', 'permission' => 'hrm.self_service', 'route' => 'hrm.me.leaves'],
                'my-payslips' => ['employee_only' => true, 'name' => 'My Payslips', 'permission' => 'hrm.self_service', 'route' => 'hrm.me.payslips'],
                // HR team
                'employees' => ['name' => 'Employees', 'permission' => 'hrm.view_employees', 'route' => 'hrm.employees.index'],
                'org-chart' => ['name' => 'Organization Chart', 'permission' => 'hrm.view_employees', 'route' => 'hrm.org-chart'],
                'departments' => ['name' => 'Departments', 'permission' => 'hrm.manage_org', 'route' => 'hrm.departments.index'],
                'designations' => ['name' => 'Designations', 'permission' => 'hrm.manage_org', 'route' => 'hrm.designations.index'],
                'attendance' => ['name' => 'Attendance', 'permission' => 'hrm.view_attendance', 'route' => 'hrm.attendance.index'],
                'leave-requests' => ['name' => 'Leave Requests', 'permission' => 'hrm.approve_leave', 'route' => 'hrm.leaves.index'],
                'leave-types' => ['name' => 'Leave Types', 'permission' => 'hrm.manage_leave', 'route' => 'hrm.leave-types.index'],
                'leave-allocations' => ['name' => 'Yearly Allocation', 'permission' => 'hrm.manage_leave', 'route' => 'hrm.allocations.index'],
                'holidays' => ['name' => 'Holidays', 'permission' => 'hrm.manage_leave', 'route' => 'hrm.holidays.index'],
                'payroll' => ['name' => 'Payroll', 'permission' => 'hrm.process_payroll', 'route' => 'hrm.payroll.index'],
                'jobs' => ['name' => 'Jobs & Careers', 'permission' => 'hrm.manage_recruitment', 'route' => 'hrm.jobs.index'],
                'applications' => ['name' => 'Applications', 'permission' => 'hrm.manage_recruitment', 'route' => 'hrm.applications.index'],
                'reports' => ['name' => 'HR Reports', 'permission' => 'hrm.reports', 'route' => 'hrm.reports'],
            ],
        ],
        'projects' => [
            'name' => 'Projects', 'icon' => 'bi-kanban', 'scope' => 'tenant', 'group' => 'business',
            'description' => 'Projects, tasks and time tracking.',
            'requires' => [],
            'permissions' => ['projects.view' => 'View projects', 'projects.create' => 'Create', 'projects.update' => 'Edit', 'projects.delete' => 'Delete', 'projects.manage_tasks' => 'Manage tasks'],
            'features' => [
                'list' => ['name' => 'Projects', 'permission' => 'projects.view'],
                'tasks' => ['name' => 'Tasks', 'permission' => 'projects.manage_tasks'],
            ],
        ],
        'reports' => [
            'name' => 'Reports & Analytics', 'icon' => 'bi-bar-chart-line', 'scope' => 'tenant', 'group' => 'business',
            'description' => 'Cross-module dashboards. Shows only enabled, permitted modules.',
            'requires_any' => ['sales', 'purchase', 'inventory', 'pos', 'ecommerce', 'accounting', 'hrm'],
            'permissions' => ['reports.view' => 'View reports', 'reports.export' => 'Export reports'],
            'features' => [
                'sales' => ['name' => 'Sales & Purchase', 'permission' => 'reports.view', 'needs_module' => ['sales', 'purchase']],
                'inventory' => ['name' => 'Inventory', 'permission' => 'reports.view', 'needs_module' => ['inventory']],
                'pos' => ['name' => 'POS', 'permission' => 'reports.view', 'needs_module' => ['pos']],
                'ecommerce' => ['name' => 'E-commerce', 'permission' => 'reports.view', 'needs_module' => ['ecommerce']],
                'accounting' => ['name' => 'Accounting', 'permission' => 'reports.view', 'needs_module' => ['accounting']],
                'hr' => ['name' => 'HR', 'permission' => 'reports.view', 'needs_module' => ['hrm']],
            ],
        ],
    ],
];
