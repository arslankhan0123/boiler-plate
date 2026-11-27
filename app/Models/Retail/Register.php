<?php

declare(strict_types=1);

namespace App\Models\Retail;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\UsesTenantConnection;
use App\Models\Inventory\Warehouse;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A POS cash-drawer session (shift). */
class Register extends Model
{
    use Auditable;
    use UsesTenantConnection;

    protected $table = 'rt_registers';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime', 'closed_at' => 'datetime',
            'opening_cash' => 'float', 'counted_cash' => 'float', 'expected_cash' => 'float',
            'cash_sales' => 'float', 'cash_refunds' => 'float', 'other_sales' => 'float',
            'over_short' => 'float', 'cash_in' => 'float', 'cash_out' => 'float',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(RegisterMovement::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    /** The current user's open register, if any. */
    public static function openFor(int $userId): ?self
    {
        return self::query()->where('status', 'open')->where('opened_by', $userId)->latest('id')->first();
    }

    /** Live cash expected in the drawer right now (opening + cash in/sales - cash out/refunds). */
    public function liveExpected(): array
    {
        $payments = Payment::query()->whereIn('document_id', Document::query()->where('register_id', $this->id)->select('id'));
        $cashSales = (float) (clone $payments)->where('direction', 'in')->where('method', 'Cash')->sum('amount');
        $cashRefunds = (float) (clone $payments)->where('direction', 'out')->where('method', 'Cash')->sum('amount');
        $otherSales = (float) (clone $payments)->where('direction', 'in')->where('method', '!=', 'Cash')->sum('amount');

        return [
            'cash_sales' => $cashSales, 'cash_refunds' => $cashRefunds, 'other_sales' => $otherSales,
            'cash_in' => (float) $this->movements()->where('direction', 'in')->sum('amount'),
            'cash_out' => (float) $this->movements()->where('direction', 'out')->sum('amount'),
            'expected' => round($this->opening_cash + $cashSales - $cashRefunds + (float) $this->movements()->where('direction', 'in')->sum('amount') - (float) $this->movements()->where('direction', 'out')->sum('amount'), 2),
            'invoices' => Document::query()->where('register_id', $this->id)->where('type', 'invoice')->count(),
            'total_sales' => (float) Document::query()->where('register_id', $this->id)->where('type', 'invoice')->sum('grand_total'),
        ];
    }
}
