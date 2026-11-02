<?php

declare(strict_types=1);

namespace App\Models\Crm;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\UsesTenantConnection;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Opportunity extends Model
{
    use Auditable, SoftDeletes, UsesTenantConnection;

    protected $table = 'crm_opportunities';

    public const STAGES = ['qualification' => 'Qualification', 'proposal' => 'Proposal', 'negotiation' => 'Negotiation', 'won' => 'Won', 'lost' => 'Lost'];
    public const OPEN_STAGES = ['qualification', 'proposal', 'negotiation'];
    public const TONES = ['qualification' => 'info', 'proposal' => 'primary', 'negotiation' => 'warning', 'won' => 'success', 'lost' => 'danger'];

    protected $fillable = ['title', 'company_id', 'contact_id', 'stage', 'amount', 'probability', 'close_date',
        'source', 'owner_id', 'notes', 'lost_reason', 'closed_at', 'created_by'];

    protected function casts(): array { return ['amount' => 'float', 'probability' => 'int', 'close_date' => 'date', 'closed_at' => 'datetime']; }

    public function company(): BelongsTo { return $this->belongsTo(Company::class, 'company_id'); }
    public function contact(): BelongsTo { return $this->belongsTo(Contact::class, 'contact_id'); }
    public function owner(): BelongsTo { return $this->belongsTo(User::class, 'owner_id'); }

    public function stageLabel(): string { return self::STAGES[$this->stage] ?? $this->stage; }
    public function stageTone(): string { return self::TONES[$this->stage] ?? 'muted'; }
    public function isOpen(): bool { return in_array($this->stage, self::OPEN_STAGES, true); }
    public function weighted(): float { return round($this->amount * $this->probability / 100, 2); }
}
