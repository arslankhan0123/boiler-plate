<?php

/*
|--------------------------------------------------------------------------
| Module permissions (web panel)
|--------------------------------------------------------------------------
| Every key below becomes permissions "<key>.<action>" (guard: api).
| "view" controls whether the sidebar tab / page is visible.
| Add a new module here, then run: php artisan db:seed --class=MasterAdminSeeder
*/

return [
    'master_role' => 'Master Admin',

    'actions' => ['view', 'create', 'update', 'delete'],

    'modules' => [
        'users'       => ['label' => 'Users',              'icon' => 'bi-people',          'group' => 'Administration'],
        'roles'       => ['label' => 'Roles & Permissions','icon' => 'bi-shield-lock',     'group' => 'Administration'],
        'accounting'  => ['label' => 'Accounting',         'icon' => 'bi-calculator',      'group' => 'Modules'],
        'admin'       => ['label' => 'Admin',              'icon' => 'bi-gear',            'group' => 'Modules'],
        'ecommerce'   => ['label' => 'Ecommerce',          'icon' => 'bi-bag',             'group' => 'Modules'],
        'inventory'   => ['label' => 'Inventory',          'icon' => 'bi-box-seam',        'group' => 'Modules'],
        'pos'         => ['label' => 'POS',                'icon' => 'bi-shop',            'group' => 'Modules'],
        'purchase'    => ['label' => 'Purchase',           'icon' => 'bi-cart-plus',       'group' => 'Modules'],
        'sale'        => ['label' => 'Sale',               'icon' => 'bi-receipt',         'group' => 'Modules'],
        'setup'       => ['label' => 'Setup',              'icon' => 'bi-sliders',         'group' => 'Modules'],
    ],
];
