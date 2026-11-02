<?php

declare(strict_types=1);

namespace App\Http\Controllers\Ecommerce;

use App\Models\Ecommerce\Order;
use App\Models\Ecommerce\Product;
use Illuminate\Support\Facades\DB;

/** Store analytics dashboard. */
class AnalyticsController extends EcommerceController
{
    public function index()
    {
        $this->need('ecommerce.reports');
        $valid = Order::whereNotIn('status', ['cancelled']);
        $orderCount = (clone $valid)->count();
        $revenue = (clone $valid)->sum('grand_total');

        $topProducts = DB::connection('tenant')->table('ecom_order_items')
            ->groupBy('name')->selectRaw('name, SUM(qty) qty, SUM(line_total) total')
            ->orderByDesc('qty')->limit(10)->get();

        return view('ecommerce.analytics.index', [
            'orderCount' => $orderCount,
            'revenue' => $revenue,
            'avgOrder' => $orderCount ? round($revenue / $orderCount, 2) : 0,
            'published' => Product::where('is_published', true)->count(),
            'byStatus' => Order::query()->selectRaw('status, COUNT(*) n, SUM(grand_total) total')->groupBy('status')->get(),
            'topProducts' => $topProducts,
            'pending' => Order::where('status', 'pending')->count(),
        ]);
    }
}
