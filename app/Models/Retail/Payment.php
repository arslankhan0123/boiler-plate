<?php

declare(strict_types=1);

namespace App\Models\Retail;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Money in / out for customers and suppliers (invoice payments, opening balance, advances, refunds). */
class Payment extends Model
{
    use Auditable;
    use UsesTenantConnection;

    public const KINDS = ['payment' => 'Payment', 'opening' => 'Opening balance', 'advance' => 'Advance', 'refund' => 'Refund'];

    protected $table = 'rt_payments';

    protected $fillable = ['code', 'direction', 'kind', 'party_type', 'party_id', 'document_id', 'date', 'method', 'amount', 'change_return', 'cheque_no', 'cheque_date', 'cheque_status', 'note', 'created_by'];

    protected function casts(): array
    {
        return ['date' => 'date', 'cheque_date' => 'date', 'amount' => 'float', 'change_return' => 'float'];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class)->withTrashed();
    }

    public function party(): BelongsTo
    {
        return $this->party_type === 'supplier'
            ? $this->belongsTo(Supplier::class, 'party_id')->withTrashed()
            : $this->belongsTo(Customer::class, 'party_id')->withTrashed();
    }
}
