<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\Billing\BillingManager;
use App\Support\ModuleAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tenant subscription gate. A tenant with no active subscription/trial AND no
 * enabled business modules is nudged to the billing page instead of hitting a
 * bare 403 on every business screen. Platform admins, guests, and the always-
 * available core screens (dashboard, billing, settings, profile, account admin)
 * are never gated — so a tenant can always reach the page where they subscribe.
 *
 * Manually-provisioned tenants (modules assigned by the master admin without a
 * subscription row) still pass, because they have enabled modules.
 */
class EnsureSubscribed
{
    /** Route-name prefixes that are always reachable. */
    private const ALLOW = [
        'dashboard', 'billing.', 'profile.', 'settings.', 'logout',
        'users.', 'roles.', 'verification.', 'password.', 'password',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Only gate real tenant users.
        if (! $user instanceof User || $user->is_platform_admin || $user->tenant_id === null) {
            return $next($request);
        }

        $name = $request->route()?->getName() ?? '';
        foreach (self::ALLOW as $prefix) {
            if ($name === $prefix || str_starts_with($name, $prefix)) {
                return $next($request);
            }
        }

        // Has access if there is an active subscription/trial OR any enabled module.
        $tenant = ModuleAccess::tenantOf($user);
        $hasAccess = BillingManager::activeFor((string) $user->tenant_id) !== null
            || ($tenant && $tenant->enabledModules() !== []);

        if (! $hasAccess) {
            if ($request->expectsJson()) {
                abort(402, 'A subscription is required to use this feature.');
            }

            return redirect()->route('billing.index')
                ->with('error', 'Please choose a plan to continue using your workspace.');
        }

        return $next($request);
    }
}
