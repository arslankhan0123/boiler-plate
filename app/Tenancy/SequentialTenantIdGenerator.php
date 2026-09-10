<?php

declare(strict_types=1);

namespace App\Tenancy;

use App\Models\Tenant;
use Stancl\Tenancy\Contracts\UniqueIdentifierGenerator;

/**
 * Generates sequential, zero-padded tenant ids (001, 002, 003, …) instead of
 * stancl's default UUID. This keeps the tenant key human-readable and yields
 * predictable database names (tenant_001_database, tenant_002_database, …).
 *
 * Note: the id stays a string primary key (so the zero-padding is preserved).
 * Tenant onboarding is a rare, admin-driven action, so the max()+1 lookup is
 * acceptable; the primary-key uniqueness constraint guards the unlikely race.
 */
class SequentialTenantIdGenerator implements UniqueIdentifierGenerator
{
    public static function generate(mixed $resource): string
    {
        $last = Tenant::query()
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->max() ?? 0;

        return str_pad((string) ($last + 1), 3, '0', STR_PAD_LEFT);
    }
}
