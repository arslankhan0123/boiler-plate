<?php

declare(strict_types=1);

namespace App\Support;

use App\Http\Middleware\CaptureRequestContext;
use App\Models\Concerns\Auditable;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Request-scoped holder for the "from where" context of the current request —
 * ip, user-agent-derived device, url, etc. Populated once by
 * {@see CaptureRequestContext} and read by the
 * {@see Auditable} trait when it dispatches the audit job.
 *
 * Registered as a scoped singleton so the middleware and the trait share one
 * instance per request. Capturing here (synchronously, while the request exists)
 * is essential: the background worker runs later, with no request to read.
 */
class RequestContext
{
    /**
     * @var array<string, mixed>
     */
    private array $context = [];

    private bool $captured = false;

    /**
     * Snapshot the request's context. The geo lookup (country from ip) is
     * deliberately deferred to the job — only the raw ip is captured here.
     *
     * `request_id` is ALWAYS generated server-side (a fresh UUID v4) and is the
     * correlation key shared by this request's activity log and audit trails. We
     * never derive it from a client header — that would let a caller send a
     * duplicate id and conflate unrelated requests. (To support upstream
     * distributed tracing, capture the inbound header into a SEPARATE trace_id
     * field; never reuse it as the correlation key.)
     */
    public function capture(Request $request): void
    {
        $agent = (string) $request->userAgent();

        $this->context = [
            'ip_address' => $request->ip(),
            'browser' => self::browser($agent),
            'os' => self::os($agent),
            'device_name' => self::device($agent),
            'session_id' => $request->hasSession() ? $request->session()->getId() : null,
            'request_id' => (string) Str::uuid(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
        ];

        $this->captured = true;
    }

    public function captured(): bool
    {
        return $this->captured;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->context;
    }

    private static function browser(string $agent): ?string
    {
        return match (true) {
            $agent === '' => null,
            str_contains($agent, 'Edg') => 'Edge',
            str_contains($agent, 'OPR') || str_contains($agent, 'Opera') => 'Opera',
            str_contains($agent, 'Chrome') => 'Chrome',
            str_contains($agent, 'Firefox') => 'Firefox',
            str_contains($agent, 'Safari') => 'Safari',
            default => 'Other',
        };
    }

    private static function os(string $agent): ?string
    {
        return match (true) {
            $agent === '' => null,
            str_contains($agent, 'Windows') => 'Windows',
            str_contains($agent, 'iPhone') || str_contains($agent, 'iPad') || str_contains($agent, 'iOS') => 'iOS',
            str_contains($agent, 'Mac OS') || str_contains($agent, 'Macintosh') => 'macOS',
            str_contains($agent, 'Android') => 'Android',
            str_contains($agent, 'Linux') => 'Linux',
            default => 'Other',
        };
    }

    private static function device(string $agent): ?string
    {
        return match (true) {
            $agent === '' => null,
            str_contains($agent, 'iPad') || str_contains($agent, 'Tablet') => 'tablet',
            str_contains($agent, 'Mobile') || str_contains($agent, 'Android') || str_contains($agent, 'iPhone') => 'mobile',
            default => 'desktop',
        };
    }
}
