<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Role;
use Illuminate\Support\Facades\Config;

/**
 * Pins a model to the CURRENT default connection — which is the tenant
 * connection once tenancy is initialized.
 *
 * Returning an explicit connection is essential: without it, when a tenant
 * model is loaded as a relation of a central-pinned model (e.g. User), Laravel
 * would make it inherit the central connection and query the wrong database.
 * Mirrors the inline override on {@see Role}.
 */
trait UsesTenantConnection
{
    public function getConnectionName(): ?string
    {
        return Config::string('database.default');
    }
}
