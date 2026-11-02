<?php

declare(strict_types=1);

namespace App\Models\Retail;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Retail view of the shared suppliers table. */
class Supplier extends Model
{
    use Auditable;
    use SoftDeletes;
    use UsesTenantConnection;

    protected $table = 'suppliers';

    protected $fillable = [
        'company_name', 'contact_person', 'vat_number', 'email', 'phone', 'whatsapp', 'website', 'street', 'city', 'zip',
        'opening_balance', 'is_active', 'created_by',
    ];

    protected function casts(): array
    {
        return ['opening_balance' => 'float', 'is_active' => 'boolean'];
    }

    public function getNameAttribute(): string
    {
        return (string) $this->company_name;
    }
}
