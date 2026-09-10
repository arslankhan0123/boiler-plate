<?php

declare(strict_types=1);

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;

if (! function_exists('format_datetime')) {
    /**
     * Format any date-time value using the project-wide format defined in
     * config('datetime.format'). Accepts a Carbon/DateTime instance, a parsable
     * date string, or null (returns null) — so it is safe to pass model
     * attributes such as $user->email_verified_at directly.
     */
    function format_datetime(DateTimeInterface|string|null $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse($value)->format(Config::string('datetime.format'));
    }
}

if (! function_exists('paginated')) {
    /**
     * Wrap a paginator's already-transformed items together with the standard
     * pagination meta into the `data` payload shape used across the API:
     * `data => { items: [...], pagination: {...} }`.
     *
     * @param  LengthAwarePaginator<int, mixed>  $paginator
     * @return array{items: mixed, pagination: array{total: int, per_page: int, current_page: int, last_page: int, next_page: int|null}}
     */
    function paginated(LengthAwarePaginator $paginator, mixed $items): array
    {
        return [
            'items' => $items,
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'next_page' => $paginator->hasMorePages() ? $paginator->currentPage() + 1 : null,
            ],
        ];
    }
}
