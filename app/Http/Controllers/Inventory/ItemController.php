<?php

declare(strict_types=1);

namespace App\Http\Controllers\Inventory;

use App\Models\Inventory\Attribute;
use App\Models\Inventory\Item;
use App\Models\Inventory\Stock;
use App\Models\Inventory\StockMovement;
use App\Models\Inventory\Warehouse;
use App\Models\Settings\TaxRate;
use App\Support\Inventory\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Modules\Setup\Models\Supplier;

/**
 * Items & services. Pricing follows the retail rules:
 *   purchase price = cost (+ tax when tax is exclusive), sales price = purchase price + profit margin %.
 * Variant items: a parent row + one child row per variant (each with its own code, barcode, prices and stock).
 */
class ItemController extends InventoryController
{
    public function index(Request $request)
    {
        $this->need('inventory.view');
        $wid = $request->integer('warehouse') ?: null;

        $items = Item::query()
            ->with(['category', 'brand', 'unit', 'tax', 'variant', 'stocks', 'children.variant', 'children.stocks'])
            ->whereNull('parent_id')
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%$s%")->orWhere('code', 'like', "%$s%")
                ->orWhere('barcode', $s)->orWhere('sku', 'like', "%$s%")
                ->orWhereHas('children', fn ($c) => $c->where('barcode', $s)->orWhere('code', $s))))
            ->when($request->category, fn ($q, $v) => $q->where('category_id', $v))
            ->when($request->brand, fn ($q, $v) => $q->where('brand_id', $v))
            ->when($request->department, fn ($q, $v) => $q->where('department_id', $v))
            ->when($request->kind === 'service', fn ($q) => $q->where('is_service', true))
            ->when($request->kind === 'item', fn ($q) => $q->where('is_service', false))
            ->when($request->stock === 'low', fn ($q) => $q->where('is_service', false)->where('alert_qty', '>', 0)->whereColumn('stock', '<=', 'alert_qty'))
            ->when($request->stock === 'out', fn ($q) => $q->where('is_service', false)->where('stock', '<=', 0))
            ->when($request->stock === 'expired', fn ($q) => $q->whereNotNull('expire_date')->whereDate('expire_date', '<', today()))
            ->when($request->status === 'inactive', fn ($q) => $q->where('is_active', false), fn ($q) => $request->status === 'all' ? $q : $q->where('is_active', true))
            ->orderByDesc('id')->paginate(25)->withQueryString();

        return view('inventory.items.index', [
            'items' => $items,
            'wid' => $wid,
            'warehouses' => Warehouse::options(),
            'categories' => Attribute::options('category'),
            'brands' => Attribute::options('brand'),
            'departments' => Attribute::options('department'),
            'stats' => [
                'items' => Item::query()->sellable()->count(),
                'low' => Item::query()->sellable()->where('is_service', false)->where('alert_qty', '>', 0)->whereColumn('stock', '<=', 'alert_qty')->count(),
                'out' => Item::query()->sellable()->where('is_service', false)->where('stock', '<=', 0)->count(),
                'value' => (float) Item::query()->sellable()->where('is_service', false)->where('stock', '>', 0)->sum(DB::raw('stock * purchase_price')),
            ],
            'canEdit' => $this->can('inventory.manage_products'),
        ]);
    }

    public function create(Request $request)
    {
        $this->need('inventory.manage_products');

        return view('inventory.items.form', $this->formData(new Item([
            'item_group' => 'single', 'is_service' => $request->boolean('service'), 'tax_type' => 'exclusive', 'discount_type' => 'percentage',
            'is_active' => true, 'unit_id' => array_key_first(Attribute::options('unit')),
        ])));
    }

    public function edit(Item $item)
    {
        $this->need('inventory.manage_products');
        if ($item->parent_id) {
            return redirect()->route('inventory.items.edit', $item->parent_id);
        }

        return view('inventory.items.form', $this->formData($item->load('children.variant', 'children.stocks')));
    }

    public function show(Item $item)
    {
        $this->need('inventory.view');
        $item->load(['category', 'subcategory', 'department', 'brand', 'unit', 'color', 'size', 'rack', 'bin', 'sub_bin', 'tax', 'supplier', 'variant', 'stocks.warehouse', 'children.variant', 'children.stocks.warehouse', 'parent']);
        $ids = $item->item_group === 'variant' ? $item->children->pluck('id')->all() : [$item->id];

        return view('inventory.items.show', [
            'item' => $item,
            'movements' => StockMovement::query()->with('warehouse', 'item.variant')->whereIn('item_id', $ids)->latest('date')->latest('id')->paginate(20),
            'canEdit' => $this->can('inventory.manage_products'),
        ]);
    }

    public function store(Request $request)
    {
        $this->need('inventory.manage_products');
        $item = DB::transaction(fn () => $this->save($request, new Item()));

        return $request->boolean('save_new')
            ? redirect()->route('inventory.items.create', ['service' => $item->is_service ? 1 : null])->with('success', "{$item->name} saved. Add the next one.")
            : redirect()->route('inventory.items.show', $item)->with('success', "{$item->name} saved.");
    }

    public function update(Request $request, Item $item)
    {
        $this->need('inventory.manage_products');
        DB::transaction(fn () => $this->save($request, $item));

        return redirect()->route('inventory.items.show', $item)->with('success', 'Item updated.');
    }

    public function toggle(Item $item)
    {
        $this->need('inventory.manage_products');
        $item->update(['is_active' => ! $item->is_active]);
        $item->children()->update(['is_active' => $item->is_active]);

        return back()->with('success', $item->name.' is now '.($item->is_active ? 'active' : 'inactive').'.');
    }

    public function destroy(Item $item)
    {
        $this->need('inventory.manage_products');
        $ids = array_merge([$item->id], $item->children()->pluck('id')->all());
        $used = StockMovement::query()->whereIn('item_id', $ids)->where('type', '!=', 'opening')->exists();
        if ($used) {
            $item->update(['is_active' => false]);
            $item->children()->update(['is_active' => false]);

            return back()->with('success', 'Item has transactions, so it was set inactive instead of deleted.');
        }
        DB::transaction(function () use ($item, $ids) {
            StockMovement::query()->whereIn('item_id', $ids)->delete();
            Stock::query()->whereIn('item_id', $ids)->delete();
            $item->children()->delete();
            $item->delete();
        });

        return redirect()->route('inventory.items.index')->with('success', 'Item deleted.');
    }

    public function bulk(Request $request)
    {
        $this->need('inventory.manage_products');
        $data = $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer'], 'action' => ['required', 'in:activate,deactivate,delete']]);
        $done = 0;
        foreach (Item::query()->whereIn('id', $data['ids'])->get() as $item) {
            if ($data['action'] === 'delete') {
                $this->destroy($item);
            } else {
                $item->update(['is_active' => $data['action'] === 'activate']);
                $item->children()->update(['is_active' => $data['action'] === 'activate']);
            }
            $done++;
        }

        return back()->with('success', "$done item(s) updated.");
    }

    /**
     * JSON search used by every document screen (sales, POS, purchase, adjustments…):
     * matches name / code / barcode / sku and returns sellable rows with price, tax and stock.
     */
    public function search(Request $request)
    {
        $this->need('inventory.view', 'sales.view', 'pos.sell', 'purchase.view');
        $q = trim((string) $request->q);
        $wid = $request->integer('warehouse') ?: null;

        $items = Item::query()->sellable()->with('tax', 'variant', 'unit', 'stocks')
            ->when($request->boolean('stock_only'), fn ($w) => $w->where('is_service', false))
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('barcode', $q)->orWhere('code', $q)->orWhere('name', 'like', "%$q%")->orWhere('sku', 'like', "%$q%")))
            ->when($request->category, fn ($w, $v) => $w->where('category_id', $v))
            ->when($request->brand, fn ($w, $v) => $w->where('brand_id', $v))
            ->orderByRaw('CASE WHEN barcode = ? OR code = ? THEN 0 ELSE 1 END', [$q, $q])->orderBy('name')
            ->limit($request->integer('limit', 20))->get();

        return response()->json($items->map(fn (Item $i) => self::json($i, $wid)));
    }

    public static function json(Item $i, ?int $wid = null): array
    {
        return [
            'id' => $i->id, 'code' => $i->code, 'barcode' => $i->barcode, 'name' => $i->displayName(),
            'unit' => $i->unit->name ?? null, 'is_service' => $i->is_service,
            'price' => $i->price, 'purchase_price' => $i->purchase_price, 'sales_price' => $i->sales_price, 'mrp' => $i->mrp,
            'tax_id' => $i->tax_id, 'tax_rate' => $i->taxRate(), 'tax_type' => $i->tax_type,
            'discount_type' => $i->discount_type, 'discount' => $i->discount, 'seller_points' => $i->seller_points,
            'stock' => $i->stockIn($wid), 'image' => $i->imageUrl(), 'expire_date' => $i->expire_date?->toDateString(),
        ];
    }

    /** Barcode labels: pick items + copies, then print. */
    public function labels(Request $request)
    {
        $this->need('inventory.view');
        $rows = collect($request->input('rows', []))->filter(fn ($r) => ! empty($r['item_id']) && (int) ($r['copies'] ?? 0) > 0);
        $items = Item::query()->with('variant')->whereIn('id', $rows->pluck('item_id'))->get()->keyBy('id');

        return view('inventory.items.labels', [
            'rows' => $rows->map(fn ($r) => ['item' => $items[$r['item_id']] ?? null, 'copies' => min(500, (int) $r['copies'])])->filter(fn ($r) => $r['item'])->values(),
            'opts' => [
                'name' => $request->boolean('show_name', true), 'price' => $request->boolean('show_price', true),
                'code' => $request->boolean('show_code', true), 'company' => $request->boolean('show_company'),
                'per_row' => max(1, min(6, $request->integer('per_row', 3))),
            ],
            'preselect' => $request->filled('item') ? Item::query()->sellable()->with('variant')->whereIn('id', (array) $request->item)->get() : collect(),
            'print' => $rows->isNotEmpty(),
        ]);
    }

    private function formData(Item $item): array
    {
        $taxes = TaxRate::query()->where('is_active', true)->orderBy('name')->get();

        return [
            'item' => $item,
            'opts' => collect(Item::ATTRS)->mapWithKeys(fn ($a) => [$a => Attribute::options($a)])->all(),
            'catParents' => Attribute::query()->whereIn('type', ['category', 'subcategory', 'sub_bin'])->pluck('parent_id', 'id'),
            'taxes' => $taxes,
            'suppliers' => Supplier::query()->where('is_active', true)->orderBy('company_name')->pluck('company_name', 'id'),
            'warehouses' => Warehouse::options(),
            'defaultWarehouse' => Warehouse::default()->id,
        ];
    }

    private function save(Request $request, Item $item): Item
    {
        $isNew = ! $item->exists;
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'item_group' => ['required', 'in:single,variant'],
            'is_service' => ['nullable', 'boolean'],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('inv_items', 'code')->ignore($item->id)],
            'barcode' => [\App\Support\Settings::get('barcode_mode', 'auto') === 'manual' && ! $request->boolean('is_service') && $request->input('item_group') !== 'variant' ? 'required' : 'nullable', 'string', 'max:100'],
            'sku' => ['nullable', 'string', 'max:100'],
            'hsn' => ['nullable', 'string', 'max:50'],
            ...collect(Item::ATTRS)->mapWithKeys(fn ($a) => [$a.'_id' => ['nullable', 'integer', Rule::exists('inv_attributes', 'id')->where('type', $a)]])->all(),
            'supplier_id' => ['nullable', 'integer'],
            'alert_qty' => ['nullable', 'numeric', 'min:0'],
            'min_qty' => ['nullable', 'numeric', 'min:0'],
            'max_qty' => ['nullable', 'numeric', 'min:0'],
            'reorder_qty' => ['nullable', 'numeric', 'min:0'],
            'lot_number' => ['nullable', 'string', 'max:100'],
            'expire_date' => ['nullable', 'date'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'tax_id' => ['nullable', 'integer', 'exists:tax_rates,id'],
            'tax_type' => ['required', 'in:exclusive,inclusive'],
            'profit_margin' => ['nullable', 'numeric'],
            'sales_price' => ['nullable', 'numeric', 'min:0'],
            'mrp' => ['nullable', 'numeric', 'min:0'],
            'discount_type' => ['required', 'in:percentage,fixed'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'seller_points' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:5000'],
            'image' => ['nullable', 'image', 'max:2048'],
            'opening_qty' => ['nullable', 'numeric'],
            'opening_warehouse_id' => ['nullable', 'integer', 'exists:inv_warehouses,id'],
            'variants' => ['nullable', 'array'],
            'variants.*.id' => ['nullable', 'integer'],
            'variants.*.variant_id' => ['nullable', 'integer', Rule::exists('inv_attributes', 'id')->where('type', 'variant')],
            'variants.*.sku' => ['nullable', 'string', 'max:100'],
            'variants.*.barcode' => ['nullable', 'string', 'max:100'],
            'variants.*.price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.sales_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.mrp' => ['nullable', 'numeric', 'min:0'],
            'variants.*.opening_qty' => ['nullable', 'numeric'],
        ], [], ['variants.*.variant_id' => 'variant']);

        $isService = $request->boolean('is_service');
        $taxRate = (float) (TaxRate::query()->whereKey($data['tax_id'] ?? 0)->value('rate') ?? 0);
        $prices = self::prices((float) ($data['price'] ?? 0), $taxRate, $data['tax_type'], $data['profit_margin'] ?? null, $data['sales_price'] ?? null);

        $attrs = collect($data)->only(array_merge(
            ['name', 'item_group', 'sku', 'hsn', 'supplier_id', 'lot_number', 'expire_date', 'tax_id', 'tax_type', 'mrp', 'discount_type', 'description'],
            array_map(fn ($a) => $a.'_id', Item::ATTRS),
        ))->all() + [
            'is_service' => $isService,
            'alert_qty' => $data['alert_qty'] ?? 0, 'min_qty' => $data['min_qty'] ?? null, 'max_qty' => $data['max_qty'] ?? null, 'reorder_qty' => $data['reorder_qty'] ?? null,
            'discount' => $data['discount'] ?? 0, 'seller_points' => $data['seller_points'] ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ] + $prices;
        if ($isService) {
            $attrs['item_group'] = 'single';
        }
        $attrs['variant_id'] = null;

        if ($request->hasFile('image')) {
            if ($item->image) {
                Storage::disk('public')->delete($item->image);
            }
            $attrs['image'] = $request->file('image')->store(tenant_upload_path('inventory/items'), 'public');
        } elseif ($request->boolean('remove_image') && $item->image) {
            Storage::disk('public')->delete($item->image);
            $attrs['image'] = null;
        }

        if ($isNew) {
            $attrs['code'] = ($data['code'] ?? null) ?: StockService::nextNumber(Item::class, 'code', 'IT');
            $attrs['created_by'] = auth()->id();
        } elseif (! empty($data['code'])) {
            $attrs['code'] = $data['code'];
        }
        $attrs['barcode'] = ($data['barcode'] ?? null) ?: ($attrs['code'] ?? $item->code);

        $item->fill($attrs)->save();

        $warehouseId = (int) ($data['opening_warehouse_id'] ?? 0) ?: Warehouse::default()->id;

        if ($item->item_group === 'variant') {
            $this->saveVariants($item, $data['variants'] ?? [], $taxRate, $warehouseId);
        } else {
            // Opening stock (on create) or an opening-stock adjustment (on edit).
            $qty = (float) ($data['opening_qty'] ?? 0);
            if ($qty != 0.0 && ! $isService) {
                StockService::move($item, $warehouseId, $qty, 'opening', $item, $isNew ? 'Opening stock' : 'Opening stock adjustment');
                $item->increment('opening_stock', $qty);
            }
            $item->children()->update(['is_active' => false]);
        }

        return $item;
    }

    private function saveVariants(Item $parent, array $rows, float $taxRate, int $warehouseId): void
    {
        $keep = [];
        foreach ($rows as $row) {
            if (empty($row['variant_id'])) {
                continue;
            }
            $child = ! empty($row['id']) ? $parent->children()->find($row['id']) : null;
            $child ??= $parent->children()->where('variant_id', $row['variant_id'])->first() ?? new Item(['parent_id' => $parent->id]);
            $isNew = ! $child->exists;
            $prices = self::prices((float) ($row['price'] ?? $parent->price), $taxRate, $parent->tax_type, $parent->profit_margin, $row['sales_price'] ?? null);

            $child->fill([
                'parent_id' => $parent->id, 'item_group' => 'single', 'variant_id' => $row['variant_id'],
                'name' => $parent->name, 'sku' => $row['sku'] ?? null, 'mrp' => $row['mrp'] ?? null,
                ...collect($parent->only(array_merge(['hsn', 'supplier_id', 'tax_id', 'tax_type', 'discount_type', 'discount', 'seller_points', 'alert_qty', 'min_qty', 'max_qty', 'reorder_qty', 'lot_number', 'expire_date', 'is_active'], array_map(fn ($a) => $a.'_id', array_diff(Item::ATTRS, ['variant'])))))->all(),
                ...$prices,
            ]);
            if ($isNew) {
                $child->code = StockService::nextNumber(Item::class, 'code', 'IT');
                $child->created_by = auth()->id();
            }
            $child->barcode = ($row['barcode'] ?? null) ?: $child->code;
            $child->save();
            $keep[] = $child->id;

            $qty = (float) ($row['opening_qty'] ?? 0);
            if ($qty != 0.0) {
                StockService::move($child, $warehouseId, $qty, 'opening', $child, $isNew ? 'Opening stock' : 'Opening stock adjustment');
                $child->increment('opening_stock', $qty);
            }
        }
        // Variants removed from the form: delete if unused, otherwise deactivate.
        foreach ($parent->children()->whereNotIn('id', $keep)->get() as $old) {
            StockMovement::query()->where('item_id', $old->id)->where('type', '!=', 'opening')->exists()
                ? $old->update(['is_active' => false])
                : $old->forceDelete();
        }
        StockService::syncItemTotal($parent->id);
    }

    /** Retail pricing rules. */
    public static function prices(float $price, float $taxRate, string $taxType, mixed $margin, mixed $salesPrice): array
    {
        $purchase = $taxType === 'exclusive' ? $price + ($price * $taxRate / 100) : $price;
        if ($salesPrice !== null && $salesPrice !== '') {
            $sales = (float) $salesPrice;
            $margin = $purchase > 0 ? round((($sales - $purchase) / $purchase) * 100, 4) : null;
        } else {
            $margin = $margin === null || $margin === '' ? null : (float) $margin;
            $sales = $purchase + ($purchase * (float) $margin / 100);
        }

        return ['price' => round($price, 4), 'purchase_price' => round($purchase, 4), 'profit_margin' => $margin, 'sales_price' => round($sales, 4)];
    }
}
