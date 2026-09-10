<?php

declare(strict_types=1);

namespace Modules\RolesAndPermissions\Http\Controllers;

use App\Http\Controllers\Controller as BaseController;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Base controller for the RolesAndPermissions module. Adds the
 * AuthorizesRequests trait (the app base controller does not include it) so
 * actions can call `$this->authorize()` against the central policies.
 */
abstract class Controller extends BaseController
{
    use AuthorizesRequests;
}
