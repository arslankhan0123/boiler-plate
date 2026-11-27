<?php

declare(strict_types=1);

namespace App\Http\Controllers\Inventory;

use App\Models\Inventory\Attribute;
use App\Models\Inventory\Item;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Departments, categories, sub categories, brands, units, variants, colors, sizes, racks, bins, sub bins. */
class AttributeController extends InventoryController
{
    public function index(string $type)
    {
        $this->need('inventory.view');
        abort_unless(isset(Attribute::TYPES[$type]), 404);
        [, , $parentType] = Attribute::TYPES[$type];

        $usage = in_array($type, Item::ATTRS, true)
            ? Item::query()->whereNotNull($type.'_id')->selectRaw($type.'_id as a, COUNT(*) c')->groupBy($type.'_id')->pluck('c', 'a')
            : collect();

        return view('inventory.attributes', [
            'type' => $type,
            'meta' => Attribute::TYPES[$type],
            'rows' => Attribute::query()->with('parent')->where('type', $type)
                ->when(request('q'), fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%$s%")->orWhere('code', 'like', "%$s%")))
                ->orderBy('name')->paginate(30)->withQueryString(),
            'parents' => $parentType ? Attribute::options($parentType) : [],
            'parentLabel' => $parentType ? Attribute::TYPES[$parentType][0] : null,
            'usage' => $usage,
            'counts' => Attribute::query()->selectRaw('type, COUNT(*) c')->groupBy('type')->pluck('c', 'type'),
            'editing' => request('edit') ? Attribute::query()->where('type', $type)->find(request('edit')) : null,
            'canEdit' => $this->can('inventory.manage_products'),
        ]);
    }

    public function store(Request $request, string $type)
    {
        $this->need('inventory.manage_products');
        $attr = Attribute::create($this->data($request, $type) + ['type' => $type]);

        return $request->wantsJson()
            ? response()->json(['id' => $attr->id, 'name' => $attr->name])
            : back()->with('success', Attribute::TYPES[$type][0].' added.');
    }

    public function update(Request $request, string $type, Attribute $attribute)
    {
        $this->need('inventory.manage_products');
        abort_unless($attribute->type === $type, 404);
        $attribute->update($this->data($request, $type, $attribute));

        return redirect()->route('inventory.masters.type', $type)->with('success', 'Saved.');
    }

    public function destroy(string $type, Attribute $attribute)
    {
        $this->need('inventory.manage_products');
        abort_unless($attribute->type === $type, 404);
        if (in_array($type, Item::ATTRS, true) && Item::query()->where($type.'_id', $attribute->id)->exists()) {
            return back()->with('error', 'This '.strtolower(Attribute::TYPES[$type][0]).' is used by items — set it inactive instead.');
        }
        $attribute->delete();

        return back()->with('success', 'Deleted.');
    }

    private function data(Request $request, string $type, ?Attribute $current = null): array
    {
        abort_unless(isset(Attribute::TYPES[$type]), 404);
        $parentType = Attribute::TYPES[$type][2];

        return $request->validate([
            'name' => ['required', 'string', 'max:150',
                Rule::unique('inv_attributes', 'name')->where('type', $type)->where('parent_id', $request->input('parent_id'))->ignore($current?->id)],
            'code' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:1000'],
            'parent_id' => $parentType ? ['nullable', Rule::exists('inv_attributes', 'id')->where('type', $parentType)] : ['prohibited'],
        ]) + ['is_active' => $request->boolean('is_active', true)];
    }
}
