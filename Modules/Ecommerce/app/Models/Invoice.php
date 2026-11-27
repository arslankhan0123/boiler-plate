<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use Auditable, UsesTenantConnection;

    protected $table = 'ecommerce_invoices';

    protected $fillable = ['order_id', 'number', 'status', 'issued_at', 'due_at', 'subtotal', 'tax_total', 'discount_total', 'grand_total', 'paid_total', 'notes', 'created_by'];

    protected function casts(): array
    {
        return ['issued_at' => 'date', 'due_at' => 'date', 'subtotal' => 'decimal:2', 'tax_total' => 'decimal:2', 'discount_total' => 'decimal:2', 'grand_total' => 'decimal:2', 'paid_total' => 'decimal:2'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class);
    }
}
