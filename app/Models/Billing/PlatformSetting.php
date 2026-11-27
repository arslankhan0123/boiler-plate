<?php

declare(strict_types=1);

namespace App\Models\Billing;

use Illuminate\Database\Eloquent\Model;

/**
 * Central key/value row for platform-level settings (master admin's own Stripe /
 * PayPal credentials, etc.). Read/written through App\Support\Billing\
 * PlatformSettings — not usually touched directly.
 *
 * @property string $key
 * @property string|null $value
 */
class PlatformSetting extends Model
{
    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = [];

    protected $table = 'platform_settings';

    public function getConnectionName(): ?string
    {
        return config('tenancy.database.central_connection', 'central');
    }
}
