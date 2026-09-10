<?php

declare(strict_types=1);

namespace Modules\Setup\Policies;

use App\Models\User;

/**
 * Maps the customer actions to the `customers.*` permissions resolved from the
 * current tenant database. Platform super-admins bypass these via the User model's
 * checkPermissionTo() override.
 */
class CustomerPolicy
{
    public function create(User $actor): bool
    {
        return $actor->can('customers.create');
    }

    /** Listing pages (limited columns). */
    public function view(User $actor): bool
    {
        return $actor->can('customers.view');
    }

    /** Detail page (full record). */
    public function viewFull(User $actor): bool
    {
        return $actor->can('customers.viewFull');
    }

    /** Include soft-deleted records. */
    public function viewAny(User $actor): bool
    {
        return $actor->can('customers.viewAny');
    }

    public function update(User $actor): bool
    {
        return $actor->can('customers.update');
    }

    public function delete(User $actor): bool
    {
        return $actor->can('customers.delete');
    }

    /** Restore soft-deleted records. */
    public function undoDelete(User $actor): bool
    {
        return $actor->can('customers.undoDelete');
    }

    /** Permanently remove a soft-deleted record. */
    public function hardDelete(User $actor): bool
    {
        return $actor->can('customers.hardDelete');
    }
}
