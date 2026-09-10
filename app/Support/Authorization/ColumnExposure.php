<?php

declare(strict_types=1);

namespace App\Support\Authorization;

/**
 * How visible a catalogued column is. Used both as a column's base exposure
 * (in `resource_columns`) and as a per-role grant (in `column_visibilities`).
 *
 * Visibility is ranked: a column is shown in a given context when its effective
 * exposure rank is at least the context's rank.
 *   - listing: shown on listing endpoints (view) and detail endpoints (viewFull)
 *   - detail:  shown only on detail endpoints (viewFull)
 *   - hidden:  never shown (until a role grants it a higher exposure)
 */
enum ColumnExposure: string
{
    case Listing = 'listing';
    case Detail = 'detail';
    case Hidden = 'hidden';

    /**
     * Visibility rank — higher is more visible. Drives both "which exposure
     * wins across a user's roles" and "is this column visible in this context".
     */
    public function rank(): int
    {
        return match ($this) {
            self::Listing => 2,
            self::Detail => 1,
            self::Hidden => 0,
        };
    }

    /**
     * Is a column with this effective exposure visible in the given context?
     * The context is itself an exposure (Listing for list endpoints, Detail for
     * detail endpoints).
     */
    public function visibleIn(self $context): bool
    {
        return $this->rank() >= $context->rank();
    }

    /**
     * The more visible of two exposures (used to merge a user's role grants on
     * top of a column's base exposure — grants are additive).
     */
    public function max(self $other): self
    {
        return $this->rank() >= $other->rank() ? $this : $other;
    }
}
