<?php

declare(strict_types=1);

namespace Modules\Setup\Policies;

use App\Models\User;

/**
 * Maps lookup-catalog actions to the `lookups.*` permissions resolved from the
 * current tenant database. Tenants read the master (search & pick); central
 * management of the master is a platform-admin concern (super-admins bypass
 * these via the User model's checkPermissionTo() override).
 */
class LookupPolicy
{
    public function view(User $actor): bool
    {
        return $actor->can('lookups.view');
    }
}
