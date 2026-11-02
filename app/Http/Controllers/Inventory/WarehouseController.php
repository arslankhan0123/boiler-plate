<?php

declare(strict_types=1);

namespace App\Http\Controllers\Inventory;

use App\Models\Inventory\Item;
use App\Models\Inventory\Stock;
use App\Models\Inventory\StockMovement;
use App\Models\Inventory\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WarehouseController extends InventoryController
{
    public function index()
    {
        $this->need('inventory.view');
        Warehouse::default();
        $summary = Stock::query()->join('inv_items', 'inv_items.id', '=', 'inv_stocks.item_id')
            ->where('inv_stocks.qty', '!=', 0)
            ->groupBy('warehouse_id')
            ->select('warehouse_id', DB::raw('COUNT(*) items'), DB::raw('SUM(inv_stocks.qty) qty'), DB::raw('SUM(inv_stocks.qty * inv_items.purchase_price) value'))
            ->get()->keyBy('warehouse_id');

        return view('inventory.warehouses', [
            'warehouses' => Warehouse::query()->orderByDesc('is_default')->orderBy('name')->get(),
            'summary' => $summary,
            'editing' => request('edit') ? Warehouse::query()->find(request('edit')) : null,
            'canEdit' => $this->can('inventory.manage_warehouses'),
        ]);
    }

    public function show(Warehouse $warehouse)
    {
        $this->need('inventory.view');
        $stocks = Stock::query()->with('item.category', 'item.unit', 'item.variant')->where('warehouse_id', $warehouse->id)
            ->where('qty', '!=', 0)
            ->whereHas('item', fn ($q) => $q->when(request('q'), fn ($w, $s) => $w->where(fn ($x) => $x->where('name', 'like', "%$s%")->orWhere('code', 'like', "%$s%")->orWhere('barcode', $s))))
            ->orderByDesc('qty')->paginate(40)->withQueryString();

        return view('inventory.warehouse-show', ['warehouse' => $warehouse, 'stocks' => $stocks]);
    }

    public function store(Request $request)
    {
        $this->need('inventory.manage_warehouses');
        $w = Warehouse::create($this->data($request));
        $this->single($w);

        return back()->with('success', 'Warehouse added.');
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        $this->need('inventory.manage_warehouses');
        $warehouse->update($this->data($request));
        $this->single($warehouse);

        return redirect()->route('inventory.warehouses.index')->with('success', 'Warehouse saved.');
    }

    public function destroy(Warehouse $warehouse)
    {
        $this->need('inventory.manage_warehouses');
        if ($warehouse->is_default) {
            return back()->with('error', 'The default warehouse cannot be deleted.');
        }
        if (StockMovement::query()->where('warehouse_id', $warehouse->id)->exists()) {
            return back()->with('error', 'This warehouse has stock history — set it inactive instead.');
        }
        $warehouse->delete();

        return back()->with('success', 'Warehouse deleted.');
    }

    private function single(Warehouse $w): void
    {
        if ($w->is_default) {
            Warehouse::query()->whereKeyNot($w->id)->update(['is_default' => false]);
        }
    }

    private function data(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', 'in:'.implode(',', array_keys(Warehouse::TYPES))],
            'contact_person' => ['nullable', 'string', 'max:150'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:150'],
            'city' => ['nullable', 'string', 'max:100'],
            'area' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
        ]) + ['is_default' => $request->boolean('is_default'), 'is_active' => $request->boolean('is_active', true)];
    }
}
