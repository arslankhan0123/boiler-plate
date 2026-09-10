<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ActivityLog;
use App\Services\GeoIpService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;

/**
 * Persists ONE central {@see ActivityLog} row per API request — the
 * "who did what, from where" record (user, ip, geo-resolved country, device,
 * url, response status), reads included. Resolves the country-of-request from
 * the captured IP. The data changes made during the same request (if any) live
 * in {@see AuditTrail}, correlated by the shared `request_id`.
 *
 * Dispatched from the terminating middleware after the response is sent, on the
 * same `audit` queue, so logging never slows the response.
 */
class RecordActivity implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [10, 30, 60];

    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public ?string $tenantId,
        public ?int $userId,
        public string $action,
        public ?int $statusCode,
        public array $context,
    ) {}

    public function handle(GeoIpService $geoip): void
    {
        if (! Config::boolean('audit.enabled', true)) {
            return;
        }

        $ip = isset($this->context['ip_address']) ? (string) $this->context['ip_address'] : null;

        ActivityLog::create([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'action' => $this->action,
            'ip_address' => $ip,
            'request_country' => $geoip->country($ip),
            'browser' => $this->context['browser'] ?? null,
            'os' => $this->context['os'] ?? null,
            'device_name' => $this->context['device_name'] ?? null,
            'session_id' => $this->context['session_id'] ?? null,
            'request_id' => $this->context['request_id'] ?? null,
            'method' => $this->context['method'] ?? null,
            'url' => $this->context['url'] ?? null,
            'status_code' => $this->statusCode,
        ]);
    }
}
