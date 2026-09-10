<?php

declare(strict_types=1);

namespace Modules\Setup\Policies;

use App\Models\User;

/**
 * Maps the currency actions to the `currencies.*` permissions resolved from the
 * current tenant database. Platform super-admins bypass these via the User model's
 * checkPermissionTo() override.
 */
class CurrencyPolicy
{
    public function create(User $actor): bool
    {
        return $actor->can('currencies.create');
    }

    /** Listing pages (limited columns). */
    public function view(User $actor): bool
    {
        return $actor->can('currencies.view');
    }

    /** Detail page (full record). */
    public function viewFull(User $actor): bool
    {
        return $actor->can('currencies.viewFull');
    }

    /** Include soft-deleted records. */
    public function viewAny(User $actor): bool
    {
        return $actor->can('currencies.viewAny');
    }

    public function update(User $actor): bool
    {
        return $actor->can('currencies.update');
    }

    public function delete(User $actor): bool
    {
        return $actor->can('currencies.delete');
    }

    /** Restore soft-deleted records. */
    public function undoDelete(User $actor): bool
    {
        return $actor->can('currencies.undoDelete');
    }

    /** Permanently remove a soft-deleted record. */
    public function hardDelete(User $actor): bool
    {
        return $actor->can('currencies.hardDelete');
    }
}
