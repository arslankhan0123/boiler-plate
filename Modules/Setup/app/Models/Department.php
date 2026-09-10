<?php

declare(strict_types=1);

namespace Modules\Setup\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\TracksSoftDeleteUser;
use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Modules\Setup\Database\Factories\DepartmentFactory;

/**
 * A tenant-defined department. Pure tenant-owned custom data — a flat list, NOT
 * picked from any central master.
 *
 * @property int $id
 * @property string $name
 * @property string|null $code
 * @property string|null $description
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
class Department extends Model
{
    use Auditable;

    /** @use HasFactory<DepartmentFactory> */
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
        'data',
        'is_active',
        'is_default',
        'sort_order',
        'created_by',
    ];

    /**
     * The designations (job titles) defined under this department.
     *
     * @return HasMany<Designation, $this>
     */
    public function designations(): HasMany
    {
        return $this->hasMany(Designation::class);
    }

    /**
     * The employees assigned to this department.
     *
     * @return HasMany<Employee, $this>
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data' => 'array',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'sort_order' => 'integer',
            'created_by' => 'integer',
            'deleted_by_user_id' => 'integer',
        ];
    }

    protected static function newFactory(): DepartmentFactory
    {
        return DepartmentFactory::new();
    }
}
