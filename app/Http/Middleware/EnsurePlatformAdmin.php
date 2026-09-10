<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts a route to platform super-admins (tenant-less, `is_platform_admin`).
 *
 * Used for central, cross-tenant operations such as tenant onboarding. Must run
 * after `auth:api`. Tenant users (and unauthenticated requests) get a 403.
 */
class EnsurePlatformAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless(
            $user instanceof User && $user->is_platform_admin,
            403,
            'Platform administrator access required.',
        );

        return $next($request);
    }
}
