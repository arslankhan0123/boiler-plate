<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Authorization\ColumnExposure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Catalog entry: one exposable column of a resource, with its base exposure.
 * Lives in the TENANT database (uses the default connection, which is the
 * tenant connection once tenancy is initialized).
 *
 * @property int $id
 * @property string $resource
 * @property string $column
 * @property string|null $label
 * @property ColumnExposure $exposure
 * @property bool $is_sensitive
 * @property int $sort_order
 */
class ResourceColumn extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'resource',
        'column',
        'label',
        'exposure',
        'is_sensitive',
        'sort_order',
    ];

    /**
     * @return HasMany<ColumnVisibility, $this>
     */
    public function visibilities(): HasMany
    {
        return $this->hasMany(ColumnVisibility::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'exposure' => ColumnExposure::class,
            'is_sensitive' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
