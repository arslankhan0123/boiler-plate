<?php

/*
|--------------------------------------------------------------------------
| Tenant media (deleted together with the tenant)
|--------------------------------------------------------------------------
| When a tenant is deleted, BEFORE its database is dropped we:
|  1. read every file path stored in the columns below (in that tenant's DB)
|     and delete exactly those files, and
|  2. delete the tenant's own upload folder (tenants/{id}) on each disk.
|
| Only list columns that hold files UPLOADED BY THE TENANT. Do not list
| columns that point at shared/central files (e.g. countries.flag_image,
| which is copied from the central lookup master).
*/

return [
    'disks' => ['public', 'local'],

    // New uploads go here (see tenant_upload_path() helper).
    'folder' => 'tenants/{id}',

    'columns' => [
        'ecommerce_categories' => ['image'],
        'ecommerce_products' => ['feature_image'],
        'ecommerce_product_images' => ['path'],
    ],
];
