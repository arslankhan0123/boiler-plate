<?php

declare(strict_types=1);

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;

/** Base for inventory screens: module check is on the route group; permissions here. */
abstract class InventoryController extends Controller
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

    /** Parse posted rows (items[][item_id], items[][qty], …) skipping empty lines. */
    protected function rows(array $rows, bool $signed = false): array
    {
        return collect($rows)
            ->filter(fn ($r) => ! empty($r['item_id']) && (float) ($r['qty'] ?? 0) != 0.0)
            ->map(fn ($r) => ['item_id' => (int) $r['item_id'], 'qty' => $signed ? (float) $r['qty'] : abs((float) $r['qty']), 'description' => $r['description'] ?? null])
            ->values()->all();
    }
}
