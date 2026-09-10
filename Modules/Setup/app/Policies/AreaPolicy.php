<?php

declare(strict_types=1);

namespace Modules\Setup\Policies;

use App\Models\User;

/**
 * Maps the area actions to the `areas.*` permissions resolved from the current
 * tenant database. Platform super-admins bypass these via the User model's
 * checkPermissionTo() override.
 */
class AreaPolicy
{
    public function create(User $actor): bool
    {
        return $actor->can('areas.create');
    }

    /** Listing pages (limited columns). */
    public function view(User $actor): bool
    {
        return $actor->can('areas.view');
    }

    /** Detail page (full record). */
    public function viewFull(User $actor): bool
    {
        return $actor->can('areas.viewFull');
    }

    /** Include soft-deleted records. */
    public function viewAny(User $actor): bool
    {
        return $actor->can('areas.viewAny');
    }

    public function update(User $actor): bool
    {
        return $actor->can('areas.update');
    }

    public function delete(User $actor): bool
    {
        return $actor->can('areas.delete');
    }

    /** Restore soft-deleted records. */
    public function undoDelete(User $actor): bool
    {
        return $actor->can('areas.undoDelete');
    }

    /** Permanently remove a soft-deleted record. */
    public function hardDelete(User $actor): bool
    {
        return $actor->can('areas.hardDelete');
    }
}
