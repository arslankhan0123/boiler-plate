<?php

declare(strict_types=1);

namespace Modules\Setup\Policies;

use App\Models\User;

/**
 * Maps the supplier actions to the `suppliers.*` permissions resolved from the
 * current tenant database. Platform super-admins bypass these via the User model's
 * checkPermissionTo() override.
 */
class SupplierPolicy
{
    public function create(User $actor): bool
    {
        return $actor->can('suppliers.create');
    }

    /** Listing pages (limited columns). */
    public function view(User $actor): bool
    {
        return $actor->can('suppliers.view');
    }

    /** Detail page (full record). */
    public function viewFull(User $actor): bool
    {
        return $actor->can('suppliers.viewFull');
    }

    /** Include soft-deleted records. */
    public function viewAny(User $actor): bool
    {
        return $actor->can('suppliers.viewAny');
    }

    public function update(User $actor): bool
    {
        return $actor->can('suppliers.update');
    }

    public function delete(User $actor): bool
    {
        return $actor->can('suppliers.delete');
    }

    /** Restore soft-deleted records. */
    public function undoDelete(User $actor): bool
    {
        return $actor->can('suppliers.undoDelete');
    }

    /** Permanently remove a soft-deleted record. */
    public function hardDelete(User $actor): bool
    {
        return $actor->can('suppliers.hardDelete');
    }
}
