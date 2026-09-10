<?php

declare(strict_types=1);

namespace Modules\Setup\Policies;

use App\Models\User;

/**
 * Maps the employee actions to the `employees.*` permissions resolved from the
 * current tenant database. Platform super-admins bypass these via the User model's
 * checkPermissionTo() override.
 */
class EmployeePolicy
{
    public function create(User $actor): bool
    {
        return $actor->can('employees.create');
    }

    /** Listing pages (limited columns). */
    public function view(User $actor): bool
    {
        return $actor->can('employees.view');
    }

    /** Detail page (full record). */
    public function viewFull(User $actor): bool
    {
        return $actor->can('employees.viewFull');
    }

    /** Include soft-deleted records. */
    public function viewAny(User $actor): bool
    {
        return $actor->can('employees.viewAny');
    }

    public function update(User $actor): bool
    {
        return $actor->can('employees.update');
    }

    public function delete(User $actor): bool
    {
        return $actor->can('employees.delete');
    }

    /** Restore soft-deleted records. */
    public function undoDelete(User $actor): bool
    {
        return $actor->can('employees.undoDelete');
    }

    /** Permanently remove a soft-deleted record. */
    public function hardDelete(User $actor): bool
    {
        return $actor->can('employees.hardDelete');
    }
}
