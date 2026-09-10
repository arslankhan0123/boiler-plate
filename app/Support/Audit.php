<?php

declare(strict_types=1);

namespace App\Support;

use App\Jobs\RecordAudit;
use App\Models\Concerns\Auditable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Stancl\Tenancy\Contracts\Tenant;
use Stancl\Tenancy\Tenancy;

/**
 * Records a single data-change event to the central audit trail for changes that
 * do NOT fire Eloquent model events — e.g. Spatie permission/role pivot syncs and
 * deliberate mass deletes. Mirrors the {@see Auditable} trait: resolves the
 * active tenant, the acting user, and the request context, then dispatches
 * {@see RecordAudit} to the background queue. The {@see Auditable} trait also
 * routes through here, so all audit dispatching lives in one place.
 */
class Audit
{
    /**
     * @param  array<string, mixed>|null  $old
     * @param  array<string, mixed>|null  $new
     */
    public static function record(string $auditableType, int $auditableId, string $event, ?array $old, ?array $new): void
    {
        if (! Config::boolean('audit.enabled', true)) {
            return;
        }

        $tenant = app(Tenancy::class)->tenant;
        $tenantId = $tenant instanceof Tenant ? (string) $tenant->getTenantKey() : null;

        $userId = Auth::guard('api')->id();

        RecordAudit::dispatch(
            event: $event,
            auditableType: $auditableType,
            auditableId: $auditableId,
            tenantId: $tenantId,
            userId: $userId !== null ? (int) $userId : null,
            oldValues: $old,
            newValues: $new,
            context: app(RequestContext::class)->toArray(),
        )
            ->onConnection(Config::string('audit.queue.connection', 'central_database'))
            ->onQueue(Config::string('audit.queue.name', 'audit'))
            ->afterCommit();
    }
}
