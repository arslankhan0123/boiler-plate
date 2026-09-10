<?php

declare(strict_types=1);

namespace Modules\Setup\Policies;

use App\Models\User;

/**
 * Maps the department actions to the `departments.*` permissions resolved from the
 * current tenant database. Platform super-admins bypass these via the User model's
 * checkPermissionTo() override.
 */
class DepartmentPolicy
{
    public function create(User $actor): bool
    {
        return $actor->can('departments.create');
    }

    /** Listing pages (limited columns). */
    public function view(User $actor): bool
    {
        return $actor->can('departments.view');
    }

    /** Detail page (full record). */
    public function viewFull(User $actor): bool
    {
        return $actor->can('departments.viewFull');
    }

    /** Include soft-deleted records. */
    public function viewAny(User $actor): bool
    {
        return $actor->can('departments.viewAny');
    }

    public function update(User $actor): bool
    {
        return $actor->can('departments.update');
    }

    public function delete(User $actor): bool
    {
        return $actor->can('departments.delete');
    }

    /** Restore soft-deleted records. */
    public function undoDelete(User $actor): bool
    {
        return $actor->can('departments.undoDelete');
    }

    /** Permanently remove a soft-deleted record. */
    public function hardDelete(User $actor): bool
    {
        return $actor->can('departments.hardDelete');
    }
}
