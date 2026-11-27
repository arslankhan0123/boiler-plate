<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the root-level `shop.*` storefront routes: they only serve when the
 * request arrived on a verified custom domain (flagged by CustomStoreDomain).
 * On the platform's own host these paths 404 (the platform uses /store/{tenant}).
 */
class RequireCustomStore
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->attributes->get('isCustomStore'), 404);

        return $next($request);
    }
}
