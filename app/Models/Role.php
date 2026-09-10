<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Support\Facades\Config;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * Roles live in the TENANT database. We pin this model to the CURRENT default
 * connection (which is the tenant connection once tenancy is initialized).
 *
 * Returning an explicit connection here is essential: without it, when the
 * relationship is accessed from the central-pinned User model, Laravel would
 * make this model inherit User's central connection — and roles would be
 * looked up in the central DB, where they don't exist.
 */
class Role extends SpatieRole
{
    use Auditable;

    public function getConnectionName(): ?string
    {
        return Config::string('database.default');
    }
}
