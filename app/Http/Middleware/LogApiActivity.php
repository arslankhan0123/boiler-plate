<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Jobs\RecordActivity;
use App\Models\Concerns\Auditable;
use App\Support\RequestContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Stancl\Tenancy\Contracts\Tenant;
use Stancl\Tenancy\Tenancy;
use Symfony\Component\HttpFoundation\Response;

/**
 * Logs ONE central activity row per API request — reads included — after the
 * response has been sent. Resolves the acting user and tenant (both available by
 * terminate time, once auth + tenancy middleware have run), derives an action
 * label from the route name, and dispatches {@see RecordActivity} to the
 * background `audit` queue so logging never slows the response.
 *
 * The matching data changes (if any) are logged separately by the
 * {@see Auditable} trait and correlated by `request_id`.
 */
class LogApiActivity
{
    public function __construct(private readonly RequestContext $context) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if (! Config::boolean('audit.enabled', true) || ! $this->context->captured()) {
            return;
        }

        $tenant = app(Tenancy::class)->tenant;
        $tenantId = $tenant instanceof Tenant ? (string) $tenant->getTenantKey() : null;

        $userId = Auth::guard('api')->id();

        RecordActivity::dispatch(
            tenantId: $tenantId,
            userId: $userId !== null ? (int) $userId : null,
            action: $this->action($request),
            statusCode: $response->getStatusCode(),
            context: $this->context->toArray(),
        )
            ->onConnection(Config::string('audit.queue.connection', 'central_database'))
            ->onQueue(Config::string('audit.queue.name', 'audit'));
    }

    /**
     * A stable label for the request: the matched route name (e.g.
     * `api.countries.update`), or `METHOD path` when no route matched (404).
     */
    private function action(Request $request): string
    {
        $name = $request->route()?->getName();

        if (is_string($name) && $name !== '') {
            return $name;
        }

        return mb_strtolower($request->method()).' '.$request->path();
    }
}
