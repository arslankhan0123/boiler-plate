<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Ecommerce\Models\Product;
use Modules\Inventory\Models\Stock;
use Modules\Inventory\Models\StockMovement;
use Modules\Inventory\Models\Warehouse;

class InventoryController extends Controller
{
    public function index(): JsonResponse
    {
        return ApiResponse::successResponse('Inventory dashboard.', ['warehouses' => Warehouse::count(), 'low_stock' => Stock::whereColumn('quantity', '<=', 'reorder_level')->count()]);
    }

    public function stocks(Request $r): JsonResponse
    {
        return ApiResponse::successResponse('Stocks retrieved.', Stock::paginate($r->integer('per_page', 20)));
    }

    public function store(Request $r): JsonResponse
    {
        $d = $r->validate(['name' => 'required|string', 'code' => 'required|string|unique:inventory_warehouses,code', 'address' => 'nullable|string']);

        return ApiResponse::successResponse('Warehouse created.', Warehouse::create($d + ['created_by' => Auth::guard('api')->id()]));
    }

    public function update(Request $r, Warehouse $warehouse): JsonResponse
    {
        $warehouse->update($r->validate(['name' => 'sometimes|string', 'code' => 'sometimes|string|unique:inventory_warehouses,code,'.$warehouse->id, 'address' => 'nullable|string', 'is_active' => 'boolean']));

        return ApiResponse::successResponse('Warehouse updated.', $warehouse);
    }

    public function destroy(Warehouse $warehouse): JsonResponse
    {
        $warehouse->delete();

        return ApiResponse::successResponse('Warehouse deleted.');
    }

    public function movement(Request $r): JsonResponse
    {
        $d = $r->validate(['warehouse_id' => 'required|exists:inventory_warehouses,id', 'product_id' => 'required|exists:ecommerce_products,id', 'type' => 'required|in:opening,receipt,adjustment_in,adjustment_out,transfer_in,transfer_out,return', 'quantity' => 'required|integer|not_in:0', 'notes' => 'nullable|string']);

        return ApiResponse::successResponse('Stock movement recorded.', DB::transaction(function () use ($d) {
            $delta = in_array($d['type'], ['adjustment_out', 'transfer_out']) ? -abs($d['quantity']) : abs($d['quantity']);
            $s = Stock::firstOrCreate(['warehouse_id' => $d['warehouse_id'], 'product_id' => $d['product_id']], ['quantity' => 0]);
            abort_if($s->quantity + $delta < 0, 422, 'Insufficient stock.');
            $s->increment('quantity', $delta);
            Product::whereKey($d['product_id'])->increment('stock_quantity', $delta);

            return StockMovement::create($d + ['quantity' => $delta, 'created_by' => Auth::guard('api')->id()]);
        }));
    }
}
