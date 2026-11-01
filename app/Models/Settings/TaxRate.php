<?php

declare(strict_types=1);

namespace App\Models\Settings;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;

class TaxRate extends Model
{
    use Auditable;
    use UsesTenantConnection;

    protected $table = 'tax_rates';

    protected $fillable = ['name', 'rate', 'type', 'is_default', 'is_active'];

    protected function casts(): array
    {
        return ['rate' => 'float', 'is_default' => 'boolean', 'is_active' => 'boolean'];
    }

}
