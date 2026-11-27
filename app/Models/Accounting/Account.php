<?php

declare(strict_types=1);

namespace App\Models\Accounting;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use Auditable, UsesTenantConnection;

    protected $table = 'acc_accounts';

    public const TYPES = ['asset' => 'Asset', 'liability' => 'Liability', 'equity' => 'Equity', 'income' => 'Income', 'expense' => 'Expense'];
    // Accounts whose balance increases on the debit side.
    public const DEBIT_TYPES = ['asset', 'expense'];
    public const TONES = ['asset' => 'primary', 'liability' => 'warning', 'equity' => 'info', 'income' => 'success', 'expense' => 'danger'];

    protected $fillable = ['code', 'name', 'type', 'parent_id', 'is_group', 'is_bank', 'control_type',
        'opening_balance', 'is_active', 'description', 'created_by'];

    protected function casts(): array
    {
        return ['is_group' => 'boolean', 'is_bank' => 'boolean', 'is_active' => 'boolean', 'opening_balance' => 'float'];
    }

    public function parent(): BelongsTo { return $this->belongsTo(self::class, 'parent_id'); }
    public function children(): HasMany { return $this->hasMany(self::class, 'parent_id'); }
    public function lines(): HasMany { return $this->hasMany(JournalLine::class, 'account_id'); }

    public function typeLabel(): string { return self::TYPES[$this->type] ?? $this->type; }
    public function typeTone(): string { return self::TONES[$this->type] ?? 'muted'; }
    public function isDebitNormal(): bool { return in_array($this->type, self::DEBIT_TYPES, true); }
    /** Signed sign for the account's normal side: +1 debit-normal, -1 credit-normal. */
    public function sign(): int { return $this->isDebitNormal() ? 1 : -1; }
}
