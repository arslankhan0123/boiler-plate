<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RolesController extends Controller
{
    private const GUARD = 'api';

    private function check(string $ability): void
    {
        abort_unless(auth()->user()?->can($ability), 403, 'You do not have permission for this action.');
    }

    /** Master Admin (platform) and Tenant Admin (tenant) are locked system roles. */
    private function isMaster(Role $role): bool
    {
        return in_array($role->name, [config('modules.master_role'), \App\Support\Authorization\DefaultRoles::TENANT_ADMIN], true);
    }

    public function index()
    {
        $this->check('roles.view');
        $roles = Role::query()->where('guard_name', self::GUARD)->withCount('permissions')->orderBy('name')->get();
        $counts = $this->pivot()->selectRaw('role_id, COUNT(*) as c')->groupBy('role_id')->pluck('c', 'role_id');
        $roles->each(fn ($r) => $r->users_count = (int) ($counts[$r->id] ?? 0));

        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        $this->check('roles.create');

        return view('admin.roles.form', ['role' => new Role(), 'assigned' => []]);
    }

    public function store(Request $request)
    {
        $this->check('roles.create');
        $data = $this->validated($request);

        $role = Role::create(['name' => $data['name'], 'guard_name' => self::GUARD]);
        $role->syncPermissions($this->allowedPermissions($data['permissions'] ?? []));

        return redirect()->route('roles.index')->with('success', 'Role created successfully.');
    }

    public function edit(Role $role)
    {
        $this->check('roles.update');
        abort_if($this->isMaster($role), 403, 'System roles cannot be edited.');

        return view('admin.roles.form', ['role' => $role, 'assigned' => $role->permissions->pluck('name')->all()]);
    }

    public function update(Request $request, Role $role)
    {
        $this->check('roles.update');
        abort_if($this->isMaster($role), 403, 'System roles cannot be edited.');
        $data = $this->validated($request, $role);

        $role->update(['name' => $data['name']]);
        $role->syncPermissions($this->allowedPermissions($data['permissions'] ?? []));

        return redirect()->route('roles.index')->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role)
    {
        $this->check('roles.delete');
        abort_if($this->isMaster($role), 403, 'System roles cannot be deleted.');

        if ($this->pivot()->where('role_id', $role->id)->exists()) {
            return back()->with('error', 'This role is assigned to users. Reassign them first.');
        }

        $role->delete();

        return redirect()->route('roles.index')->with('success', 'Role deleted.');
    }

    /** model_has_roles on the same DB as the roles (tenant DB for tenant users). */
    private function pivot()
    {
        return \Illuminate\Support\Facades\DB::connection((new Role())->getConnectionName())
            ->table(config('permission.table_names.model_has_roles'));
    }

    private function validated(Request $request, ?Role $role = null): array
    {
        return $request->validate([
            'name' => [
                'required', 'string', 'max:100',
                Rule::notIn([config('modules.master_role'), \App\Support\Authorization\DefaultRoles::TENANT_ADMIN]),
                Rule::unique((new Role())->getTable(), 'name')->where('guard_name', self::GUARD)->ignore($role?->id),
            ],
            'permissions' => ['array'],
            'permissions.*' => ['string'],
        ]);
    }

    /** Only known module permissions, and only ones the current user holds (no privilege escalation). */
    private function allowedPermissions(array $requested): array
    {
        $user = auth()->user();

        return Permission::query()
            ->where('guard_name', self::GUARD)
            ->whereIn('name', $requested)
            ->get()
            ->filter(fn ($p) => $user->can($p->name))
            ->values()
            ->all();
    }
}
