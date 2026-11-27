<?php

declare(strict_types=1);

namespace App\Models\Accounting;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Journal extends Model
{
    use Auditable, UsesTenantConnection;

    protected $table = 'acc_journals';

    protected $fillable = ['reference', 'date', 'memo', 'status', 'source', 'posted_at', 'posted_by', 'created_by'];

    protected function casts(): array
    {
        return ['date' => 'date', 'posted_at' => 'datetime'];
    }

    public function lines(): HasMany { return $this->hasMany(JournalLine::class, 'journal_id'); }

    public function isPosted(): bool { return $this->status === 'posted'; }
    public function totalDebit(): float { return (float) $this->lines->sum('debit'); }
    public function totalCredit(): float { return (float) $this->lines->sum('credit'); }
    public function isBalanced(): bool { return abs($this->totalDebit() - $this->totalCredit()) < 0.005 && $this->totalDebit() > 0; }

    public static function nextReference(): string
    {
        $n = (int) (self::query()->max('id') ?? 0) + 1;

        return 'JV-'.str_pad((string) $n, 4, '0', STR_PAD_LEFT);
    }
}
