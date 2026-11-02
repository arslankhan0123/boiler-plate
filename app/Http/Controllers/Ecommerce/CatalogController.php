<?php

declare(strict_types=1);

namespace App\Http\Controllers\Ecommerce;

use App\Models\Ecommerce\Category;
use App\Models\Ecommerce\Product;
use App\Models\Inventory\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** Store catalog: publish inventory items as online products, and manage categories. */
class CatalogController extends EcommerceController
{
    public function index(Request $request)
    {
        $this->need('ecommerce.view', 'ecommerce.manage_products');
        $products = Product::query()->with('item', 'category')
            ->when($request->q, fn ($q, $s) => $q->where('name', 'like', "%$s%"))
            ->when($request->category_id, fn ($q, $v) => $q->where('category_id', $v))
            ->when($request->status === 'published', fn ($q) => $q->where('is_published', true))
            ->when($request->status === 'draft', fn ($q) => $q->where('is_published', false))
            ->orderBy('sort')->latest('id')->paginate(20)->withQueryString();

        return view('ecommerce.catalog.index', [
            'products' => $products,
            'categories' => Category::orderBy('name')->pluck('name', 'id'),
            'publishedCount' => Product::where('is_published', true)->count(),
            'canEdit' => $this->can('ecommerce.manage_products'),
        ]);
    }

    public function create()
    {
        $this->need('ecommerce.manage_products');

        return view('ecommerce.catalog.form', [
            'product' => new Product(['is_published' => true]),
            'items' => $this->publishableItems(),
            'categories' => Category::where('is_active', true)->with('parent')->orderBy('name')->get()->mapWithKeys(fn ($c) => [$c->id => $c->path()]),
        ]);
    }

    public function edit(Product $product)
    {
        $this->need('ecommerce.manage_products');

        return view('ecommerce.catalog.form', [
            'product' => $product,
            'items' => $this->publishableItems($product->item_id),
            'categories' => Category::where('is_active', true)->with('parent')->orderBy('name')->get()->mapWithKeys(fn ($c) => [$c->id => $c->path()]),
        ]);
    }

    public function store(Request $request)
    {
        $this->need('ecommerce.manage_products');
        $data = $this->data($request);
        Product::create($data + ['created_by' => auth()->id()]);

        return redirect()->route('ecommerce.catalog.index')->with('success', 'Product published to the store.');
    }

    public function update(Request $request, Product $product)
    {
        $this->need('ecommerce.manage_products');
        $product->update($this->data($request, $product->id));

        return redirect()->route('ecommerce.catalog.index')->with('success', 'Product saved.');
    }

    public function togglePublish(Product $product)
    {
        $this->need('ecommerce.manage_products');
        $product->update(['is_published' => ! $product->is_published]);

        return back()->with('success', $product->is_published ? 'Product published.' : 'Product unpublished.');
    }

    public function destroy(Product $product)
    {
        $this->need('ecommerce.manage_products');
        $product->delete();

        return back()->with('success', 'Product removed from the store.');
    }

    // ---- Categories ----
    public function categories(?Category $editing = null)
    {
        $this->need('ecommerce.manage_products');
        // Parent options exclude the category being edited and its descendants (no loops).
        $exclude = $editing ? $editing->load('children')->descendantIds() : [];
        $parents = Category::query()->whereNotIn('id', $exclude)->orderBy('name')
            ->get()->mapWithKeys(fn ($c) => [$c->id => $c->path()])->all();

        // Order as a depth-first tree so each child sits right under its parent,
        // with a depth for progressive indentation.
        $all = Category::withCount('products')->with('parent')->orderBy('sort')->orderBy('name')->get();
        $byParent = $all->groupBy(fn ($c) => $c->parent_id ?? 0);
        $rows = collect();
        $walk = function ($parentId, $depth) use (&$walk, $byParent, &$rows) {
            foreach ($byParent->get($parentId, collect()) as $cat) {
                $rows->push(['cat' => $cat, 'depth' => $depth]);
                $walk($cat->id, $depth + 1);
            }
        };
        $walk(0, 0);

        return view('ecommerce.catalog.categories', [
            'rows' => $rows,
            'parents' => $parents,
            'editing' => $editing,
        ]);
    }

