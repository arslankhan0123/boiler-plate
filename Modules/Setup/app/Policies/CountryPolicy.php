<?php

declare(strict_types=1);

namespace Modules\Setup\Policies;

use App\Models\User;

/**
 * Maps the country actions to the `countries.*` permissions resolved from the
 * current tenant database. Platform super-admins bypass these via the User
 * model's checkPermissionTo() override.
 */
class CountryPolicy
{
    public function create(User $actor): bool
    {
        return $actor->can('countries.create');
    }

    /** Listing pages (limited columns). */
    public function view(User $actor): bool
    {
        return $actor->can('countries.view');
    }

    /** Detail page (full record). */
    public function viewFull(User $actor): bool
    {
        return $actor->can('countries.viewFull');
    }

    /** Include soft-deleted records. */
    public function viewAny(User $actor): bool
    {
        return $actor->can('countries.viewAny');
    }

    public function update(User $actor): bool
    {
        return $actor->can('countries.update');
    }

    public function delete(User $actor): bool
    {
        return $actor->can('countries.delete');
    }

    /** Restore soft-deleted records. */
    public function undoDelete(User $actor): bool
    {
        return $actor->can('countries.undoDelete');
    }

    /** Permanently remove a soft-deleted record. */
    public function hardDelete(User $actor): bool
    {
        return $actor->can('countries.hardDelete');
    }
}
