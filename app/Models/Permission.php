<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Support\Facades\Config;
use Spatie\Permission\Models\Permission as SpatiePermission;

/**
 * Permissions live in the TENANT database. Pinned to the current default
 * connection (the tenant connection once tenancy is initialized) so the model
 * never inherits the central connection from a related central-pinned model.
 *
 * @see Role
 */
class Permission extends SpatiePermission
{
    public function getConnectionName(): ?string
    {
        return Config::string('database.default');
    }
}
