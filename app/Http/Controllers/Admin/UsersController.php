<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UsersController extends Controller
{
    private const GUARD = 'api';

    private function check(string $ability): void
    {
        abort_unless(auth()->user()?->can($ability), 403, 'You do not have permission for this action.');
    }

    /** Users are always scoped to the logged-in user's tenant (null = platform). */
    private function scoped()
    {
        $tenantId = auth()->user()->tenant_id;

        return User::query()->when(
            $tenantId === null,
            fn ($q) => $q->whereNull('tenant_id'),
            fn ($q) => $q->where('tenant_id', $tenantId),
        );
    }

    private function ensureSameTenant(User $user): void
    {
        abort_unless($user->tenant_id === auth()->user()->tenant_id, 404);
    }

    /** Master Admin role can only be handed out by a Master Admin. */
    private function assignableRoles()
    {
        $query = Role::query()->with('permissions')->where('guard_name', self::GUARD)->orderBy('name');

        if (! auth()->user()->hasRole(config('modules.master_role'))) {
            $query->where('name', '!=', config('modules.master_role'));
        }

        return $query->get();
    }

    public function index(Request $request)
    {
        $this->check('users.view');

        $users = $this->scoped()
            ->with('roles')
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', '%'.$request->search.'%')
                ->orWhere('email', 'like', '%'.$request->search.'%')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function show(User $user)
    {
        return redirect()->route('users.edit', $user);
    }

    public function create()
    {
        $this->check('users.create');

        return view('admin.users.form', ['user' => new User(), 'roles' => $this->assignableRoles()]);
    }

    public function store(Request $request)
    {
        $this->check('users.create');
        $data = $this->validated($request);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'email_verified_at' => now(),
            'tenant_id' => auth()->user()->tenant_id,
        ]);
        $user->syncRoles([$data['role']]);

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        $this->check('users.update');
        $this->ensureSameTenant($user);

        return view('admin.users.form', ['user' => $user, 'roles' => $this->assignableRoles()]);
    }

    public function update(Request $request, User $user)
    {
        $this->check('users.update');
        $this->ensureSameTenant($user);
        $data = $this->validated($request, $user);

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
        ];
        if (! empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }
        $user->update($payload);

        if ($user->id !== auth()->id()) {
            $user->syncRoles([$data['role']]);
        }

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        $this->check('users.delete');
        $this->ensureSameTenant($user);
        abort_if($user->id === auth()->id(), 403, 'You cannot delete your own account.');

        $user->delete();

        return redirect()->route('users.index')->with('success', 'User deleted.');
    }

    private function validated(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique(User::class, 'email')->ignore($user?->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => [$user ? 'nullable' : 'required', 'confirmed', Password::min(8)],
            'role' => ['required', 'string', Rule::in($this->assignableRoles()->pluck('name')->all())],
        ]);
    }
}
