<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected string $name = 'Ecommerce';

    public function map(): void
    {
        Route::middleware('api')->prefix('api')->name('api.')->group(module_path($this->name, '/routes/api.php'));
        Route::middleware('web')->group(module_path($this->name, '/routes/web.php'));
    }
}
