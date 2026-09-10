<?php

declare(strict_types=1);

namespace Modules\Setup\Providers;

use Illuminate\Support\Facades\Gate;
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
use Modules\Setup\Policies\AreaPolicy;
use Modules\Setup\Policies\CityPolicy;
use Modules\Setup\Policies\CountryPolicy;
use Modules\Setup\Policies\CurrencyPolicy;
use Modules\Setup\Policies\CustomerPolicy;
use Modules\Setup\Policies\DepartmentPolicy;
use Modules\Setup\Policies\DesignationPolicy;
use Modules\Setup\Policies\EmployeePolicy;
use Modules\Setup\Policies\GroupPolicy;
use Modules\Setup\Policies\LookupPolicy;
use Modules\Setup\Policies\StatePolicy;
use Modules\Setup\Policies\SupplierPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class SetupServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Setup';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'setup';

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        // Module models live outside App\Models, so policy auto-discovery won't
        // find them — register them explicitly.
        Gate::policy(Lookup::class, LookupPolicy::class);
        Gate::policy(Country::class, CountryPolicy::class);
        Gate::policy(State::class, StatePolicy::class);
        Gate::policy(City::class, CityPolicy::class);
        Gate::policy(Area::class, AreaPolicy::class);
        Gate::policy(Currency::class, CurrencyPolicy::class);
        Gate::policy(Supplier::class, SupplierPolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(Department::class, DepartmentPolicy::class);
        Gate::policy(Group::class, GroupPolicy::class);
        Gate::policy(Designation::class, DesignationPolicy::class);
        Gate::policy(Employee::class, EmployeePolicy::class);
    }
}
