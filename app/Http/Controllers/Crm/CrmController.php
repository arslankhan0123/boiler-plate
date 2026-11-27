<?php

declare(strict_types=1);

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\User;

/** Base for CRM screens: module check is on the route group; permissions here. */
abstract class CrmController extends Controller
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

    /** Tenant users for the owner/assignee dropdown. */
    protected function owners(): array
    {
        return User::query()->where('tenant_id', auth()->user()->tenant_id)->orderBy('name')->pluck('name', 'id')->all();
    }
}
