<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hrm;

use App\Http\Controllers\Controller;

/** Base for HRM screens: module check is on the route group (module:hrm); permissions here. */
abstract class HrmController extends Controller
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
}
