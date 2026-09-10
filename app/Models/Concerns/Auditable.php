<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Http\Middleware\LogApiActivity;
use App\Models\AuditTrail;
use App\Support\Audit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Config;

/**
 * Records a central audit trail for data-change events on the model. Hooks
 * Eloquent's created/updated/deleted/restored/forceDeleted events, snapshots the
 * change (old/new), and hands off to {@see Audit::record()} which resolves the
 * active tenant, the acting user, and the request context and dispatches the
 * background job.
 *
 * Everything is captured SYNCHRONOUSLY (the row is changing now, the request
 * exists now); only the write is deferred. The per-request "who/where" activity
 * log is recorded separately (see {@see LogApiActivity}) and
 * correlated to these rows by `request_id`.
 */
trait Auditable
{
    /**
     * Columns never recorded in old/new audit diffs. Defaults to the framework
     * timestamps; models holding sensitive columns OVERRIDE this (re-listing the
     * framework columns) — e.g. User adds `password`/`remember_token`.
     *
     * @return list<string>
     */
    protected static function auditExcludedColumns(): array
    {
        return ['created_at', 'updated_at', 'deleted_at'];
    }

    public static function bootAuditable(): void
    {
        // registerModelEvent() (what the magic static::created()/… helpers call)
        // is defined on every Eloquent model, so it type-checks even on models
        // that don't use SoftDeletes — restored/forceDeleted simply never fire there.
        static::registerModelEvent('created', fn (Model $model) => self::recordChange($model, AuditTrail::EVENT_CREATED));
        static::registerModelEvent('updated', fn (Model $model) => self::recordChange($model, AuditTrail::EVENT_UPDATED));
        static::registerModelEvent('deleted', fn (Model $model) => self::recordChange($model, AuditTrail::EVENT_DELETED));

        if (in_array(SoftDeletes::class, class_uses_recursive(static::class), true)) {
            static::registerModelEvent('restored', fn (Model $model) => self::recordChange($model, AuditTrail::EVENT_RESTORED));
            static::registerModelEvent('forceDeleted', fn (Model $model) => self::recordChange($model, AuditTrail::EVENT_HARD_DELETED));
        }
    }

    private static function recordChange(Model $model, string $event): void
    {
        if (! Config::boolean('audit.enabled', true)) {
            return;
        }

        // A force-delete fires `deleted` too — let `forceDeleted` own that case.
        if ($event === AuditTrail::EVENT_DELETED
            && method_exists($model, 'isForceDeleting')
            && $model->isForceDeleting()) {
            return;
        }

        [$old, $new] = self::changeValues($model, $event);

        // An "update" that only touched timestamps is not worth a row.
        if ($event === AuditTrail::EVENT_UPDATED && $new === []) {
            return;
        }

        self::dispatchAudit($model, $event, $old, $new);
    }

    /**
     * @return array{0: array<string, mixed>|null, 1: array<string, mixed>|null}
     */
    private static function changeValues(Model $model, string $event): array
    {
        $excluded = array_flip(static::auditExcludedColumns());

        return match ($event) {
            AuditTrail::EVENT_CREATED => [null, array_diff_key($model->getAttributes(), $excluded)],
            AuditTrail::EVENT_HARD_DELETED => [array_diff_key($model->getAttributes(), $excluded), null],
            AuditTrail::EVENT_UPDATED => self::splitChanges($model, $excluded),
            default => [null, null], // deleted (soft) / restored — event-only
        };
    }

    /**
     * @param  array<string, int>  $excluded
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    private static function splitChanges(Model $model, array $excluded): array
    {
        $changed = array_diff_key($model->getChanges(), $excluded);

        $old = [];
        $new = [];

        // `getOriginal()` still holds pre-save values when `updated` fires
        // (original is synced afterwards in Model::finishSave()).
        foreach (array_keys($changed) as $key) {
            $old[$key] = $model->getOriginal($key);
            $new[$key] = $model->getAttribute($key);
        }

        return [$old, $new];
    }

    /**
     * @param  array<string, mixed>|null  $old
     * @param  array<string, mixed>|null  $new
     */
    private static function dispatchAudit(Model $model, string $event, ?array $old, ?array $new): void
    {
        Audit::record($model::class, (int) $model->getKey(), $event, $old, $new);
    }
}
