<?php

declare(strict_types=1);

namespace App\Http\Controllers\Ecommerce;

use App\Models\Ecommerce\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Store customer accounts — aggregated from online orders. */
class CustomerController extends EcommerceController
{
    public function index(Request $request)
    {
        $this->need('ecommerce.view');
        $rows = DB::connection('tenant')->table('ecom_orders')
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('customer_name', 'like', "%$s%")->orWhere('customer_email', 'like', "%$s%")))
            ->whereNotIn('status', ['cancelled'])
            ->groupBy('customer_email', 'customer_name')
            ->selectRaw('customer_name, customer_email, MAX(customer_phone) customer_phone, COUNT(*) orders, SUM(grand_total) spent, MAX(placed_at) last_order')
            ->orderByDesc('spent')->paginate(25)->withQueryString();

        return view('ecommerce.customers.index', ['rows' => $rows]);
    }
}
