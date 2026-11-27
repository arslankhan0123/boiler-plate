<?php

declare(strict_types=1);

namespace App\Http\Controllers\Inventory;

use App\Models\Inventory\Attribute;
use App\Models\Inventory\Item;
use App\Models\Inventory\StockMovement;
use App\Models\Inventory\Warehouse;
use Illuminate\Http\Request;

/** Stock levels (item × warehouse) and the full stock ledger. */
class StockController extends InventoryController
{
    public function index(Request $request)
    {
        $this->need('inventory.view');
        $warehouses = Warehouse::query()->where('is_active', true)->orderByDesc('is_default')->orderBy('name')->get();
        $items = Item::query()->sellable()->where('is_service', false)->with('stocks', 'category', 'brand', 'unit', 'variant')
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%$s%")->orWhere('code', $s)->orWhere('barcode', $s)))
            ->when($request->category, fn ($q, $v) => $q->where('category_id', $v))
            ->when($request->brand, fn ($q, $v) => $q->where('brand_id', $v))
            ->when($request->only === 'low', fn ($q) => $q->where('alert_qty', '>', 0)->whereColumn('stock', '<=', 'alert_qty'))
            ->when($request->only === 'reorder', fn ($q) => $q->whereNotNull('min_qty')->whereColumn('stock', '<=', 'min_qty'))
            ->when($request->only === 'out', fn ($q) => $q->where('stock', '<=', 0))
            ->when($request->only === 'in', fn ($q) => $q->where('stock', '>', 0))
            ->orderBy('name')->paginate(50)->withQueryString();

        return view('inventory.stock', [
            'items' => $items, 'warehouses' => $warehouses,
            'categories' => Attribute::options('category'), 'brands' => Attribute::options('brand'),
            'totals' => [
                'qty' => (float) Item::query()->sellable()->where('is_service', false)->sum('stock'),
                'cost' => (float) Item::query()->sellable()->where('is_service', false)->where('stock', '>', 0)->selectRaw('SUM(stock * purchase_price) v')->value('v'),
                'sale' => (float) Item::query()->sellable()->where('is_service', false)->where('stock', '>', 0)->selectRaw('SUM(stock * sales_price) v')->value('v'),
            ],
        ]);
    }

    public function movements(Request $request)
    {
        $this->need('inventory.view');
        $rows = StockMovement::query()->with('item.variant', 'warehouse')
            ->when($request->item, fn ($q, $v) => $q->where('item_id', $v))
            ->when($request->q, fn ($q, $s) => $q->whereHas('item', fn ($w) => $w->where('name', 'like', "%$s%")->orWhere('code', $s)->orWhere('barcode', $s)))
            ->when($request->warehouse, fn ($q, $v) => $q->where('warehouse_id', $v))
            ->when($request->type, fn ($q, $v) => $q->where('type', $v))
            ->when($request->from, fn ($q, $v) => $q->whereDate('date', '>=', $v))
            ->when($request->to, fn ($q, $v) => $q->whereDate('date', '<=', $v))
            ->latest('date')->latest('id')->paginate(50)->withQueryString();

        return view('inventory.movements', ['rows' => $rows, 'warehouses' => Warehouse::options(), 'types' => StockMovement::TYPES]);
    }
}
