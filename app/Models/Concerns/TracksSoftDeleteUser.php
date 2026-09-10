<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Records WHO soft-deleted a row in `deleted_by_user_id` (alongside SoftDeletes'
 * `deleted_at`, the WHEN), and clears it on restore.
 *
 * Project-wide standard: every soft-deleted table also carries a FK-less
 * `deleted_by_user_id` (the acting CENTRAL user id; a tenant->central FK is
 * impossible, same rule as `created_by`).
 *
 * Implemented via model events rather than overriding SoftDeletes::runSoftDelete()
 * — overriding it from a trait would collide with the SoftDeletes trait and force
 * per-model `insteadof` resolution. On soft delete we stamp the column with one
 * extra targeted UPDATE (the soft-delete UPDATE itself only writes deleted_at);
 * force-deletes are skipped (the row is gone). Requires the SoftDeletes trait and
 * a nullable `deleted_by_user_id` column.
 */
trait TracksSoftDeleteUser
{
    protected static function bootTracksSoftDeleteUser(): void
    {
        static::deleted(function (Model $model): void {
            // Force delete removes the row — nothing to stamp.
            if (method_exists($model, 'isForceDeleting') && $model->isForceDeleting()) {
                return;
            }

            $userId = Auth::guard('api')->id();
            $model->setAttribute('deleted_by_user_id', $userId);

            $model->newQueryWithoutScopes()
                ->whereKey($model->getKey())
                ->update(['deleted_by_user_id' => $userId]);

            $model->syncOriginalAttribute('deleted_by_user_id');
        });

        static::restoring(function (Model $model): void {
            $model->setAttribute('deleted_by_user_id', null);
        });
    }
}
