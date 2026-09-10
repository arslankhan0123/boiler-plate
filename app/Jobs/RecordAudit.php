<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AuditTrail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;

/**
 * Persists one data-change event (old/new values) to the central
 * {@see AuditTrail} table, in the background. The payload is fully
 * self-contained — no models, no tenant context, no request needed at run time.
 * The "who/where" of the originating request is logged separately, once per
 * request, by {@see RecordActivity}; the two are correlated by `request_id`.
 *
 * Runs on its own `audit` queue (a dedicated Horizon supervisor in production).
 * Dispatched with `->afterCommit()` so it is only enqueued once the originating
 * DB transaction commits — it never logs a change that rolled back.
 */
class RecordAudit implements ShouldQueue
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
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public string $event,
        public string $auditableType,
        public int $auditableId,
        public ?string $tenantId,
        public ?int $userId,
        public ?array $oldValues,
        public ?array $newValues,
        public array $context,
    ) {}

    public function handle(): void
    {
        if (! Config::boolean('audit.enabled', true)) {
            return;
        }

        AuditTrail::create([
            'tenant_id' => $this->tenantId,
            'auditable_type' => $this->auditableType,
            'auditable_id' => $this->auditableId,
            'event' => $this->event,
            'old_values' => $this->oldValues,
            'new_values' => $this->newValues,
            'user_id' => $this->userId,
            'request_id' => isset($this->context['request_id']) ? (string) $this->context['request_id'] : null,
        ]);
    }
}
