<?php

declare(strict_types=1);

namespace Modules\Setup\Http\Controllers;

use App\Http\Controllers\Controller as BaseController;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Base controller for the Setup module. Adds the AuthorizesRequests trait (the
 * app base controller does not include it) so actions can call
 * `$this->authorize()` against the module policies.
 */
abstract class Controller extends BaseController
{
    use AuthorizesRequests;
}
