<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * The permissions catalog is read-only (generated from ResourceRegistry), so it
 * exposes only a `view` ability, mapped to the `permissions.view` permission
 * resolved from the current tenant database.
 */
class PermissionPolicy
{
    public function view(User $actor): bool
    {
        return $actor->can('permissions.view');
    }
}
