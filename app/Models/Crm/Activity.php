<?php

declare(strict_types=1);

namespace App\Models\Crm;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\UsesTenantConnection;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activity extends Model
{
    use Auditable, UsesTenantConnection;

    protected $table = 'crm_activities';

    public const TYPES = ['call' => 'Call', 'email' => 'Email', 'meeting' => 'Meeting', 'task' => 'Task'];
    public const ICONS = ['call' => 'telephone', 'email' => 'envelope', 'meeting' => 'people', 'task' => 'check2-square'];
    public const RELATED = ['lead' => 'Lead', 'opportunity' => 'Opportunity', 'contact' => 'Contact', 'company' => 'Company'];

    protected $fillable = ['type', 'subject', 'due_at', 'done', 'done_at', 'related_type', 'related_id', 'owner_id', 'notes', 'created_by'];

    protected function casts(): array { return ['due_at' => 'datetime', 'done' => 'boolean', 'done_at' => 'datetime']; }

    public function owner(): BelongsTo { return $this->belongsTo(User::class, 'owner_id'); }
    public function typeLabel(): string { return self::TYPES[$this->type] ?? $this->type; }
    public function icon(): string { return self::ICONS[$this->type] ?? 'dot'; }
    public function isOverdue(): bool { return ! $this->done && $this->due_at && $this->due_at->isPast(); }
}
