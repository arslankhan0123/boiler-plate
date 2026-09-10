<?php

declare(strict_types=1);

namespace Modules\Setup\Policies;

use App\Models\User;

/**
 * Maps the group actions to the `groups.*` permissions resolved from the current
 * tenant database. Platform super-admins bypass these via the User model's
 * checkPermissionTo() override.
 */
class GroupPolicy
{
    public function create(User $actor): bool
    {
        return $actor->can('groups.create');
    }

    /** Listing pages (limited columns). */
    public function view(User $actor): bool
    {
        return $actor->can('groups.view');
    }

    /** Detail page (full record). */
    public function viewFull(User $actor): bool
    {
        return $actor->can('groups.viewFull');
    }

    /** Include soft-deleted records. */
    public function viewAny(User $actor): bool
    {
        return $actor->can('groups.viewAny');
    }

    public function update(User $actor): bool
    {
        return $actor->can('groups.update');
    }

    public function delete(User $actor): bool
    {
        return $actor->can('groups.delete');
    }

    /** Restore soft-deleted records. */
    public function undoDelete(User $actor): bool
    {
        return $actor->can('groups.undoDelete');
    }

    /** Permanently remove a soft-deleted record. */
    public function hardDelete(User $actor): bool
    {
        return $actor->can('groups.hardDelete');
    }
}
