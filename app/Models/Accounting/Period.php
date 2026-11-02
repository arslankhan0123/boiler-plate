<?php

declare(strict_types=1);

namespace App\Models\Accounting;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;

class Period extends Model
{
    use Auditable, UsesTenantConnection;

    protected $table = 'acc_periods';

    protected $fillable = ['name', 'start_date', 'end_date', 'status', 'closed_at', 'closed_by', 'created_by'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date', 'closed_at' => 'datetime'];
    }

    public function isClosed(): bool { return $this->status === 'closed'; }

    /** Is the given date inside a closed period? */
    public static function isDateLocked(string $date): bool
    {
        return self::query()->where('status', 'closed')
            ->whereDate('start_date', '<=', $date)->whereDate('end_date', '>=', $date)->exists();
    }
}
