<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Support\Authorization\ColumnExposure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-role override of a catalogued column's exposure (the visibility matrix).
 * Lives in the TENANT database. Grants are additive: a row can only raise a
 * column's effective exposure above its catalog base, never lower it.
 *
 * @property int $id
 * @property int $role_id
 * @property int $resource_column_id
 * @property ColumnExposure $exposure
 */
class ColumnVisibility extends Model
{
    use Auditable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'role_id',
        'resource_column_id',
        'exposure',
    ];

    /**
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * @return BelongsTo<ResourceColumn, $this>
     */
    public function resourceColumn(): BelongsTo
    {
        return $this->belongsTo(ResourceColumn::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'exposure' => ColumnExposure::class,
        ];
    }
}
