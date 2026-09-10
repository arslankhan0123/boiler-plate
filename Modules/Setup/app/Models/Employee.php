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
use Modules\Setup\Database\Factories\EmployeeFactory;

/**
 * A tenant-defined employee (core HR profile). Pure tenant-owned custom data;
 * belongs to a tenant `Department` + `Designation` (both required) and optionally
 * links to the tenant's own `Country`/`State`. Related names are read through the
 * relations, never duplicated. Documents are a child collection managed through the
 * Employee payload.
 *
 * @property int $id
 * @property string $name
 * @property string|null $code
 * @property int $department_id
 * @property int $designation_id
 * @property string|null $email
 * @property string|null $phone
 * @property Carbon|null $dob
 * @property string|null $gender
 * @property string|null $marital_status
 * @property string|null $blood_group
 * @property string|null $religion
 * @property int|null $country_id
 * @property int|null $state_id
 * @property string|null $street
 * @property string|null $city
 * @property string|null $zip
 * @property string|null $national_id
 * @property string|null $iqama_no
 * @property Carbon|null $iqama_no_expiry_date
 * @property string|null $passport
 * @property Carbon|null $passport_expiry_date
 * @property string|null $driving_license_no
 * @property Carbon|null $driving_license_expiry_date
 * @property string|null $tuv_no
 * @property Carbon|null $tuv_no_expiry_date
 * @property Carbon|null $join_date
 * @property string|null $type
 * @property string|null $employment_type
 * @property string|null $duty_type
 * @property string|null $bank_name
 * @property string|null $bank_branch_name
 * @property string|null $bank_account_no
 * @property string|null $iban_num
 * @property string|null $company_name
 * @property float|null $basic_salary
 * @property float|null $transport_allowance
 * @property float|null $gross_salary
 * @property float|null $hourly_rate
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
class Employee extends Model
{
    use Auditable;

    /** @use HasFactory<EmployeeFactory> */
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
        'department_id',
        'designation_id',
        'email',
        'phone',
        'dob',
        'gender',
        'marital_status',
        'blood_group',
        'religion',
        'country_id',
        'state_id',
        'street',
        'city',
        'zip',
        'national_id',
        'iqama_no',
        'iqama_no_expiry_date',
        'passport',
        'passport_expiry_date',
        'driving_license_no',
        'driving_license_expiry_date',
        'tuv_no',
        'tuv_no_expiry_date',
        'join_date',
        'type',
        'employment_type',
        'duty_type',
        'bank_name',
        'bank_branch_name',
        'bank_account_no',
        'iban_num',
        'company_name',
        'basic_salary',
        'transport_allowance',
        'gross_salary',
        'hourly_rate',
        'data',
        'is_active',
        'is_default',
        'sort_order',
        'created_by',
    ];

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * @return BelongsTo<Designation, $this>
     */
    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    /**
     * @return BelongsTo<Country, $this>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * @return BelongsTo<State, $this>
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    /**
     * The employee's documents (metadata only).
     *
     * @return HasMany<EmployeeDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'department_id' => 'integer',
            'designation_id' => 'integer',
            'country_id' => 'integer',
            'state_id' => 'integer',
            'dob' => 'date',
            'iqama_no_expiry_date' => 'date',
            'passport_expiry_date' => 'date',
            'driving_license_expiry_date' => 'date',
            'tuv_no_expiry_date' => 'date',
            'join_date' => 'date',
            'basic_salary' => 'float',
            'transport_allowance' => 'float',
            'gross_salary' => 'float',
            'hourly_rate' => 'float',
            'data' => 'array',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'sort_order' => 'integer',
            'created_by' => 'integer',
            'deleted_by_user_id' => 'integer',
        ];
    }

    protected static function newFactory(): EmployeeFactory
    {
        return EmployeeFactory::new();
    }
}