    public function editCategory(Category $category)
    {
        return $this->categories($category);
    }

    public function storeCategory(Request $request)
    {
        $this->need('ecommerce.manage_products');
        $data = $this->categoryData($request);
        Category::create($data + ['slug' => $this->uniqueSlug($data['name'], 'ecom_categories'), 'is_active' => true]);

        return redirect()->route('ecommerce.catalog.categories')->with('success', 'Category added.');
    }

    public function updateCategory(Request $request, Category $category)
    {
        $this->need('ecommerce.manage_products');
        $data = $this->categoryData($request, $category);
        $category->update($data);

        return redirect()->route('ecommerce.catalog.categories')->with('success', 'Category saved.');
    }

    public function destroyCategory(Category $category)
    {
        $this->need('ecommerce.manage_products');
        // Detach children so they become top-level, not orphaned.
        Category::where('parent_id', $category->id)->update(['parent_id' => null]);
        $category->delete();

        return back()->with('success', 'Category deleted.');
    }

    /** Drag & drop: move a category under a new parent (or to the top level). */
    public function reparentCategory(Request $request, Category $category)
    {
        $this->need('ecommerce.manage_products');
        $data = $request->validate(['parent_id' => ['nullable', 'integer', 'exists:ecom_categories,id']]);
        $parentId = $data['parent_id'] ?? null;

        if ($parentId !== null && in_array((int) $parentId, $category->load('children')->descendantIds(), true)) {
            return response()->json(['ok' => false, 'message' => 'Cannot place a category under itself or one of its subcategories.'], 422);
        }
        $category->update(['parent_id' => $parentId]);

        return response()->json(['ok' => true]);
    }

    private function categoryData(Request $request, ?Category $category = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'sort' => ['nullable', 'integer'],
            'parent_id' => ['nullable', 'integer', 'exists:ecom_categories,id'],
        ]);
        // A category cannot be its own parent or a descendant's parent.
        if ($category && ! empty($data['parent_id']) && in_array((int) $data['parent_id'], $category->load('children')->descendantIds(), true)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['parent_id' => 'A category cannot be placed under itself or one of its subcategories.']);
        }

        return ['name' => $data['name'], 'sort' => $data['sort'] ?? 0, 'parent_id' => $data['parent_id'] ?? null];
    }

    /** Inventory items not yet published (plus the current one when editing). */
    private function publishableItems(?int $keep = null): array
    {
        $taken = Product::query()->pluck('item_id')->reject(fn ($id) => $id === $keep)->all();

        return Item::query()->where('is_active', true)
            ->whereNotIn('id', $taken)
            ->orderBy('name')->get()->mapWithKeys(fn ($i) => [$i->id => $i->name.' ('.number_format($i->sales_price, 2).')'])->all();
    }

    private function data(Request $request, ?int $id = null): array
    {
        $data = $request->validate([
            'item_id' => ['required', 'integer', 'exists:inv_items,id'],
            'category_id' => ['nullable', 'integer', 'exists:ecom_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'compare_at_price' => ['nullable', 'numeric', 'min:0'],
            'image_url' => ['nullable', 'string', 'max:500'],
            'sort' => ['nullable', 'integer'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
        ]);
        $data['is_published'] = $request->boolean('is_published');
        $data['is_featured'] = $request->boolean('is_featured');
        $data['slug'] = $this->uniqueSlug($data['name'], 'ecom_products', $id);
        $data['price'] = (float) $data['price'];

        return $data;
    }

    private function uniqueSlug(string $name, string $table, ?int $id = null): string
    {
        $base = Str::slug($name) ?: 'item';
        $slug = $base;
        $i = 1;
        while (\Illuminate\Support\Facades\DB::connection('tenant')->table($table)->where('slug', $slug)->when($id, fn ($q) => $q->where('id', '!=', $id))->exists()) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }
}
