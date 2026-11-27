<?php

declare(strict_types=1);

namespace App\Http\Controllers\Inventory;

use App\Models\Inventory\Adjustment;
use App\Models\Inventory\Item;
use App\Models\Inventory\Warehouse;
use App\Models\User;
use App\Support\Inventory\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Stock adjustments (qty + adds, - removes) and damaged items (always removed from stock).
 * The route passes the kind: 'adjustment' or 'damage'.
 */
class AdjustmentController extends InventoryController
{
    private const META = [
        'adjustment' => ['title' => 'Stock Adjustments', 'one' => 'Adjustment', 'prefix' => 'ADJ-', 'route' => 'inventory.adjustments', 'icon' => 'sliders2'],
        'damage' => ['title' => 'Damaged Items', 'one' => 'Damage entry', 'prefix' => 'DMG-', 'route' => 'inventory.damages', 'icon' => 'exclamation-octagon'],
    ];

    public function index(Request $request, string $kind)
    {
        $this->need('inventory.view', 'inventory.adjust');
        $rows = Adjustment::query()->with('warehouse')->withCount('items')->withSum('items', 'qty')->where('kind', $kind)
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('reference_no', 'like', "%$s%")->orWhere('note', 'like', "%$s%")))
            ->when($request->warehouse, fn ($q, $v) => $q->where('warehouse_id', $v))
            ->when($request->from, fn ($q, $v) => $q->whereDate('date', '>=', $v))
            ->when($request->to, fn ($q, $v) => $q->whereDate('date', '<=', $v))
            ->latest('date')->latest('id')->paginate(25)->withQueryString();

        return view('inventory.adjustments.index', [
            'kind' => $kind, 'meta' => self::META[$kind], 'rows' => $rows, 'warehouses' => Warehouse::options(),
            'users' => User::query()->whereIn('id', $rows->pluck('created_by')->filter())->pluck('name', 'id'),
            'canEdit' => $this->can('inventory.adjust'),
        ]);
    }

    public function create(string $kind)
    {
        $this->need('inventory.adjust');

        return view('inventory.adjustments.form', $this->form($kind, new Adjustment([
            'kind' => $kind, 'date' => today(), 'warehouse_id' => Warehouse::default()->id,
            'reference_no' => StockService::nextNumber(Adjustment::class, 'reference_no', self::META[$kind]['prefix']),
        ])));
    }

    public function edit(Adjustment $adjustment, string $kind)
    {
        $this->need('inventory.adjust');
        abort_unless($adjustment->kind === $kind, 404);

        return view('inventory.adjustments.form', $this->form($kind, $adjustment->load('items.item.variant', 'items.item.stocks')));
    }

    public function show(Adjustment $adjustment, string $kind)
    {
        $this->need('inventory.view');
        abort_unless($adjustment->kind === $kind, 404);

        return view('inventory.adjustments.show', [
            'kind' => $kind, 'meta' => self::META[$kind], 'doc' => $adjustment->load('items.item.unit', 'items.item.variant', 'warehouse'),
            'by' => User::query()->whereKey($adjustment->created_by)->value('name'),
            'canEdit' => $this->can('inventory.adjust'),
        ]);
    }

    public function store(Request $request, string $kind)
    {
        $this->need('inventory.adjust');
        $doc = $this->save($request, $kind, new Adjustment(['kind' => $kind]));

        return redirect()->route(self::META[$kind]['route'].'.show', $doc)->with('success', self::META[$kind]['one'].' saved.');
    }

    public function update(Request $request, Adjustment $adjustment, string $kind)
    {
        $this->need('inventory.adjust');
        abort_unless($adjustment->kind === $kind, 404);
        $this->save($request, $kind, $adjustment);

        return redirect()->route(self::META[$kind]['route'].'.show', $adjustment)->with('success', self::META[$kind]['one'].' updated.');
    }

    public function destroy(Adjustment $adjustment, string $kind)
    {
        $this->need('inventory.adjust');
        abort_unless($adjustment->kind === $kind, 404);
        DB::transaction(function () use ($adjustment) {
            StockService::reverse($adjustment);
            $adjustment->delete();
        });

        return redirect()->route(self::META[$kind]['route'].'.index')->with('success', 'Deleted — stock restored.');
    }

    private function form(string $kind, Adjustment $doc): array
    {
        return [
            'kind' => $kind, 'meta' => self::META[$kind], 'doc' => $doc, 'warehouses' => Warehouse::options(),
            'lines' => $doc->exists ? $doc->items->map(fn ($l) => ItemController::json($l->item, $doc->warehouse_id) + ['qty' => $l->qty, 'description' => $l->description])->values() : collect(),
        ];
    }

    private function save(Request $request, string $kind, Adjustment $doc): Adjustment
    {
        $data = $request->validate([
            'reference_no' => ['required', 'string', 'max:50', 'unique:inv_adjustments,reference_no'.($doc->exists ? ','.$doc->id : '')],
            'warehouse_id' => ['required', 'integer', 'exists:inv_warehouses,id'],
            'date' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array'],
        ]);
        $rows = $this->rows($data['items'], signed: $kind === 'adjustment');
        if (! $rows) {
            throw \Illuminate\Validation\ValidationException::withMessages(['items' => 'Add at least one item with a quantity.']);
        }

        return DB::transaction(function () use ($doc, $data, $rows, $kind) {
            if ($doc->exists) {
                StockService::reverse($doc);
                $doc->items()->delete();
            }
            $doc->fill(collect($data)->except('items')->all() + ['created_by' => $doc->created_by ?? auth()->id()])->save();

            foreach ($rows as $r) {
                $doc->items()->create($r);
                $qty = $kind === 'damage' ? -$r['qty'] : $r['qty'];
                StockService::move($r['item_id'], (int) $data['warehouse_id'], $qty, $kind, $doc, $data['note'] ?? null, $data['date']);
            }

            return $doc;
        });
    }
}
