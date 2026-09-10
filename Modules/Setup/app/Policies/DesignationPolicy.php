<?php

declare(strict_types=1);

namespace Modules\Setup\Policies;

use App\Models\User;

/**
 * Maps the designation actions to the `designations.*` permissions resolved from
 * the current tenant database. Platform super-admins bypass these via the User
 * model's checkPermissionTo() override.
 */
class DesignationPolicy
{
    public function create(User $actor): bool
    {
        return $actor->can('designations.create');
    }

    /** Listing pages (limited columns). */
    public function view(User $actor): bool
    {
        return $actor->can('designations.view');
    }

    /** Detail page (full record). */
    public function viewFull(User $actor): bool
    {
        return $actor->can('designations.viewFull');
    }

    /** Include soft-deleted records. */
    public function viewAny(User $actor): bool
    {
        return $actor->can('designations.viewAny');
    }

    public function update(User $actor): bool
    {
        return $actor->can('designations.update');
    }

    public function delete(User $actor): bool
    {
        return $actor->can('designations.delete');
    }

    /** Restore soft-deleted records. */
    public function undoDelete(User $actor): bool
    {
        return $actor->can('designations.undoDelete');
    }

    /** Permanently remove a soft-deleted record. */
    public function hardDelete(User $actor): bool
    {
        return $actor->can('designations.hardDelete');
    }
}
