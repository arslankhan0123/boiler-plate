<?php

declare(strict_types=1);

namespace App\Models;

use Stancl\Tenancy\Database\Models\Domain as BaseDomain;

/**
 * A tenant's custom storefront domain. Extends stancl/tenancy's Domain with a
 * DNS-verification token + verified_at timestamp. Only verified domains route.
 */
class Domain extends BaseDomain
{
    protected $guarded = [];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    /** Domains always live in the central DB, even when queried in tenant context. */
    public function getConnectionName(): ?string
    {
        return config('tenancy.database.central_connection', 'central');
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }
}
