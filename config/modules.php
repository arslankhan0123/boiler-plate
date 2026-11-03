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
            'name' => 'Settings', 'icon' => 'bi-sliders', 'scope' => 'core', 'group' => 'core', 'tenant_only' => true,
            'description' => 'Company profile, branches, taxes, currencies and other settings.',
            'permissions' => ['settings.view' => 'View settings', 'settings.update' => 'Change settings', 'settings.audit_logs' => 'View audit logs'],
            'features' => [
                'company' => ['route' => 'settings.company', 'name' => 'Company Profile', 'permission' => 'settings.view'],
                'branches' => ['route' => 'settings.branches', 'name' => 'Branches / Locations', 'permission' => 'settings.view'],
                'taxes' => ['route' => 'settings.taxes', 'name' => 'Taxes & Currencies', 'permission' => 'settings.view'],
                'payments' => ['route' => 'settings.payments', 'name' => 'Payment & Shipping', 'permission' => 'settings.view'],
                'notifications' => ['route' => 'settings.notifications', 'name' => 'Notifications', 'permission' => 'settings.view'],
                'audit-logs' => ['route' => 'settings.audit', 'name' => 'Audit Logs', 'permission' => 'settings.audit_logs'],
            ],
        ],

        /* ───────────── Operations ───────────── */
        'sales' => [
            'name' => 'Sales', 'icon' => 'bi-receipt', 'scope' => 'tenant', 'group' => 'operations',
            'description' => 'Invoices, quotations, delivery notes, returns, customers, payments & cheques, coupons and salesmen.',
            'requires' => ['inventory'],
            'permissions' => [
                'sales.view' => 'View sales invoices', 'sales.create' => 'Create invoices', 'sales.update' => 'Edit invoices', 'sales.delete' => 'Delete invoices',
                'sales.discount' => 'Change prices & give discounts', 'sales.payments' => 'Receive payments, refunds & advances',
                'sales.quotations' => 'Quotations', 'sales.delivery_notes' => 'Delivery notes', 'sales.returns' => 'Sales returns',
                'sales.customers' => 'Manage customers', 'sales.coupons' => 'Discount & customer coupons', 'sales.salesmen' => 'Salesmen & commissions',
                'sales.reports' => 'Sales reports', 'sales.settings' => 'Sales settings (numbering, invoice print)',
            ],
            'features' => [
                'invoices' => ['name' => 'Sales Invoices', 'permission' => 'sales.view', 'route' => 'sales.invoices.index'],
                'quotations' => ['name' => 'Quotations', 'permission' => 'sales.quotations', 'route' => 'sales.quotations.index'],
                'delivery_notes' => ['name' => 'Delivery Notes', 'permission' => 'sales.delivery_notes', 'route' => 'sales.deliveries.index'],
                'returns' => ['name' => 'Sales Returns', 'permission' => 'sales.returns', 'route' => 'sales.returns.index'],
                'customers' => ['name' => 'Customers', 'permission' => 'sales.customers', 'route' => 'sales.customers.index'],
                'payments' => ['name' => 'Payments & Cheques', 'permission' => 'sales.payments', 'route' => 'sales.payments.index'],
                'coupons' => ['name' => 'Coupons', 'permission' => 'sales.coupons', 'route' => 'sales.coupons.index'],
                'salesmen' => ['name' => 'Salesmen', 'permission' => 'sales.salesmen', 'route' => 'sales.salesmen.index'],
                'reports' => ['name' => 'Reports', 'permission' => 'sales.reports', 'route' => 'retail.reports'],
                'settings' => ['name' => 'Sales Settings', 'permission' => 'sales.settings', 'route' => 'sales.settings'],
            ],
        ],
        'purchase' => [
            'name' => 'Purchase', 'icon' => 'bi-cart-plus', 'scope' => 'tenant', 'group' => 'operations',
            'description' => 'Purchase orders, purchases (stock in), returns, suppliers and supplier payments.',
            'requires' => ['inventory'],
            'permissions' => [
                'purchase.view' => 'View purchases', 'purchase.create' => 'Create purchases', 'purchase.update' => 'Edit purchases', 'purchase.delete' => 'Delete purchases',
                'purchase.rfq' => 'Requests for quotation', 'purchase.orders' => 'Purchase orders', 'purchase.returns' => 'Purchase returns', 'purchase.suppliers' => 'Manage suppliers',
                'purchase.payments' => 'Pay suppliers', 'purchase.reports' => 'Purchase reports',
            ],
            'features' => [
                'rfq' => ['name' => 'Requests for Quotation', 'permission' => 'purchase.rfq', 'route' => 'purchase.rfq.index'],
                'orders' => ['name' => 'Purchase Orders', 'permission' => 'purchase.orders', 'route' => 'purchase.orders.index'],
                'purchases' => ['name' => 'Purchases', 'permission' => 'purchase.view', 'route' => 'purchase.purchases.index'],
                'returns' => ['name' => 'Purchase Returns', 'permission' => 'purchase.returns', 'route' => 'purchase.returns.index'],
                'suppliers' => ['name' => 'Suppliers', 'permission' => 'purchase.suppliers', 'route' => 'purchase.suppliers.index'],
                'payments' => ['name' => 'Supplier Payments', 'permission' => 'purchase.payments', 'route' => 'purchase.payments.index'],
                'reports' => ['name' => 'Reports', 'permission' => 'purchase.reports', 'route' => 'retail.reports'],
            ],
        ],
        'inventory' => [
            'name' => 'Inventory', 'icon' => 'bi-box-seam', 'scope' => 'tenant', 'group' => 'operations',
            'description' => 'Items & services, variants, warehouses, stock levels, adjustments, damage, transfers and barcode labels.',
            'requires' => [],
            'permissions' => [
                'inventory.view' => 'View items & stock', 'inventory.manage_products' => 'Create / edit items & masters', 'inventory.manage_warehouses' => 'Manage warehouses',
                'inventory.adjust' => 'Stock adjustments & damaged items', 'inventory.transfer' => 'Stock transfers', 'inventory.reports' => 'Inventory reports',
            ],
            'features' => [
                'products' => ['name' => 'Items & Services', 'permission' => 'inventory.view', 'route' => 'inventory.items.index'],
                'categories' => ['name' => 'Categories, Brands & Units', 'permission' => 'inventory.view', 'route' => 'inventory.masters'],
                'warehouses' => ['name' => 'Warehouses', 'permission' => 'inventory.view', 'route' => 'inventory.warehouses.index'],
                'stock' => ['name' => 'Stock Levels', 'permission' => 'inventory.view', 'route' => 'inventory.stock'],
                'adjustments' => ['name' => 'Stock Adjustments', 'permission' => 'inventory.adjust', 'route' => 'inventory.adjustments.index'],
                'damages' => ['name' => 'Damaged Items', 'permission' => 'inventory.adjust', 'route' => 'inventory.damages.index'],
                'transfers' => ['name' => 'Stock Transfers', 'permission' => 'inventory.transfer', 'route' => 'inventory.transfers.index'],
                'counts' => ['name' => 'Stock Count', 'permission' => 'inventory.adjust', 'route' => 'inventory.counts.index'],
                'labels' => ['name' => 'Barcode Labels', 'permission' => 'inventory.view', 'route' => 'inventory.labels'],
                'reports' => ['name' => 'Inventory Reports', 'permission' => 'inventory.reports', 'route' => 'retail.reports'],
            ],
        ],
        'pos' => [
            'name' => 'POS', 'icon' => 'bi-shop', 'scope' => 'tenant', 'group' => 'operations',
            'description' => 'Fast checkout: barcode, hold / resume, split payments with change, coupons and thermal receipts.',
            'requires' => ['sales', 'inventory'],
            'permissions' => [
                'pos.sell' => 'Use POS (checkout)', 'pos.hold' => 'Hold & resume invoices', 'pos.edit' => 'Edit POS invoices', 'pos.discount' => 'Change price / discount in POS',
                'pos.open' => 'Open cash register & pay in/out', 'pos.close_register' => 'Close register (count cash)',
                'pos.sales' => 'View POS sales', 'pos.void_logs' => 'View void log', 'pos.summary' => 'Daily POS summary',
            ],
            'features' => [
                'terminal' => ['name' => 'POS Terminal', 'permission' => 'pos.sell', 'route' => 'pos'],
                'registers' => ['name' => 'Cash Registers', 'permission' => 'pos.open', 'route' => 'pos.registers.index'],
                'sales' => ['name' => 'POS Sales', 'permission' => 'pos.sales', 'route' => 'pos.sales'],
                'held' => ['name' => 'Held Invoices', 'permission' => 'pos.hold', 'route' => 'pos.held'],
                'summary' => ['name' => 'Daily Summary', 'permission' => 'pos.summary', 'route' => 'pos.summary'],
                'void_logs' => ['name' => 'Void Log', 'permission' => 'pos.void_logs', 'route' => 'pos.void-logs'],
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
                'catalog' => ['name' => 'Catalog', 'permission' => 'ecommerce.manage_products', 'route' => 'ecommerce.catalog.index'],
                'orders' => ['name' => 'Online Orders', 'permission' => 'ecommerce.manage_orders', 'route' => 'ecommerce.orders.index'],
                'promotions' => ['name' => 'Coupons & Promotions', 'permission' => 'ecommerce.manage_promotions', 'route' => 'ecommerce.promotions.index'],
                'shipping' => ['name' => 'Shipping & Delivery', 'permission' => 'ecommerce.manage_shipping', 'route' => 'ecommerce.shipping.index'],
                'customers' => ['name' => 'Customer Accounts', 'permission' => 'ecommerce.view', 'route' => 'ecommerce.customers.index'],
                'reviews' => ['name' => 'Reviews & Wishlists', 'permission' => 'ecommerce.manage_reviews', 'route' => 'ecommerce.reviews.index'],
                'seo' => ['name' => 'SEO & Marketing', 'permission' => 'ecommerce.manage_seo', 'route' => 'ecommerce.seo.index'],
                'abandoned' => ['name' => 'Abandoned Carts', 'permission' => 'ecommerce.manage_orders', 'route' => 'ecommerce.abandoned.index'],
                'storefront' => ['name' => 'Storefront & Payments', 'permission' => 'ecommerce.settings', 'route' => 'ecommerce.storefront.index'],
                'analytics' => ['name' => 'Store Analytics', 'permission' => 'ecommerce.reports', 'route' => 'ecommerce.analytics.index'],
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
                'leads' => ['name' => 'Leads', 'permission' => 'crm.manage_leads', 'route' => 'crm.leads.index'],
                'contacts' => ['name' => 'Contacts & Companies', 'permission' => 'crm.manage_contacts', 'route' => 'crm.contacts.index'],
                'pipeline' => ['name' => 'Opportunities / Pipeline', 'permission' => 'crm.manage_pipeline', 'route' => 'crm.opportunities.index'],
                'activities' => ['name' => 'Activities & Follow-ups', 'permission' => 'crm.manage_activities', 'route' => 'crm.activities.index'],
                'campaigns' => ['name' => 'Campaigns', 'permission' => 'crm.manage_campaigns', 'route' => 'crm.campaigns.index'],
                'history' => ['name' => 'Customer History', 'permission' => 'crm.view', 'route' => 'crm.history.index'],
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
                'chart' => ['name' => 'Chart of Accounts', 'permission' => 'accounting.manage_accounts', 'route' => 'accounting.chart.index'],
                'vouchers' => ['name' => 'Expense & Receipt', 'permission' => 'accounting.post_journal', 'route' => 'accounting.vouchers.index'],
                'journals' => ['name' => 'Journal Entries', 'permission' => 'accounting.view', 'route' => 'accounting.journals.index'],
                'ledger' => ['name' => 'General Ledger', 'permission' => 'accounting.view', 'route' => 'accounting.ledger.index'],
                'receivable' => ['name' => 'Accounts Receivable', 'permission' => 'accounting.view', 'route' => 'accounting.receivable.index'],
                'payable' => ['name' => 'Accounts Payable', 'permission' => 'accounting.view', 'route' => 'accounting.payable.index'],
                'banking' => ['name' => 'Bank & Cash', 'permission' => 'accounting.view', 'route' => 'accounting.banking.index'],
                'reconciliation' => ['name' => 'Reconciliation', 'permission' => 'accounting.reconcile', 'route' => 'accounting.reconciliation.index'],
                'tax' => ['name' => 'Tax Management', 'permission' => 'accounting.manage_tax', 'route' => 'accounting.tax.index'],
                'statements' => ['name' => 'Financial Statements', 'permission' => 'accounting.reports', 'route' => 'accounting.statements.trial'],
                'periods' => ['name' => 'Accounting Periods', 'permission' => 'accounting.close_period', 'route' => 'accounting.periods.index'],
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
                'hrm.manage_shifts' => 'Work shifts',
                'hrm.manage_performance' => 'Performance reviews (all employees)',
                'hrm.manage_lifecycle' => 'Onboarding & offboarding / exits',
                'hrm.manage_announcements' => 'Announcements',
            ],
            'view_permission' => 'hrm.self_service',
            'features' => [
                // Self-service — every employee with a login
                'my-profile' => ['employee_only' => true, 'name' => 'My Profile', 'permission' => 'hrm.self_service', 'route' => 'hrm.me.profile'],
                'my-attendance' => ['employee_only' => true, 'name' => 'My Attendance', 'permission' => 'hrm.self_service', 'route' => 'hrm.me.attendance'],
                'my-leaves' => ['employee_only' => true, 'name' => 'My Leaves', 'permission' => 'hrm.self_service', 'route' => 'hrm.me.leaves'],
                'my-interviews' => ['employee_only' => true, 'name' => 'My Interviews', 'permission' => 'hrm.self_service', 'route' => 'hrm.me.interviews'],
                'my-payslips' => ['employee_only' => true, 'name' => 'My Payslips', 'permission' => 'hrm.self_service', 'route' => 'hrm.me.payslips'],
                'my-reviews' => ['employee_only' => true, 'name' => 'My Reviews', 'permission' => 'hrm.self_service', 'route' => 'hrm.me.reviews'],
                'my-team' => ['employee_only' => true, 'team_only' => true, 'name' => 'My Team', 'permission' => 'hrm.self_service', 'route' => 'hrm.team'],
                // HR team
                'dashboard' => ['name' => 'HR Dashboard', 'permission' => 'hrm.view_employees', 'route' => 'hrm.dashboard'],
                'employees' => ['name' => 'Employees', 'permission' => 'hrm.view_employees', 'route' => 'hrm.employees.index'],
                'org-chart' => ['name' => 'Organization Chart', 'permission' => 'hrm.view_employees', 'route' => 'hrm.org-chart'],
                'departments' => ['name' => 'Departments', 'permission' => 'hrm.manage_org', 'route' => 'hrm.departments.index'],
                'designations' => ['name' => 'Designations', 'permission' => 'hrm.manage_org', 'route' => 'hrm.designations.index'],
                'attendance' => ['name' => 'Attendance', 'permission' => 'hrm.view_attendance', 'route' => 'hrm.attendance.index'],
                'leave-requests' => ['name' => 'Leave Requests', 'permission' => 'hrm.approve_leave', 'route' => 'hrm.leaves.index'],
                'leave-types' => ['name' => 'Leave Types', 'permission' => 'hrm.manage_leave', 'route' => 'hrm.leave-types.index'],
                'leave-allocations' => ['name' => 'Yearly Allocation', 'permission' => 'hrm.manage_leave', 'route' => 'hrm.allocations.index'],
                'holidays' => ['name' => 'Holidays', 'permission' => 'hrm.manage_leave', 'route' => 'hrm.holidays.index'],
                'shifts' => ['name' => 'Shifts', 'permission' => 'hrm.manage_shifts', 'route' => 'hrm.shifts.index'],
                'payroll' => ['name' => 'Payroll', 'permission' => 'hrm.process_payroll', 'route' => 'hrm.payroll.index'],
                'loans' => ['name' => 'Loans & Advances', 'permission' => 'hrm.process_payroll', 'route' => 'hrm.loans.index'],
                'reviews' => ['name' => 'Performance Reviews', 'permission' => 'hrm.manage_performance', 'route' => 'hrm.reviews.index'],
                'lifecycle' => ['name' => 'Onboarding & Exits', 'permission' => 'hrm.manage_lifecycle', 'route' => 'hrm.lifecycle.index'],
                'announcements' => ['name' => 'Announcements', 'permission' => 'hrm.manage_announcements', 'route' => 'hrm.announcements.index'],
                'jobs' => ['name' => 'Jobs & Careers', 'permission' => 'hrm.manage_recruitment', 'route' => 'hrm.jobs.index'],
                'applications' => ['name' => 'Applications', 'permission' => 'hrm.manage_recruitment', 'route' => 'hrm.applications.index'],
                'reports' => ['name' => 'HR Reports', 'permission' => 'hrm.reports', 'route' => 'hrm.reports'],
            ],
        ],
        'projects' => [
            'name' => 'Projects & Tasks', 'icon' => 'bi-kanban', 'scope' => 'tenant', 'group' => 'business',
            'description' => 'Kanban task board — assign tasks to employees and track progress.',
            'requires' => ['hrm'], // tasks are assigned to HRM employees
            'permissions' => [
                'projects.view' => 'See & move my own tasks',
                'projects.manage_tasks' => 'Create, assign & manage everyone\'s tasks',
            ],
            'features' => [
                'tasks' => ['name' => 'Task Board', 'permission' => 'projects.view', 'route' => 'projects.tasks.index'],
            ],
        ],
        'reports' => [
            'name' => 'Reports & Analytics', 'icon' => 'bi-bar-chart-line', 'scope' => 'tenant', 'group' => 'business',
            'description' => 'Cross-module dashboards. Shows only enabled, permitted modules.',
            'requires_any' => ['sales', 'purchase', 'inventory', 'pos', 'ecommerce', 'accounting', 'hrm'],
            'permissions' => ['reports.view' => 'View reports', 'reports.export' => 'Export reports'],
            'features' => [
                'sales' => ['name' => 'Sales & Purchase', 'route' => 'reports.sales', 'permission' => 'reports.view', 'needs_module' => ['sales', 'purchase']],
                'inventory' => ['name' => 'Inventory', 'route' => 'reports.inventory', 'permission' => 'reports.view', 'needs_module' => ['inventory']],
                'pos' => ['name' => 'POS', 'route' => 'reports.pos', 'permission' => 'reports.view', 'needs_module' => ['pos']],
                'ecommerce' => ['name' => 'E-commerce', 'route' => 'reports.ecommerce', 'permission' => 'reports.view', 'needs_module' => ['ecommerce']],
                'accounting' => ['name' => 'Accounting', 'route' => 'reports.accounting', 'permission' => 'reports.view', 'needs_module' => ['accounting']],
                'hr' => ['name' => 'HR', 'route' => 'reports.hr', 'permission' => 'reports.view', 'needs_module' => ['hrm']],
            ],
        ],
    ],
];
