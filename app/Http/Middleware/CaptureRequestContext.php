<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Concerns\Auditable;
use App\Support\RequestContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Snapshots the current request's context (ip, device, url, …) into the
 * request-scoped {@see RequestContext} so the {@see Auditable}
 * trait can attach it to the audit job. Runs on every API request; the worker
 * that writes the log runs later, when no request exists.
 */
class CaptureRequestContext
{
    public function __construct(private readonly RequestContext $context) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $this->context->capture($request);

        return $next($request);
    }
}
