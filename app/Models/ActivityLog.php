<?php

declare(strict_types=1);

namespace App\Models;

use App\Jobs\RecordActivity;
use App\Models\Concerns\UsesCentralConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Central activity log row: the "who did what, from where" record for a single
 * API request — request ip, geo-resolved country, device, url, user, response
 * status. One row per request (reads included), written in the background by
 * {@see RecordActivity}. The data changes made during that request (if any) live
 * in `audit_trails`, correlated by the shared `request_id`.
 *
 * Pinned to the central connection. The morph (`subject_type`/`subject_id`) is
 * FK-less and resolved together with `tenant_id`. Rows are immutable —
 * `created_at` only.
 *
 * @property int $id
 * @property string|null $tenant_id
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property int|null $user_id
 * @property string $action
 * @property string|null $ip_address
 * @property string|null $request_country
 * @property string|null $browser
 * @property string|null $os
 * @property string|null $device_name
 * @property float|null $latitude
 * @property float|null $longitude
 * @property string|null $session_id
 * @property string|null $request_id
 * @property string|null $method
 * @property string|null $url
 * @property int|null $status_code
 * @property Carbon|null $created_at
 */
class ActivityLog extends Model
{
    use UsesCentralConnection;

    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'subject_type',
        'subject_id',
        'user_id',
        'action',
        'ip_address',
        'request_country',
        'browser',
        'os',
        'device_name',
        'latitude',
        'longitude',
        'session_id',
        'request_id',
        'method',
        'url',
        'status_code',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subject_id' => 'integer',
            'user_id' => 'integer',
            'latitude' => 'float',
            'longitude' => 'float',
            'status_code' => 'integer',
        ];
    }

    /**
     * The data changes recorded during this request, correlated by the shared
     * `request_id` (one request -> one activity log -> many audit trails).
     *
     * @return HasMany<AuditTrail, $this>
     */
    public function auditTrails(): HasMany
    {
        return $this->hasMany(AuditTrail::class, 'request_id', 'request_id');
    }
}
