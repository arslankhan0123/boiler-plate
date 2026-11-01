<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantAccessService;
use App\Support\ModuleAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Throwable;

/**
 * Platform-only: create tenants (each gets its own database), pick which
 * modules they may use, and create their first admin user.
 */
class TenantsController extends Controller
{
    private function check(string $ability): void
    {
        abort_unless(auth()->user()?->can($ability), 403, 'You do not have permission for this action.');
    }

    public function index(Request $request)
    {
        $this->check('tenants.view');

        $tenants = Tenant::query()
            ->withCount('users')
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->search.'%'))
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.tenants.index', compact('tenants'));
    }

    public function create()
    {
        $this->check('tenants.create');

        return view('admin.tenants.form', ['tenant' => new Tenant(['is_active' => true]), 'admin' => null]);
    }

    public function store(Request $request, TenantAccessService $access)
    {
        $this->check('tenants.create');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150', Rule::unique(Tenant::class, 'name')],
            'is_active' => ['required', 'boolean'],
            'modules' => ['array'],
            'modules.*' => [Rule::in(array_keys(ModuleAccess::assignable()))],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255', Rule::unique(User::class, 'email')],
            'admin_password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $tenant = null;

        try {
            // Creating the tenant fires the pipeline: create DB → migrate → seed (roles & permissions).
            $tenant = Tenant::create([
                'name' => $data['name'],
                'is_active' => (bool) $data['is_active'],
                'modules' => array_values($data['modules'] ?? []),
            ]);

            $admin = User::create([
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'password' => Hash::make($data['admin_password']),
                'email_verified_at' => now(),
                'tenant_id' => $tenant->id,
                'is_platform_admin' => false,
            ]);

            // Create this tenant's module permissions + Tenant Admin role in its own DB.
            $access->sync($tenant, $admin);
        } catch (Throwable $e) {
            report($e);
            if ($tenant) {
                User::query()->where('tenant_id', $tenant->id)->forceDelete();
                $tenant->delete(); // also drops its database
            }

            return back()->withInput()->with('error', 'Tenant could not be created: '.$e->getMessage());
        }

        return redirect()->route('tenants.index')
            ->with('success', "Tenant \"{$tenant->name}\" created with database {$tenant->databaseName()}.");
    }

    public function show(Tenant $tenant)
    {
        return redirect()->route('tenants.edit', $tenant);
    }

    public function edit(Tenant $tenant)
    {
        $this->check('tenants.update');

        $admin = User::query()->where('tenant_id', $tenant->id)->oldest()->first();

        return view('admin.tenants.form', compact('tenant', 'admin'));
    }

    public function update(Request $request, Tenant $tenant, TenantAccessService $access)
    {
        $this->check('tenants.update');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150', Rule::unique(Tenant::class, 'name')->ignore($tenant->id)],
            'is_active' => ['required', 'boolean'],
            'modules' => ['array'],
            'modules.*' => [Rule::in(array_keys(ModuleAccess::assignable()))],
        ]);

        $tenant->update([
            'name' => $data['name'],
            'is_active' => (bool) $data['is_active'],
            'modules' => array_values($data['modules'] ?? []),
        ]);

        // Add permissions for newly ticked modules, remove the unticked ones.
        $access->sync($tenant);

        return redirect()->route('tenants.index')->with('success', 'Tenant updated and permissions synced.');
    }

    public function destroy(Tenant $tenant)
    {
        $this->check('tenants.delete');

        $name = $tenant->name;
        User::query()->where('tenant_id', $tenant->id)->forceDelete();
        $tenant->delete(); // TenantDeleted pipeline drops the database

        return redirect()->route('tenants.index')->with('success', "Tenant \"{$name}\" and its database were deleted.");
    }
}
