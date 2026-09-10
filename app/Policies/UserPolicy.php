<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * Maps the seven resource actions to the `users.*` permissions resolved from
 * the current tenant database. Platform super-admins bypass these — see the
 * `checkPermissionTo()` override on the User model, which short-circuits the
 * check before it ever touches the tenant permission tables.
 */
class UserPolicy
{
    public function create(User $actor): bool
    {
        return $actor->can('users.create');
    }

    /** Listing pages (limited columns). */
    public function view(User $actor): bool
    {
        return $actor->can('users.view');
    }

    /** Detail page (full record). */
    public function viewFull(User $actor): bool
    {
        return $actor->can('users.viewFull');
    }

    /** Include soft-deleted records. */
    public function viewAny(User $actor): bool
    {
        return $actor->can('users.viewAny');
    }

    public function update(User $actor): bool
    {
        return $actor->can('users.update');
    }

    public function delete(User $actor): bool
    {
        return $actor->can('users.delete');
    }

    /** Restore soft-deleted records. */
    public function undoDelete(User $actor): bool
    {
        return $actor->can('users.undoDelete');
    }
}
