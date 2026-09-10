<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * Maps role-management actions to the `roles.*` permissions resolved from the
 * current tenant database. Platform super-admins bypass these via the
 * `checkPermissionTo()` override on the User model.
 */
class RolePolicy
{
    public function view(User $actor): bool
    {
        return $actor->can('roles.view');
    }

    public function create(User $actor): bool
    {
        return $actor->can('roles.create');
    }

    public function update(User $actor): bool
    {
        return $actor->can('roles.update');
    }

    public function delete(User $actor): bool
    {
        return $actor->can('roles.delete');
    }
}
