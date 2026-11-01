<?php

declare(strict_types=1);

namespace App\Models\Settings;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use Auditable;
    use UsesTenantConnection;

    protected $table = 'payment_methods';

    protected $fillable = ['name', 'type', 'details', 'is_active', 'sort'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public const TYPES = ['cash' => 'Cash', 'bank' => 'Bank transfer', 'card' => 'Card', 'wallet' => 'Mobile wallet (JazzCash / Easypaisa)', 'cod' => 'Cash on delivery', 'online' => 'Online gateway'];
}
