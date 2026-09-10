<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Support\Facades\Config;

/**
 * Pins a model to the CENTRAL connection, so it is always read/written from the
 * central database even after tenancy switches the default connection to a
 * tenant. Used by central-DB models that are accessed during tenant requests
 * (e.g. the shared `lookups` master, audit/activity logs). Mirrors the inline
 * override on {@see User}.
 */
trait UsesCentralConnection
{
    public function getConnectionName(): ?string
    {
        return Config::string('tenancy.database.central_connection');
    }
}
