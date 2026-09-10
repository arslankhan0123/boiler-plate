<?php

declare(strict_types=1);

namespace App\Models;

use App\Jobs\RecordAudit;
use App\Models\Concerns\UsesCentralConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Central audit trail row: the before/after of a single data-change event on an
 * auditable model. Written in the background by {@see RecordAudit}.
 *
 * Pinned to the central connection so the worker writes the central DB even when
 * it runs without (or in a different) tenant context. The morph
 * (`auditable_type`/`auditable_id`) is FK-less and is resolved together with
 * `tenant_id` (the record may live in a tenant DB). Rows are immutable —
 * `created_at` only.
 *
 * @property int $id
 * @property string|null $tenant_id
 * @property string $auditable_type
 * @property int $auditable_id
 * @property string $event
 * @property array<string, mixed>|null $old_values
 * @property array<string, mixed>|null $new_values
 * @property string|null $reason
 * @property int|null $user_id
 * @property string|null $request_id
 * @property Carbon|null $created_at
 */
class AuditTrail extends Model
{
    use UsesCentralConnection;

    public const UPDATED_AT = null;

    public const EVENT_CREATED = 'created';

    public const EVENT_UPDATED = 'updated';

    public const EVENT_DELETED = 'deleted';

    public const EVENT_RESTORED = 'restored';

    public const EVENT_HARD_DELETED = 'hardDeleted';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'auditable_type',
        'auditable_id',
        'event',
        'old_values',
        'new_values',
        'reason',
        'user_id',
        'request_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'auditable_id' => 'integer',
            'old_values' => 'array',
            'new_values' => 'array',
            'user_id' => 'integer',
        ];
    }

    /**
     * The request-level activity log this data change belongs to, correlated by
     * the shared `request_id` (one request -> one activity log -> many audits).
     *
     * @return BelongsTo<ActivityLog, $this>
     */
    public function activityLog(): BelongsTo
    {
        return $this->belongsTo(ActivityLog::class, 'request_id', 'request_id');
    }
}
