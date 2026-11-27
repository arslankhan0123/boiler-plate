<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Models;

use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoicePayment extends Model
{
    use UsesTenantConnection;

    protected $table = 'ecommerce_invoice_payments';

    protected $fillable = ['amount', 'method', 'reference', 'paid_at', 'notes', 'created_by'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'paid_at' => 'datetime'];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
