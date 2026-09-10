<?php

declare(strict_types=1);

namespace App\Support\Authorization;

use App\Models\ColumnVisibility;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Setup\Models\Area;
use Modules\Setup\Models\City;
use Modules\Setup\Models\Country;
use Modules\Setup\Models\Currency;
use Modules\Setup\Models\Customer;
use Modules\Setup\Models\Department;
use Modules\Setup\Models\Designation;
use Modules\Setup\Models\Employee;
use Modules\Setup\Models\Group;
use Modules\Setup\Models\Lookup;
use Modules\Setup\Models\State;
use Modules\Setup\Models\Supplier;

/**
 * Central catalog of authorizable resources.
 *
 * Drives permission generation (`permissions:sync`), the default-role grants,
 * and the column visibility matrix. As modules add domain models, register them
 * here.
 */
class ResourceRegistry
{
    /**
     * Actions every full-CRUD resource supports.
     *
     * @var list<string>
     */
    public const BASE_ACTIONS = [
        'create',
        'view',        // listing pages (limited columns)
        'viewFull',    // detail page (full record)
        'update',
        'delete',
    ];

    /**
     * Actions that only make sense for SOFT-DELETABLE resources — generated only
     * when the resource's model uses the SoftDeletes trait (or declares
     * `softDeletes => true`).
     *
     * @var list<string>
     */
    public const SOFT_DELETE_ACTIONS = [
        'viewAny',     // include soft-deleted records
        'undoDelete',  // restore soft-deleted records
        'hardDelete',  // permanently remove a soft-deleted record
    ];

