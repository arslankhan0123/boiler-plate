<?php

declare(strict_types=1);

namespace App\Models\Hrm;

use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Setup\Models\Employee;

class PayslipLine extends Model
{
    use UsesTenantConnection;
    use \App\Models\Concerns\Auditable;

    protected $table = 'hrm_payslip_lines';

    protected $fillable = ['payslip_id', 'type', 'label', 'amount', 'loan_id'];

    protected function casts(): array
    {
        return ['amount' => 'float'];
    }
}
