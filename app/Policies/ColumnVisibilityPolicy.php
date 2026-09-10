<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * Maps column-visibility matrix actions to the `columnVisibility.*` permissions
 * resolved from the current tenant database. Platform super-admins bypass these
 * via the `checkPermissionTo()` override on the User model.
 */
class ColumnVisibilityPolicy
{
    public function view(User $actor): bool
    {
        return $actor->can('columnVisibility.view');
    }

    public function update(User $actor): bool
    {
        return $actor->can('columnVisibility.update');
    }
}
