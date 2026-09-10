<?php

declare(strict_types=1);

namespace Modules\Setup\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\TracksSoftDeleteUser;
use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Modules\Setup\Database\Factories\EmployeeDocumentFactory;

/**
 * A document attached to a tenant `Employee` (metadata only — `file` is a
 * filename/reference; no upload/storage handling). Managed through the Employee
 * payload (replace-sync), not a standalone API resource.
 *
 * @property int $id
 * @property int $employee_id
 * @property string $name
 * @property string|null $file
 * @property Carbon|null $expiry_date
 * @property int|null $created_by
 * @property int|null $deleted_by_user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class EmployeeDocument extends Model
{
    use Auditable;

    /** @use HasFactory<EmployeeDocumentFactory> */
    use HasFactory;

    use SoftDeletes;
    use TracksSoftDeleteUser;
    use UsesTenantConnection;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'employee_id',
        'name',
        'file',
        'expiry_date',
        'created_by',
    ];

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'employee_id' => 'integer',
            'expiry_date' => 'date',
            'created_by' => 'integer',
            'deleted_by_user_id' => 'integer',
        ];
    }

    protected static function newFactory(): EmployeeDocumentFactory
    {
        return EmployeeDocumentFactory::new();
    }
}
