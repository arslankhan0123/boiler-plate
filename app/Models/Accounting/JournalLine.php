<?php

declare(strict_types=1);

namespace App\Models\Accounting;

use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalLine extends Model
{
    use UsesTenantConnection;

    protected $table = 'acc_journal_lines';

    protected $fillable = ['journal_id', 'account_id', 'debit', 'credit', 'memo', 'reconciled', 'reconciled_at', 'party_type', 'party_id'];

    protected function casts(): array
    {
        return ['debit' => 'float', 'credit' => 'float', 'reconciled' => 'boolean', 'reconciled_at' => 'datetime'];
    }

    public function account(): BelongsTo { return $this->belongsTo(Account::class, 'account_id'); }
    public function journal(): BelongsTo { return $this->belongsTo(Journal::class, 'journal_id'); }
}
