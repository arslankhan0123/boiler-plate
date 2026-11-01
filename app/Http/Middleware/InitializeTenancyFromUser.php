<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Tenancy;
use Symfony\Component\HttpFoundation\Response;

/**
 * Initializes tenancy from the authenticated user (central identity directory).
 *
 * Must run AFTER `auth:api`. For a tenant-bound user it switches the database
 * connection to that user's tenant database; platform admins and unauthenticated
 * requests are left in the central context.
 */
class InitializeTenancyFromUser
{
    public function __construct(private readonly Tenancy $tenancy) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && ! $user->is_platform_admin && $user->tenant_id !== null) {
            $tenant = Tenant::query()->find($user->tenant_id);

            if ($tenant === null || ! $tenant->is_active) {
                // Web panel: sign out and send back to login with a toast.
                if (! $request->expectsJson() && ! $request->is('api/*') && $request->hasSession()) {
                    auth()->guard('web')->logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return redirect()->route('login')->with('toast', [
                        'type' => 'error',
                        'title' => $tenant ? 'Account inactive' : 'Organization not found',
                        'message' => $tenant
                            ? "Your organization ({$tenant->name}) has been deactivated. Please contact the administrator."
                            : 'Your organization no longer exists. Please contact the administrator.',
                    ]);
                }

                abort(403, 'Your tenant is unavailable.');
            }

            $this->tenancy->initialize($tenant);
        }

        return $next($request);
    }
}
