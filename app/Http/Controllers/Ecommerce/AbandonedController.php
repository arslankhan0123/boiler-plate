<?php

declare(strict_types=1);

namespace App\Http\Controllers\Ecommerce;

use App\Models\Ecommerce\Cart;

/** Abandoned carts — started but not checked out. */
class AbandonedController extends EcommerceController
{
    public function index()
    {
        $this->need('ecommerce.manage_orders');
        $carts = Cart::query()->where('placed', false)->where('subtotal', '>', 0)
            ->where('updated_at', '<', now()->subMinutes(30))
            ->latest('updated_at')->paginate(25);

        return view('ecommerce.abandoned.index', [
            'carts' => $carts,
            'lostValue' => Cart::where('placed', false)->where('subtotal', '>', 0)->sum('subtotal'),
        ]);
    }
}
