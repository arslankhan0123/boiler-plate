<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Domain;
use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Tenancy;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serves a tenant's public storefront on its own verified custom domain.
 *
 * On the platform's own host(s) this is a no-op. On any other host it looks the
 * domain up in the central `domains` table; if verified and the tenant is active
 * it initializes tenancy and lets the `shop.*` root routes serve the store. Admin
 * and auth paths are blocked on custom domains (store only). Unknown domains 404.
 */
class CustomStoreDomain
{
    public function __construct(private readonly Tenancy $tenancy) {}

    /** Path prefixes allowed on a custom storefront domain. */
    private const STORE_PATHS = ['', 'cart', 'checkout', 'p', 'order', 'webhook'];

    public function handle(Request $request, Closure $next): Response
    {
        $host = strtolower($request->getHost());

        if ($this->isCentralHost($host)) {
            return $next($request);
        }

        // Resolve the custom domain → tenant. If the table isn't there yet
        // (before migration) behave exactly as before this feature.
        try {
            $domain = Domain::query()->where('domain', $host)->whereNotNull('verified_at')->first();
        } catch (\Throwable) {
            return $next($request);
        }

        if (! $domain) {
            abort(404, 'No store is configured for this address.');
        }

        $tenant = Tenant::query()->find($domain->tenant_id);
        abort_unless($tenant && $tenant->is_active, 404, 'This store is unavailable.');

        // Store-only: block admin, auth, API and the internal /store/{id} paths.
        $first = explode('/', trim($request->path(), '/'))[0] ?? '';
        if (! in_array($first, self::STORE_PATHS, true)) {
            abort(404);
        }

        $this->tenancy->initialize($tenant);
        $request->attributes->set('isCustomStore', true);
        $request->attributes->set('customStoreTenant', $tenant);

        return $next($request);
    }

    private function isCentralHost(string $host): bool
    {
        $central = array_map('strtolower', (array) config('tenancy.central_domains', []));
        foreach (['app.url'] as $cfg) {
            $h = parse_url((string) config($cfg), PHP_URL_HOST);
            if ($h) {
                $central[] = strtolower($h);
            }
        }

        return in_array($host, $central, true);
    }
}
