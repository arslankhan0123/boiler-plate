<?php

declare(strict_types=1);

namespace App\Models\Crm;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\UsesTenantConnection;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use Auditable, SoftDeletes, UsesTenantConnection;

    protected $table = 'crm_leads';

    public const STATUSES = ['new' => 'New', 'contacted' => 'Contacted', 'qualified' => 'Qualified', 'unqualified' => 'Unqualified', 'converted' => 'Converted'];
    public const TONES = ['new' => 'primary', 'contacted' => 'info', 'qualified' => 'success', 'unqualified' => 'muted', 'converted' => 'success'];

    protected $fillable = ['name', 'company', 'email', 'phone', 'source', 'status', 'value', 'owner_id', 'notes',
        'converted_contact_id', 'converted_opportunity_id', 'converted_at', 'created_by'];

    protected function casts(): array { return ['value' => 'float', 'converted_at' => 'datetime']; }

    public function owner(): BelongsTo { return $this->belongsTo(User::class, 'owner_id'); }
    public function statusLabel(): string { return self::STATUSES[$this->status] ?? $this->status; }
    public function statusTone(): string { return self::TONES[$this->status] ?? 'muted'; }
}
