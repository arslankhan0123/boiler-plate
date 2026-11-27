<?php

declare(strict_types=1);

namespace App\Http\Controllers\Retail;

use App\Http\Controllers\Controller;
use App\Models\Settings\PaymentMethod;

/** Base for sales / purchase / POS screens: module check is on the route group; permissions here. */
abstract class RetailController extends Controller
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

    /** Payment methods from Settings → Payment & Shipping (Cash if none). */
    public static function methods(bool $withAdvance = false): array
    {
        $list = PaymentMethod::query()->where('is_active', true)->orderBy('sort')->orderBy('name')->pluck('name')->all() ?: ['Cash'];
        if ($withAdvance) {
            $list[] = 'Advance';
        }

        return array_combine($list, $list);
    }

    protected function paymentRules(string $prefix = ''): array
    {
        return [
            $prefix.'date' => ['required', 'date'],
            $prefix.'method' => ['required', 'string', 'max:50'],
            $prefix.'amount' => ['required', 'numeric', 'gt:0'],
            $prefix.'note' => ['nullable', 'string', 'max:1000'],
            $prefix.'cheque_no' => ['nullable', 'string', 'max:50'],
            $prefix.'cheque_date' => ['nullable', 'date'],
        ];
    }
}
