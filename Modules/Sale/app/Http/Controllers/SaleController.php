<?php

declare(strict_types=1);

namespace Modules\Sale\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Sale\Models\SaleOrder;

class SaleController extends Controller
{
    public function index(Request $r): JsonResponse
    {
        return ApiResponse::successResponse('Sales retrieved.', SaleOrder::with('items')->latest()->paginate($r->integer('per_page', 20)));
    }

    public function show(SaleOrder $saleOrder): JsonResponse
    {
        return ApiResponse::successResponse('Sale retrieved.', $saleOrder->load('items'));
    }

    public function store(Request $r): JsonResponse
    {
        $d = $r->validate(['warehouse_id' => 'required|exists:inventory_warehouses,id', 'customer_name' => 'nullable|string', 'customer_phone' => 'nullable|string', 'notes' => 'nullable|string', 'items' => 'required|array|min:1', 'items.*.product_id' => 'required|exists:ecommerce_products,id', 'items.*.quantity' => 'required|integer|min:1', 'items.*.unit_price' => 'required|numeric|min:0']);
        $s = SaleOrder::create(['warehouse_id' => $d['warehouse_id'], 'number' => 'SO-'.now()->format('YmdHis'), 'customer_name' => $d['customer_name'] ?? null, 'customer_phone' => $d['customer_phone'] ?? null, 'status' => 'draft', 'payment_status' => 'unpaid', 'subtotal' => 0, 'tax_total' => 0, 'discount_total' => 0, 'grand_total' => 0, 'notes' => $d['notes'] ?? null, 'created_by' => Auth::guard('api')->id()]);
        $total = 0;
        foreach ($d['items'] as $x) {
            $s->items()->create($x + ['name' => 'Product', 'quantity' => $x['quantity'], 'unit_price' => $x['unit_price'], 'tax_rate' => 0, 'tax_total' => 0, 'discount_total' => 0, 'line_total' => $x['quantity'] * $x['unit_price']]);
            $total += $x['quantity'] * $x['unit_price'];
        }$s->update(['subtotal' => $total, 'grand_total' => $total]);

        return ApiResponse::successResponse('Sale created.', $s->load('items'));
    }

    public function confirm(SaleOrder $saleOrder): JsonResponse
    {
        $saleOrder->update(['status' => 'confirmed']);

        return ApiResponse::successResponse('Sale confirmed.', $saleOrder);
    }
}
