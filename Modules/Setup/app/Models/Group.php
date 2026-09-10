<?php

declare(strict_types=1);

namespace Modules\Setup\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\TracksSoftDeleteUser;
use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Modules\Setup\Database\Factories\GroupFactory;

/**
 * A tenant-defined group — a hierarchical classification node. Pure tenant-owned
 * custom data (NOT picked from any central master). May nest under another group
 * via `parent_id`.
 *
 * @property int $id
 * @property string $name
 * @property string|null $code
 * @property string|null $description
 * @property string|null $color
 * @property int|null $parent_id
 * @property array<string, mixed>|null $data
 * @property bool $is_active
 * @property bool $is_default
 * @property int $sort_order
 * @property int|null $created_by
 * @property int|null $deleted_by_user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class Group extends Model
{
    use Auditable;

    /** @use HasFactory<GroupFactory> */
    use HasFactory;

    use SoftDeletes;
    use TracksSoftDeleteUser;
    use UsesTenantConnection;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'code',
        'description',
        'color',
        'parent_id',
        'data',
        'is_active',
        'is_default',
        'sort_order',
        'created_by',
    ];

    /**
     * The parent group this group nests under (optional).
     *
     * @return BelongsTo<Group, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * The child groups nested under this group.
     *
     * @return HasMany<Group, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'parent_id' => 'integer',
            'data' => 'array',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'sort_order' => 'integer',
            'created_by' => 'integer',
            'deleted_by_user_id' => 'integer',
        ];
    }

    protected static function newFactory(): GroupFactory
    {
        return GroupFactory::new();
    }
}
