<?php

declare(strict_types=1);

namespace App\Http\Controllers\Inventory;

use App\Models\Inventory\Adjustment;
use App\Models\Inventory\Attribute;
use App\Models\Inventory\Item;
use App\Models\Inventory\StockCount;
use App\Models\Inventory\Warehouse;
use App\Models\User;
use App\Support\Inventory\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Physical stock count. Build a sheet (snapshot of system stock), enter counted quantities,
 * then finalise — which creates a stock adjustment for the differences.
 */
class StockCountController extends InventoryController
{
    public function index(Request $request)
    {
        $this->need('inventory.view', 'inventory.adjust');
        $rows = StockCount::query()->with('warehouse')->withCount('items')
            ->when($request->q, fn ($q, $s) => $q->where('reference_no', 'like', "%$s%"))
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->warehouse, fn ($q, $v) => $q->where('warehouse_id', $v))
            ->latest('id')->paginate(25)->withQueryString();

        return view('inventory.counts.index', [
            'rows' => $rows, 'warehouses' => Warehouse::options(),
            'users' => User::query()->whereIn('id', $rows->pluck('created_by')->filter())->pluck('name', 'id'),
            'canEdit' => $this->can('inventory.adjust'),
        ]);
    }

    public function create()
    {
        $this->need('inventory.adjust');

        return view('inventory.counts.create', [
            'warehouses' => Warehouse::options(), 'default' => Warehouse::default()->id,
            'categories' => Attribute::options('category'), 'brands' => Attribute::options('brand'),
        ]);
    }

    /** Build the sheet: snapshot system stock for the chosen scope. */
    public function store(Request $request)
    {
        $this->need('inventory.adjust');
        $data = $request->validate([
            'warehouse_id' => ['required', 'integer', 'exists:inv_warehouses,id'],
            'date' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:1000'],
            'scope' => ['required', 'in:all,category,brand,in_stock'],
            'category_id' => ['nullable', 'integer'],
            'brand_id' => ['nullable', 'integer'],
        ]);

        $items = Item::query()->sellable()->where('is_service', false)
            ->when($data['scope'] === 'category', fn ($q) => $q->where('category_id', $data['category_id']))
            ->when($data['scope'] === 'brand', fn ($q) => $q->where('brand_id', $data['brand_id']))
            ->with(['stocks' => fn ($q) => $q->where('warehouse_id', $data['warehouse_id'])])
            ->orderBy('name')->get();
        if ($data['scope'] === 'in_stock') {
            $items = $items->filter(fn ($i) => $i->stockIn($data['warehouse_id']) != 0.0);
        }
        if ($items->isEmpty()) {
            return back()->withInput()->with('error', 'No items match this scope.');
        }

        $count = DB::transaction(function () use ($data, $items) {
            $count = StockCount::create([
                'reference_no' => StockService::nextNumber(StockCount::class, 'reference_no', 'SC-'),
                'warehouse_id' => $data['warehouse_id'], 'date' => $data['date'], 'note' => $data['note'] ?? null,
                'status' => 'draft', 'created_by' => auth()->id(),
            ]);
            foreach ($items as $i) {
                $count->items()->create(['item_id' => $i->id, 'system_qty' => $i->stockIn($data['warehouse_id']), 'counted_qty' => null]);
            }

            return $count;
        });

        return redirect()->route('inventory.counts.show', $count)->with('success', 'Count sheet created with '.$items->count().' items.');
    }

    public function show(StockCount $count)
    {
        $this->need('inventory.view');

        return view('inventory.counts.show', [
            'count' => $count->load('warehouse', 'items.item.unit', 'items.item.variant'),
            'by' => User::query()->whereKey($count->created_by)->value('name'),
            'canEdit' => $this->can('inventory.adjust') && $count->status === 'draft',
        ]);
    }

    /** Save entered counts (draft stays draft). */
    public function update(Request $request, StockCount $count)
    {
        $this->need('inventory.adjust');
        abort_unless($count->status === 'draft', 422);
        $counts = $request->input('counts', []);
        DB::transaction(function () use ($count, $counts, $request) {
            foreach ($count->items as $line) {
                $v = $counts[$line->id] ?? null;
                $line->update(['counted_qty' => ($v === null || $v === '') ? null : (float) $v, 'note' => $request->input('notes.'.$line->id)]);
            }
        });

        return back()->with('success', 'Counts saved.');
    }

    /** Finalise: create an adjustment for every counted line whose count differs from the system qty. */
    public function finalize(Request $request, StockCount $count)
    {
        $this->need('inventory.adjust');
        abort_unless($count->status === 'draft', 422);
        $counts = $request->input('counts', []);

        return DB::transaction(function () use ($count, $counts, $request) {
            // Save the latest entered counts first.
            foreach ($count->items as $line) {
                $v = $counts[$line->id] ?? $line->counted_qty;
                $line->update(['counted_qty' => ($v === null || $v === '') ? null : (float) $v, 'note' => $request->input('notes.'.$line->id, $line->note)]);
            }
            $count->refresh()->load('items');

            $diffs = $count->items->filter(fn ($l) => $l->counted_qty !== null && abs($l->diff()) > 0.0001);
            if ($diffs->isEmpty()) {
                $count->update(['status' => 'finalized']);

                return redirect()->route('inventory.counts.show', $count)->with('success', 'Count finalised — everything matched, no adjustment needed.');
            }

            $adj = Adjustment::create([
                'kind' => 'adjustment',
                'reference_no' => StockService::nextNumber(Adjustment::class, 'reference_no', 'ADJ-'),
                'warehouse_id' => $count->warehouse_id, 'date' => $count->date->toDateString(),
                'note' => 'Stock count '.$count->reference_no.($count->note ? ' — '.$count->note : ''), 'created_by' => auth()->id(),
            ]);
            foreach ($diffs as $l) {
                $adj->items()->create(['item_id' => $l->item_id, 'qty' => $l->diff(), 'description' => 'Count: '.$l->system_qty.' → '.$l->counted_qty]);
                StockService::move($l->item_id, $count->warehouse_id, $l->diff(), 'adjustment', $adj, 'Stock count '.$count->reference_no, $count->date->toDateString());
            }
            $count->update(['status' => 'finalized', 'adjustment_id' => $adj->id]);

            return redirect()->route('inventory.counts.show', $count)->with('success', $diffs->count().' item(s) adjusted. Adjustment '.$adj->reference_no.' created.');
        });
    }

    public function destroy(StockCount $count)
    {
        $this->need('inventory.adjust');
        if ($count->status === 'finalized') {
            return back()->with('error', 'A finalised count cannot be deleted. Delete its adjustment from Stock Adjustments to reverse the stock.');
        }
        $count->delete();

        return redirect()->route('inventory.counts.index')->with('success', 'Count sheet deleted.');
    }
}
