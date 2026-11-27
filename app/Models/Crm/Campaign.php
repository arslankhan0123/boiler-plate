<?php

declare(strict_types=1);

namespace App\Models\Crm;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    use Auditable, UsesTenantConnection;

    protected $table = 'crm_campaigns';

    public const CHANNELS = ['email' => 'Email', 'sms' => 'SMS', 'social' => 'Social', 'event' => 'Event', 'other' => 'Other'];
    public const STATUSES = ['planned' => 'Planned', 'active' => 'Active', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];
    public const TONES = ['planned' => 'muted', 'active' => 'success', 'completed' => 'info', 'cancelled' => 'danger'];

    protected $fillable = ['name', 'channel', 'status', 'start_date', 'end_date', 'budget', 'notes', 'created_by'];

    protected function casts(): array { return ['start_date' => 'date', 'end_date' => 'date', 'budget' => 'float']; }

    public function channelLabel(): string { return self::CHANNELS[$this->channel] ?? $this->channel; }
    public function statusLabel(): string { return self::STATUSES[$this->status] ?? $this->status; }
    public function statusTone(): string { return self::TONES[$this->status] ?? 'muted'; }
}
