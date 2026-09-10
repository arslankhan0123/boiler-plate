<?php

declare(strict_types=1);

namespace Modules\Setup\Policies;

use App\Models\User;

/**
 * Maps the city actions to the `cities.*` permissions resolved from the current
 * tenant database. Platform super-admins bypass these via the User model's
 * checkPermissionTo() override.
 */
class CityPolicy
{
    public function create(User $actor): bool
    {
        return $actor->can('cities.create');
    }

    /** Listing pages (limited columns). */
    public function view(User $actor): bool
    {
        return $actor->can('cities.view');
    }

    /** Detail page (full record). */
    public function viewFull(User $actor): bool
    {
        return $actor->can('cities.viewFull');
    }

    /** Include soft-deleted records. */
    public function viewAny(User $actor): bool
    {
        return $actor->can('cities.viewAny');
    }

    public function update(User $actor): bool
    {
        return $actor->can('cities.update');
    }

    public function delete(User $actor): bool
    {
        return $actor->can('cities.delete');
    }

    /** Restore soft-deleted records. */
    public function undoDelete(User $actor): bool
    {
        return $actor->can('cities.undoDelete');
    }

    /** Permanently remove a soft-deleted record. */
    public function hardDelete(User $actor): bool
    {
        return $actor->can('cities.hardDelete');
    }
}