    /**
     * Resource key => definition.
     *
     * `columns` is the visibility catalog: each column's base exposure, whether
     * it is sensitive, and whether it is `editable` (default true; false = a
     * system-managed / read-only field that no one may write, e.g. snapshot ISO
     * identity fields). Columns absent here are never read-filtered (e.g. the
     * primary key). Secrets such as `password` are NOT catalogued — they are
     * excluded from output entirely (model `$hidden`).
     *
     * `actions` restricts a resource to an explicit subset (RBAC-management
     * resources use this — e.g. permissions are a read-only catalog). Omit it for
     * a full-CRUD resource, which gets BASE_ACTIONS plus, when soft-deletable, the
     * SOFT_DELETE_ACTIONS. `softDeletes` overrides trait detection if needed.
     *
     * @return array<string, array{model: class-string, columns?: array<string, array{exposure: ColumnExposure, sensitive?: bool, editable?: bool}>, actions?: list<string>, softDeletes?: bool}>
     */
    public static function resources(): array
    {
        return [
            'users' => [
                'model' => User::class,
                'columns' => [
                    'name' => ['exposure' => ColumnExposure::Listing],
                    'email' => ['exposure' => ColumnExposure::Listing],
                    'email_verified_at' => ['exposure' => ColumnExposure::Detail],
                    // PII: hidden by default; revealed per-role via the matrix.
                    'phone' => ['exposure' => ColumnExposure::Hidden, 'sensitive' => true],
                ],
            ],

            // RBAC management resources (administered via the RolesAndPermissions
            // module). No `columns` => not column-filtered.
            'roles' => [
                'model' => Role::class,
                'actions' => ['view', 'create', 'update', 'delete'],
            ],
            'permissions' => [
                'model' => Permission::class,
                'actions' => ['view'], // read-only catalog
            ],
            'columnVisibility' => [
                'model' => ColumnVisibility::class,
                'actions' => ['view', 'update'], // read + edit the matrix
            ],

            // Setup module — central lookup master (tenant read access only;
            // central CRUD is a platform-admin concern that bypasses tenant RBAC).
            'lookups' => [
                'model' => Lookup::class,
                'actions' => ['view'],
            ],

            // Setup module — the tenant's selected countries (snapshot of the
            // master). ISO identity + snapshot fields are read-only (editable=false);
            // only the tenant-owned fields may be written.
            'countries' => [
                'model' => Country::class,
                'columns' => [
                    'name' => ['exposure' => ColumnExposure::Listing, 'editable' => false],
                    'iso2' => ['exposure' => ColumnExposure::Listing, 'editable' => false],
                    'iso3' => ['exposure' => ColumnExposure::Detail, 'editable' => false],
                    'numeric_code' => ['exposure' => ColumnExposure::Detail, 'editable' => false],
                    'phone_code' => ['exposure' => ColumnExposure::Detail, 'editable' => false],
                    'capital' => ['exposure' => ColumnExposure::Detail, 'editable' => false],
                    'region' => ['exposure' => ColumnExposure::Listing, 'editable' => false],
                    'currency_code' => ['exposure' => ColumnExposure::Detail, 'editable' => false],
                    'currency_symbol' => ['exposure' => ColumnExposure::Detail, 'editable' => false],
                    'locale' => ['exposure' => ColumnExposure::Detail, 'editable' => false],
                    'timezone' => ['exposure' => ColumnExposure::Detail, 'editable' => false],
                    'flag_image' => ['exposure' => ColumnExposure::Listing, 'editable' => false],
                    'is_active' => ['exposure' => ColumnExposure::Listing, 'editable' => true],
                    'is_default' => ['exposure' => ColumnExposure::Listing, 'editable' => true],
                    'sort_order' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                ],
            ],

            // Setup module — the tenant's selected states (snapshot of the master).
            'states' => [
                'model' => State::class,
                'columns' => [
                    'name' => ['exposure' => ColumnExposure::Listing, 'editable' => false],
                    'code' => ['exposure' => ColumnExposure::Detail, 'editable' => false],
                    'country_name' => ['exposure' => ColumnExposure::Listing, 'editable' => false],
                    'country_iso2' => ['exposure' => ColumnExposure::Detail, 'editable' => false],
                    'latitude' => ['exposure' => ColumnExposure::Detail, 'editable' => false],
                    'longitude' => ['exposure' => ColumnExposure::Detail, 'editable' => false],
                    'is_active' => ['exposure' => ColumnExposure::Listing, 'editable' => true],
                    'is_default' => ['exposure' => ColumnExposure::Listing, 'editable' => true],
                    'sort_order' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                ],
            ],

            // Setup module — the tenant's selected cities (snapshot of the master).
            'cities' => [
                'model' => City::class,
                'columns' => [
                    'name' => ['exposure' => ColumnExposure::Listing, 'editable' => false],
                    'state_name' => ['exposure' => ColumnExposure::Listing, 'editable' => false],
                    'country_name' => ['exposure' => ColumnExposure::Listing, 'editable' => false],
                    'country_iso2' => ['exposure' => ColumnExposure::Detail, 'editable' => false],
                    'latitude' => ['exposure' => ColumnExposure::Detail, 'editable' => false],
                    'longitude' => ['exposure' => ColumnExposure::Detail, 'editable' => false],
                    'is_active' => ['exposure' => ColumnExposure::Listing, 'editable' => true],
                    'is_default' => ['exposure' => ColumnExposure::Listing, 'editable' => true],
                    'sort_order' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                ],
            ],

            // Setup module — tenant-defined areas under a city. NOT a lookup snapshot:
            // pure custom data, so the custom fields are editable; the denormalized
            // ancestry (city/state/country names) is read-only.
            'areas' => [
                'model' => Area::class,
                'columns' => [
                    'name' => ['exposure' => ColumnExposure::Listing, 'editable' => true],
                    'area_code' => ['exposure' => ColumnExposure::Listing, 'editable' => true],
                    'city_name' => ['exposure' => ColumnExposure::Listing, 'editable' => false],
                    'state_name' => ['exposure' => ColumnExposure::Listing, 'editable' => false],
                    'country_name' => ['exposure' => ColumnExposure::Detail, 'editable' => false],
                    'address_line_1' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'address_line_2' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'phone' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'email' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'description' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'latitude' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'longitude' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'data' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'is_active' => ['exposure' => ColumnExposure::Listing, 'editable' => true],
                    'is_default' => ['exposure' => ColumnExposure::Listing, 'editable' => true],
                    'sort_order' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                ],
            ],

            // Setup module — the tenant's selected currencies (snapshot of the master).
            'currencies' => [
                'model' => Currency::class,
                'columns' => [
                    'code' => ['exposure' => ColumnExposure::Listing, 'editable' => false],
                    'name' => ['exposure' => ColumnExposure::Listing, 'editable' => false],
                    'symbol' => ['exposure' => ColumnExposure::Listing, 'editable' => false],
                    'numeric_code' => ['exposure' => ColumnExposure::Detail, 'editable' => false],
                    'decimal_digits' => ['exposure' => ColumnExposure::Detail, 'editable' => false],
                    'is_active' => ['exposure' => ColumnExposure::Listing, 'editable' => true],
                    'is_default' => ['exposure' => ColumnExposure::Listing, 'editable' => true],
                    'sort_order' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                ],
            ],

            // Setup module — tenant-defined suppliers (custom data). Optionally linked
            // to the tenant's own country/state/currency; the related names
            // (currency_code/country_name/state_name) are relation-derived output and
            // always shown (not catalogued).
            'suppliers' => [
                'model' => Supplier::class,
                'columns' => [
                    'company_name' => ['exposure' => ColumnExposure::Listing, 'editable' => true],
                    'group_name' => ['exposure' => ColumnExposure::Listing, 'editable' => true],
                    'email' => ['exposure' => ColumnExposure::Listing, 'editable' => true],
                    'phone' => ['exposure' => ColumnExposure::Listing, 'editable' => true],
                    'vat_number' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'contact_person' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'whatsapp' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'website' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'currency_id' => ['exposure' => ColumnExposure::Listing, 'editable' => true],
                    'country_id' => ['exposure' => ColumnExposure::Listing, 'editable' => true],
                    'state_id' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'default_language' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'street' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'city' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'zip' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'po_box' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'mailing_address' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'opening_balance' => ['exposure' => ColumnExposure::Listing, 'editable' => true],
                    'data' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'is_active' => ['exposure' => ColumnExposure::Listing, 'editable' => true],
                    'is_default' => ['exposure' => ColumnExposure::Listing, 'editable' => true],
                    'sort_order' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                ],
            ],

            // Setup module — tenant-defined customers (custom data). Optionally linked
            // to the tenant's own country/state/currency (names are relation-derived
            // output, always shown).
            'customers' => [
                'model' => Customer::class,
                'columns' => [
                    'company_name' => ['exposure' => ColumnExposure::Listing, 'editable' => true],
                    'code' => ['exposure' => ColumnExposure::Listing, 'editable' => true],
                    'group_name' => ['exposure' => ColumnExposure::Listing, 'editable' => true],
                    'email' => ['exposure' => ColumnExposure::Listing, 'editable' => true],
                    'phone' => ['exposure' => ColumnExposure::Listing, 'editable' => true],
                    'vat_number' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'mobile' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'fax' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'whatsapp' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'website' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'short_name' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'vendor_code' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'currency_id' => ['exposure' => ColumnExposure::Listing, 'editable' => true],
                    'country_id' => ['exposure' => ColumnExposure::Listing, 'editable' => true],
                    'state_id' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'default_language' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'address' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'city' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'zip' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'location_url' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'opening_balance' => ['exposure' => ColumnExposure::Listing, 'editable' => true],
                    'data' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'is_active' => ['exposure' => ColumnExposure::Listing, 'editable' => true],
                    'is_default' => ['exposure' => ColumnExposure::Listing, 'editable' => true],
                    'sort_order' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                ],
            ],

            // Setup module — tenant-defined departments (custom data, flat list).
            'departments' => [
                'model' => Department::class,
                'columns' => [
                    'name' => ['exposure' => ColumnExposure::Listing, 'editable' => true],
                    'code' => ['exposure' => ColumnExposure::Listing, 'editable' => true],
                    'description' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'data' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'is_active' => ['exposure' => ColumnExposure::Listing, 'editable' => true],
                    'is_default' => ['exposure' => ColumnExposure::Listing, 'editable' => true],
                    'sort_order' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                ],
            ],

            // Setup module — tenant-defined groups (hierarchical classification).
            // parent_id is catalogued editable (re-parentable); parent_name is
            // relation-derived output, always shown (not catalogued).
            'groups' => [
                'model' => Group::class,
                'columns' => [
                    'name' => ['exposure' => ColumnExposure::Listing, 'editable' => true],
                    'code' => ['exposure' => ColumnExposure::Listing, 'editable' => true],
                    'color' => ['exposure' => ColumnExposure::Listing, 'editable' => true],
                    'parent_id' => ['exposure' => ColumnExposure::Listing, 'editable' => true],
                    'description' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'data' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'is_active' => ['exposure' => ColumnExposure::Listing, 'editable' => true],
                    'is_default' => ['exposure' => ColumnExposure::Listing, 'editable' => true],
                    'sort_order' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                ],
            ],

            // Setup module — tenant-defined designations (job titles under a department).
            'designations' => [
                'model' => Designation::class,
                'columns' => [
                    'name' => ['exposure' => ColumnExposure::Listing, 'editable' => true],
                    'code' => ['exposure' => ColumnExposure::Listing, 'editable' => true],
                    'department_id' => ['exposure' => ColumnExposure::Listing, 'editable' => true],
                    'description' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'data' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'is_active' => ['exposure' => ColumnExposure::Listing, 'editable' => true],
                    'is_default' => ['exposure' => ColumnExposure::Listing, 'editable' => true],
                    'sort_order' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                ],
            ],

            // Setup module — tenant-defined employees (core HR profile). FK ids are
            // catalogued editable; relation-derived names + the documents list are
            // always-shown output (not catalogued). `employee_documents` is NOT a
            // registered resource (managed through the Employee payload).
            'employees' => [
                'model' => Employee::class,
                'columns' => [
                    'name' => ['exposure' => ColumnExposure::Listing, 'editable' => true],
                    'code' => ['exposure' => ColumnExposure::Listing, 'editable' => true],
                    'department_id' => ['exposure' => ColumnExposure::Listing, 'editable' => true],
                    'designation_id' => ['exposure' => ColumnExposure::Listing, 'editable' => true],
                    'email' => ['exposure' => ColumnExposure::Listing, 'editable' => true],
                    'phone' => ['exposure' => ColumnExposure::Listing, 'editable' => true],
                    'dob' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'gender' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'marital_status' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'blood_group' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'religion' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'country_id' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'state_id' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'street' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'city' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'zip' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'national_id' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'iqama_no' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'iqama_no_expiry_date' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'passport' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'passport_expiry_date' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'driving_license_no' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'driving_license_expiry_date' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'tuv_no' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'tuv_no_expiry_date' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'join_date' => ['exposure' => ColumnExposure::Listing, 'editable' => true],
                    'type' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'employment_type' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'duty_type' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'bank_name' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'bank_branch_name' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'bank_account_no' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'iban_num' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'company_name' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'basic_salary' => ['exposure' => ColumnExposure::Detail, 'sensitive' => true, 'editable' => true],
                    'transport_allowance' => ['exposure' => ColumnExposure::Detail, 'sensitive' => true, 'editable' => true],
                    'gross_salary' => ['exposure' => ColumnExposure::Detail, 'sensitive' => true, 'editable' => true],
                    'hourly_rate' => ['exposure' => ColumnExposure::Detail, 'sensitive' => true, 'editable' => true],
                    'data' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                    'is_active' => ['exposure' => ColumnExposure::Listing, 'editable' => true],
                    'is_default' => ['exposure' => ColumnExposure::Listing, 'editable' => true],
                    'sort_order' => ['exposure' => ColumnExposure::Detail, 'editable' => true],
                ],
            ],
        ];
    }

