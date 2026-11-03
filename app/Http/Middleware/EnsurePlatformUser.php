<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts a route to platform (central) accounts — tenant-less users, which in
 * this app are the Master Admin and any other platform-level account. This is
 * the same boundary the sidebar uses to show platform modules (tenant_id === null),
 * so platform screens authorize consistently with what the user can see.
 *
 * Unlike EnsurePlatformAdmin (which requires the is_platform_admin flag, used for
 * the API), this works for the role-based Master Admin seeded with tenant_id null.
 * Tenant users (tenant_id set) and guests get a 403.
 */
class EnsurePlatformUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless(
            $user instanceof User && $user->tenant_id === null,
            403,
            'Platform administrator access required.',
        );

        return $next($request);
    }
}
