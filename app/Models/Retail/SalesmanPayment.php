<?php

declare(strict_types=1);

namespace App\Models\Retail;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;

class SalesmanPayment extends Model
{
    use Auditable;
    use UsesTenantConnection;

    protected $table = 'rt_salesman_payments';

    protected $fillable = ['salesman_id', 'kind', 'date', 'amount', 'method', 'note', 'created_by'];

    protected function casts(): array
    {
        return ['date' => 'date', 'amount' => 'float'];
    }
}
