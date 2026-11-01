<?php

declare(strict_types=1);

namespace App\Models\Settings;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use Auditable;
    use UsesTenantConnection;

    protected $table = 'branches';

    protected $fillable = ['name', 'code', 'phone', 'email', 'address', 'city', 'manager_id', 'is_head_office', 'is_active'];

    protected function casts(): array
    {
        return ['is_head_office' => 'boolean', 'is_active' => 'boolean'];
    }

    public function manager(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\Modules\Setup\Models\Employee::class, 'manager_id');
    }
}
