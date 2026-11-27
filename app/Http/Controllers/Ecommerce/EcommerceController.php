<?php

declare(strict_types=1);

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;

/** Base for admin e-commerce screens: module check on route group; permissions here. */
abstract class EcommerceController extends Controller
{
    protected function need(string ...$permissions): void
    {
        $user = auth()->user();
        foreach ($permissions as $p) {
            if ($user?->can($p)) {
                return;
            }
        }
        abort(403, 'You do not have permission for this action.');
    }

    protected function can(string $permission): bool
    {
        return (bool) auth()->user()?->can($permission);
    }
}
