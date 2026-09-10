<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Concerns\Auditable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Contracts\Permission;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property bool $is_verification_required
 * @property string|null $tenant_id
 * @property bool $is_platform_admin
 * @property Carbon|null $email_verified_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class User extends Authenticatable implements OAuthenticatable
{
    /** @use HasFactory<UserFactory> */
    use Auditable, HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    use HasRoles {
        checkPermissionTo as protected spatieCheckPermissionTo;
    }

    /**
     * Never record credential columns in audit diffs.
     *
     * @return list<string>
     */
    protected static function auditExcludedColumns(): array
    {
        return ['created_at', 'updated_at', 'deleted_at', 'password', 'remember_token'];
    }

    /**
     * Guard for Spatie roles/permissions (matches the Passport API guard).
     */
    protected string $guard_name = 'api';

    /**
     * Users live in the CENTRAL database (identity directory). Pin the model to
     * the central connection so it is never read from a tenant DB after tenancy
     * switches the default connection. (Role assignments still use the tenant
     * connection via the Role model — the pivot follows the related model.)
     */
    public function getConnectionName(): ?string
    {
        return config('tenancy.database.central_connection');
    }

    /**
     * Platform super-admins bypass every permission check (across all tenants)
     * without touching the tenant permission tables. This runs through Spatie's
     * gate hook, so it covers can()/authorize()/@can everywhere.
     *
     * @param  string|int|Permission|\BackedEnum  $permission
     */
    public function checkPermissionTo($permission, ?string $guardName = null): bool
    {
        if ($this->is_platform_admin) {
            return true;
        }

        return $this->spatieCheckPermissionTo($permission, $guardName);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'is_verification_required',
        'tenant_id',
        'is_platform_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_verification_required' => 'boolean',
            'is_platform_admin' => 'boolean',
        ];
    }

    /**
     * Always persist the email address in lower case (on create and update).
     *
     * @return Attribute<string, string>
     */
    protected function email(): Attribute
    {
        return Attribute::make(
            set: fn (string $value): string => mb_strtolower(trim($value)),
        );
    }

    /**
     * Always persist the name with the first letter of each word capitalised
     * (on create and update). Supports multi-word names.
     *
     * @return Attribute<string, string>
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            set: fn (string $value): string => mb_convert_case(
                mb_strtolower((string) preg_replace('/\s+/u', ' ', trim($value))),
                MB_CASE_TITLE,
            ),
        );
    }
}