    /**
     * The actions a resource supports — its explicit subset, or BASE_ACTIONS plus
     * (when soft-deletable) the SOFT_DELETE_ACTIONS.
     *
     * @return list<string>
     */
    public static function actions(string $resource): array
    {
        $definition = self::resources()[$resource];

        if (isset($definition['actions'])) {
            return $definition['actions'];
        }

        return self::usesSoftDeletes($resource)
            ? [...self::BASE_ACTIONS, ...self::SOFT_DELETE_ACTIONS]
            : self::BASE_ACTIONS;
    }

    /**
     * Whether a resource's model is soft-deletable (drives the SOFT_DELETE_ACTIONS).
     * A `softDeletes` key on the definition overrides trait detection.
     */
    public static function usesSoftDeletes(string $resource): bool
    {
        $definition = self::resources()[$resource];

        if (isset($definition['softDeletes'])) {
            return $definition['softDeletes'];
        }

        return in_array(SoftDeletes::class, class_uses_recursive($definition['model']), true);
    }

    /**
     * The normalised column catalog for a resource, in declared order. (Used to
     * mirror the catalog into the tenant `resource_columns` table — `editable` is
     * a code-only concern and is not persisted there.)
     *
     * @return array<string, array{exposure: ColumnExposure, sensitive: bool, sort: int}>
     */
    public static function columns(string $resource): array
    {
        $columns = self::resources()[$resource]['columns'] ?? [];

        $catalog = [];
        $sort = 0;

        foreach ($columns as $column => $definition) {
            $catalog[$column] = [
                'exposure' => $definition['exposure'],
                'sensitive' => $definition['sensitive'] ?? false,
                'sort' => $sort++,
            ];
        }

        return $catalog;
    }

    /**
     * Columns of a resource that MAY be written (editable !== false). Returns null
     * when the resource has no column catalog — meaning there is no column-level
     * write restriction (the whole validated payload is allowed).
     *
     * @return list<string>|null
     */
    public static function editableColumns(string $resource): ?array
    {
        $columns = self::resources()[$resource]['columns'] ?? null;

        if ($columns === null) {
            return null;
        }

        $editable = [];
        foreach ($columns as $column => $definition) {
            if (($definition['editable'] ?? true) !== false) {
                $editable[] = $column;
            }
        }

        return $editable;
    }

    /**
     * Every permission name in the form "{resource}.{action}".
     *
     * @return list<string>
     */
    public static function permissions(): array
    {
        $permissions = [];

        foreach (array_keys(self::resources()) as $resource) {
            foreach (self::actions($resource) as $action) {
                $permissions[] = "{$resource}.{$action}";
            }
        }

        return $permissions;
    }
}
