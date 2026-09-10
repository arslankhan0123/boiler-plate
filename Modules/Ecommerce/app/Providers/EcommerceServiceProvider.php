<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class EcommerceServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Ecommerce';
    protected string $nameLower = 'ecommerce';
    protected array $providers = [RouteServiceProvider::class];
}
