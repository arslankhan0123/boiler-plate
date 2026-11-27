<?php

declare(strict_types=1);

namespace Modules\Pos\Models;

use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;

class PosPayment extends Model
{
    use UsesTenantConnection;

    protected $table = 'pos_payments';

    protected $fillable = ['sale_id', 'method', 'amount', 'reference', 'received_at'];
}
