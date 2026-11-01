<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\ModuleAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route guard: `module:pos` or `module:pos,returns` (module + feature).
 * Blocks the request when the module/feature is not enabled for the
 * user's tenant — regardless of the user's role permissions.
 */
class EnsureModuleEnabled
{
    public function handle(Request $request, Closure $next, string $module, ?string $feature = null): Response
    {
        $user = $request->user();

        $allowed = $user instanceof User && ($feature
            ? ModuleAccess::featureAvailable($user, $module, $feature)
            : ModuleAccess::moduleAvailable($user, $module));

        abort_unless($allowed, 403, 'This module is not enabled for your organization.');

        return $next($request);
    }
}
