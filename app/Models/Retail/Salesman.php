<?php

declare(strict_types=1);

namespace App\Models\Retail;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Salesman extends Model
{
    use Auditable;
    use SoftDeletes;
    use UsesTenantConnection;

    protected $table = 'rt_salesmen';

    protected $fillable = ['code', 'name', 'mobile', 'email', 'address', 'opening_balance', 'commission_rate', 'is_active'];

    protected function casts(): array
    {
        return ['opening_balance' => 'float', 'commission_rate' => 'float', 'is_active' => 'boolean'];
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SalesmanPayment::class);
    }
}
