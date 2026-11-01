<?php

declare(strict_types=1);

namespace App\Models\Settings;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use Auditable;
    use UsesTenantConnection;

    protected $table = 'tenant_settings';

    protected $fillable = ['key', 'value'];

    protected function casts(): array
    {
        return [];
    }

}
