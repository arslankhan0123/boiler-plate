<?php

declare(strict_types=1);

namespace Modules\Setup\Policies;

use App\Models\User;

/**
 * Maps the state actions to the `states.*` permissions resolved from the current
 * tenant database. Platform super-admins bypass these via the User model's
 * checkPermissionTo() override.
 */
class StatePolicy
{
    public function create(User $actor): bool
    {
        return $actor->can('states.create');
    }

    /** Listing pages (limited columns). */
    public function view(User $actor): bool
    {
        return $actor->can('states.view');
    }

    /** Detail page (full record). */
    public function viewFull(User $actor): bool
    {
        return $actor->can('states.viewFull');
    }

    /** Include soft-deleted records. */
    public function viewAny(User $actor): bool
    {
        return $actor->can('states.viewAny');
    }

    public function update(User $actor): bool
    {
        return $actor->can('states.update');
    }

    public function delete(User $actor): bool
    {
        return $actor->can('states.delete');
    }

    /** Restore soft-deleted records. */
    public function undoDelete(User $actor): bool
    {
        return $actor->can('states.undoDelete');
    }

    /** Permanently remove a soft-deleted record. */
    public function hardDelete(User $actor): bool
    {
        return $actor->can('states.hardDelete');
    }
}
