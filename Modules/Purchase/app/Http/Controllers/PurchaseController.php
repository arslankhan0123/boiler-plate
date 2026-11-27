<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Purchase\Models\PurchaseOrder;

class PurchaseController extends Controller
{
    public function index(Request $r): JsonResponse
    {
        return ApiResponse::successResponse('Purchases retrieved.', PurchaseOrder::with('items')->latest()->paginate($r->integer('per_page', 20)));
    }

    public function show(PurchaseOrder $purchaseOrder): JsonResponse
    {
        return ApiResponse::successResponse('Purchase retrieved.', $purchaseOrder->load('items'));
    }

    public function store(Request $r): JsonResponse
    {
        $d = $r->validate(['warehouse_id' => 'required|exists:inventory_warehouses,id', 'supplier_name' => 'required|string', 'supplier_invoice' => 'nullable|string', 'notes' => 'nullable|string', 'items' => 'required|array|min:1', 'items.*.product_id' => 'required|exists:ecommerce_products,id', 'items.*.quantity' => 'required|integer|min:1', 'items.*.unit_cost' => 'required|numeric|min:0']);
        $p = PurchaseOrder::create(['warehouse_id' => $d['warehouse_id'], 'number' => 'PO-'.now()->format('YmdHis'), 'supplier_name' => $d['supplier_name'], 'supplier_invoice' => $d['supplier_invoice'] ?? null, 'status' => 'draft', 'subtotal' => 0, 'tax_total' => 0, 'discount_total' => 0, 'grand_total' => 0, 'notes' => $d['notes'] ?? null, 'created_by' => Auth::guard('api')->id()]);
        $total = 0;
        foreach ($d['items'] as $x) {
            $p->items()->create($x + ['name' => 'Product', 'quantity' => $x['quantity'], 'unit_cost' => $x['unit_cost'], 'tax_rate' => 0, 'tax_total' => 0, 'discount_total' => 0, 'line_total' => $x['quantity'] * $x['unit_cost']]);
            $total += $x['quantity'] * $x['unit_cost'];
        }$p->update(['subtotal' => $total, 'grand_total' => $total]);

        return ApiResponse::successResponse('Purchase created.', $p->load('items'));
    }

    public function receive(PurchaseOrder $purchaseOrder): JsonResponse
    {
        $purchaseOrder->update(['status' => 'received', 'received_at' => now()]);

        return ApiResponse::successResponse('Purchase received.', $purchaseOrder);
    }
}
