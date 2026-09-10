<?php

declare(strict_types=1);

namespace App\Services;

use GeoIp2\Database\Reader;
use Illuminate\Support\Facades\Config;
use Throwable;

/**
 * Resolves the country of a request IP from a self-hosted MaxMind GeoLite2
 * database. Intentionally pluggable and fail-soft: if geoip is disabled, the
 * `.mmdb` file is missing, or the lookup fails (private/unknown IP), it returns
 * null — logging continues, `request_country` is just left empty until a
 * licensed database is dropped in at the configured path.
 */
class GeoIpService
{
    private ?Reader $reader = null;

    private bool $resolved = false;

    /**
     * The English country name for an IP (e.g. "Pakistan"), or null when it
     * cannot be resolved.
     */
    public function country(?string $ip): ?string
    {
        $reader = $this->reader();

        if ($reader === null || $ip === null || $ip === '') {
            return null;
        }

        try {
            $country = $reader->country($ip)->country;

            return $country->name ?? $country->isoCode;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Lazily open the database once. Returns null when geoip is unavailable.
     */
    private function reader(): ?Reader
    {
        if ($this->resolved) {
            return $this->reader;
        }

        $this->resolved = true;

        if (! Config::boolean('audit.geoip.enabled', true)) {
            return null;
        }

        $path = Config::string('audit.geoip.database', '');

        if ($path === '' || ! is_file($path)) {
            return null;
        }

        try {
            $this->reader = new Reader($path);
        } catch (Throwable) {
            $this->reader = null;
        }

        return $this->reader;
    }
}
