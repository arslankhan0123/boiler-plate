<?php

declare(strict_types=1);

namespace App\Tenancy;

use App\Support\Settings;
use Stancl\Tenancy\Contracts\Tenant;
use Stancl\Tenancy\Contracts\TenancyBootstrapper;

/**
 * Applies the tenant's own timezone when tenancy is initialized.
 *
 * Runs after DatabaseTenancyBootstrapper (so the tenant DB is active and its
 * settings are readable). Each tenant stores its timezone in settings; until it
 * sets one, the app default (config/app.php → UTC) is used. Because every tenant
 * has its own database, now()/today()/Carbon and new timestamps all follow the
 * tenant's wall-clock time — so reports, attendance and records line up with it.
 */
class TimezoneBootstrapper implements TenancyBootstrapper
{
    private ?string $original = null;

    public function bootstrap(Tenant $tenant): void
    {
        $this->original = config('app.timezone');

        $tz = (string) Settings::get('timezone', '');
        if ($tz !== '' && in_array($tz, timezone_identifiers_list(), true)) {
            config(['app.timezone' => $tz]);
            date_default_timezone_set($tz);
        }
    }

    public function revert(): void
    {
        if ($this->original !== null) {
            config(['app.timezone' => $this->original]);
            date_default_timezone_set($this->original);
        }
    }
}
