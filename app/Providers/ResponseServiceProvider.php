<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\JsonResponseService;
use Illuminate\Support\ServiceProvider;

class ResponseServiceProvider extends ServiceProvider
{
    /**
     * Bind the response service as a singleton under both its class name and the
     * 'api-response' alias used by the ApiResponse facade, so it is resolvable
     * and mockable in tests.
     */
    public function register(): void
    {
        $this->app->singleton(JsonResponseService::class, fn (): JsonResponseService => new JsonResponseService);
        $this->app->alias(JsonResponseService::class, 'api-response');
    }

    public function boot(): void
    {
        //
    }
}
