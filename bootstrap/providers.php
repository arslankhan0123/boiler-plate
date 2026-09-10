<?php

use App\Providers\AppServiceProvider;
use App\Providers\ResponseServiceProvider;
use App\Providers\TenancyServiceProvider;

return [
    AppServiceProvider::class,
    ResponseServiceProvider::class,
    TenancyServiceProvider::class,
];
