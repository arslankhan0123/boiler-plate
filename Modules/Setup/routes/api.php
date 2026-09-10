<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Setup\Http\Controllers\AreaController;
use Modules\Setup\Http\Controllers\CityController;
use Modules\Setup\Http\Controllers\CountryController;
use Modules\Setup\Http\Controllers\CurrencyController;
use Modules\Setup\Http\Controllers\CustomerController;
use Modules\Setup\Http\Controllers\DepartmentController;
use Modules\Setup\Http\Controllers\DesignationController;
use Modules\Setup\Http\Controllers\EmployeeController;
use Modules\Setup\Http\Controllers\GroupController;
use Modules\Setup\Http\Controllers\LookupController;
use Modules\Setup\Http\Controllers\StateController;
use Modules\Setup\Http\Controllers\SupplierController;

/*
|--------------------------------------------------------------------------
| Setup Module API Routes (mounted at /api/v1)
|--------------------------------------------------------------------------
|
| Setup is a code-organization module; its resources are exposed as flat,
| top-level API resources (no `setup` URL prefix). `lookups` is the central
| master (tenant read/search); `countries` is the tenant's selected subset.
| Every route runs after `auth:api` + `tenant.init`, and `tenant.required`
| rejects non-tenant (platform) contexts so permission checks resolve against
| the caller's tenant database.
|
*/

Route::prefix('v1')
    ->middleware(['auth:api', 'tenant.init', 'tenant.required'])
    ->group(function (): void {
        // Central lookup master — read/search (e.g. the country picker list).
        Route::get('lookups', [LookupController::class, 'index'])->name('lookups.index');

        // Tenant countries. Specific paths declared BEFORE the {id} wildcard.
        Route::get('countries/export', [CountryController::class, 'export'])->name('countries.export');
        Route::get('countries/import-sample', [CountryController::class, 'importSample'])->name('countries.import-sample');
        Route::post('countries/import', [CountryController::class, 'import'])->name('countries.import');
        Route::delete('countries/force', [CountryController::class, 'forceDestroy'])->name('countries.force-destroy');

        Route::get('countries', [CountryController::class, 'index'])->name('countries.index');
        Route::post('countries', [CountryController::class, 'store'])->name('countries.store');
        Route::put('countries', [CountryController::class, 'update'])->name('countries.update');
        Route::delete('countries', [CountryController::class, 'destroy'])->name('countries.destroy');
        Route::get('countries/{id}', [CountryController::class, 'show'])->whereNumber('id')->name('countries.show');

        // Tenant states. Specific paths declared BEFORE the {id} wildcard.
        Route::get('states/export', [StateController::class, 'export'])->name('states.export');
        Route::get('states/import-sample', [StateController::class, 'importSample'])->name('states.import-sample');
        Route::post('states/import', [StateController::class, 'import'])->name('states.import');
        Route::delete('states/force', [StateController::class, 'forceDestroy'])->name('states.force-destroy');

        Route::get('states', [StateController::class, 'index'])->name('states.index');
        Route::post('states', [StateController::class, 'store'])->name('states.store');
        Route::put('states', [StateController::class, 'update'])->name('states.update');
        Route::delete('states', [StateController::class, 'destroy'])->name('states.destroy');
        Route::get('states/{id}', [StateController::class, 'show'])->whereNumber('id')->name('states.show');

        // Tenant cities. Specific paths declared BEFORE the {id} wildcard.
        Route::get('cities/export', [CityController::class, 'export'])->name('cities.export');
        Route::get('cities/import-sample', [CityController::class, 'importSample'])->name('cities.import-sample');
        Route::post('cities/import', [CityController::class, 'import'])->name('cities.import');
        Route::delete('cities/force', [CityController::class, 'forceDestroy'])->name('cities.force-destroy');

        Route::get('cities', [CityController::class, 'index'])->name('cities.index');
        Route::post('cities', [CityController::class, 'store'])->name('cities.store');
        Route::put('cities', [CityController::class, 'update'])->name('cities.update');
        Route::delete('cities', [CityController::class, 'destroy'])->name('cities.destroy');
        Route::get('cities/{id}', [CityController::class, 'show'])->whereNumber('id')->name('cities.show');

        // Tenant areas (custom, not lookup-backed). Specific paths declared BEFORE the {id} wildcard.
        Route::get('areas/export', [AreaController::class, 'export'])->name('areas.export');
        Route::get('areas/import-sample', [AreaController::class, 'importSample'])->name('areas.import-sample');
        Route::post('areas/import', [AreaController::class, 'import'])->name('areas.import');
        Route::delete('areas/force', [AreaController::class, 'forceDestroy'])->name('areas.force-destroy');

        Route::get('areas', [AreaController::class, 'index'])->name('areas.index');
        Route::post('areas', [AreaController::class, 'store'])->name('areas.store');
        Route::put('areas', [AreaController::class, 'update'])->name('areas.update');
        Route::delete('areas', [AreaController::class, 'destroy'])->name('areas.destroy');
        Route::get('areas/{id}', [AreaController::class, 'show'])->whereNumber('id')->name('areas.show');

        // Tenant currencies. Specific paths declared BEFORE the {id} wildcard.
        Route::get('currencies/export', [CurrencyController::class, 'export'])->name('currencies.export');
        Route::get('currencies/import-sample', [CurrencyController::class, 'importSample'])->name('currencies.import-sample');
        Route::post('currencies/import', [CurrencyController::class, 'import'])->name('currencies.import');
        Route::delete('currencies/force', [CurrencyController::class, 'forceDestroy'])->name('currencies.force-destroy');

        Route::get('currencies', [CurrencyController::class, 'index'])->name('currencies.index');
        Route::post('currencies', [CurrencyController::class, 'store'])->name('currencies.store');
        Route::put('currencies', [CurrencyController::class, 'update'])->name('currencies.update');
        Route::delete('currencies', [CurrencyController::class, 'destroy'])->name('currencies.destroy');
        Route::get('currencies/{id}', [CurrencyController::class, 'show'])->whereNumber('id')->name('currencies.show');

        // Tenant suppliers (custom, not lookup-backed). Specific paths declared BEFORE the {id} wildcard.
        Route::get('suppliers/export', [SupplierController::class, 'export'])->name('suppliers.export');
        Route::get('suppliers/import-sample', [SupplierController::class, 'importSample'])->name('suppliers.import-sample');
        Route::post('suppliers/import', [SupplierController::class, 'import'])->name('suppliers.import');
        Route::delete('suppliers/force', [SupplierController::class, 'forceDestroy'])->name('suppliers.force-destroy');

        Route::get('suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
        Route::post('suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
        Route::put('suppliers', [SupplierController::class, 'update'])->name('suppliers.update');
        Route::delete('suppliers', [SupplierController::class, 'destroy'])->name('suppliers.destroy');
        Route::get('suppliers/{id}', [SupplierController::class, 'show'])->whereNumber('id')->name('suppliers.show');

        // Tenant customers (custom, not lookup-backed). Specific paths declared BEFORE the {id} wildcard.
        Route::get('customers/export', [CustomerController::class, 'export'])->name('customers.export');
        Route::get('customers/import-sample', [CustomerController::class, 'importSample'])->name('customers.import-sample');
        Route::post('customers/import', [CustomerController::class, 'import'])->name('customers.import');
        Route::delete('customers/force', [CustomerController::class, 'forceDestroy'])->name('customers.force-destroy');

        Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::post('customers', [CustomerController::class, 'store'])->name('customers.store');
        Route::put('customers', [CustomerController::class, 'update'])->name('customers.update');
        Route::delete('customers', [CustomerController::class, 'destroy'])->name('customers.destroy');
        Route::get('customers/{id}', [CustomerController::class, 'show'])->whereNumber('id')->name('customers.show');

        // Tenant departments (custom, not lookup-backed). Specific paths declared BEFORE the {id} wildcard.
        Route::get('departments/export', [DepartmentController::class, 'export'])->name('departments.export');
        Route::get('departments/import-sample', [DepartmentController::class, 'importSample'])->name('departments.import-sample');
        Route::post('departments/import', [DepartmentController::class, 'import'])->name('departments.import');
        Route::delete('departments/force', [DepartmentController::class, 'forceDestroy'])->name('departments.force-destroy');

        Route::get('departments', [DepartmentController::class, 'index'])->name('departments.index');
        Route::post('departments', [DepartmentController::class, 'store'])->name('departments.store');
        Route::put('departments', [DepartmentController::class, 'update'])->name('departments.update');
        Route::delete('departments', [DepartmentController::class, 'destroy'])->name('departments.destroy');
        Route::get('departments/{id}', [DepartmentController::class, 'show'])->whereNumber('id')->name('departments.show');

        // Tenant groups (custom, hierarchical). Specific paths declared BEFORE the {id} wildcard.
        Route::get('groups/export', [GroupController::class, 'export'])->name('groups.export');
        Route::get('groups/import-sample', [GroupController::class, 'importSample'])->name('groups.import-sample');
        Route::post('groups/import', [GroupController::class, 'import'])->name('groups.import');
        Route::delete('groups/force', [GroupController::class, 'forceDestroy'])->name('groups.force-destroy');

        Route::get('groups', [GroupController::class, 'index'])->name('groups.index');
        Route::post('groups', [GroupController::class, 'store'])->name('groups.store');
        Route::put('groups', [GroupController::class, 'update'])->name('groups.update');
        Route::delete('groups', [GroupController::class, 'destroy'])->name('groups.destroy');
        Route::get('groups/{id}', [GroupController::class, 'show'])->whereNumber('id')->name('groups.show');

        // Tenant designations (custom; require a department). Specific paths BEFORE {id}.
        Route::get('designations/export', [DesignationController::class, 'export'])->name('designations.export');
        Route::get('designations/import-sample', [DesignationController::class, 'importSample'])->name('designations.import-sample');
        Route::post('designations/import', [DesignationController::class, 'import'])->name('designations.import');
        Route::delete('designations/force', [DesignationController::class, 'forceDestroy'])->name('designations.force-destroy');

        Route::get('designations', [DesignationController::class, 'index'])->name('designations.index');
        Route::post('designations', [DesignationController::class, 'store'])->name('designations.store');
        Route::put('designations', [DesignationController::class, 'update'])->name('designations.update');
        Route::delete('designations', [DesignationController::class, 'destroy'])->name('designations.destroy');
        Route::get('designations/{id}', [DesignationController::class, 'show'])->whereNumber('id')->name('designations.show');

        // Tenant employees (custom; require department + designation). Specific paths BEFORE {id}.
        Route::get('employees/export', [EmployeeController::class, 'export'])->name('employees.export');
        Route::get('employees/import-sample', [EmployeeController::class, 'importSample'])->name('employees.import-sample');
        Route::post('employees/import', [EmployeeController::class, 'import'])->name('employees.import');
        Route::delete('employees/force', [EmployeeController::class, 'forceDestroy'])->name('employees.force-destroy');

        Route::get('employees', [EmployeeController::class, 'index'])->name('employees.index');
        Route::post('employees', [EmployeeController::class, 'store'])->name('employees.store');
        Route::put('employees', [EmployeeController::class, 'update'])->name('employees.update');
        Route::delete('employees', [EmployeeController::class, 'destroy'])->name('employees.destroy');
        Route::get('employees/{id}', [EmployeeController::class, 'show'])->whereNumber('id')->name('employees.show');
    });
