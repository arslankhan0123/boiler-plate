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
            'categories' => Category::where('is_active', true)->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function edit(Product $product)
    {
        $this->need('ecommerce.manage_products');

        return view('ecommerce.catalog.form', [
            'product' => $product,
            'items' => $this->publishableItems($product->item_id),
            'categories' => Category::where('is_active', true)->orderBy('name')->pluck('name', 'id'),
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
    public function categories()
    {
        $this->need('ecommerce.manage_products');

        return view('ecommerce.catalog.categories', ['categories' => Category::withCount('products')->orderBy('sort')->orderBy('name')->get()]);
    }

    public function storeCategory(Request $request)
    {
        $this->need('ecommerce.manage_products');
        $data = $request->validate(['name' => ['required', 'string', 'max:100'], 'sort' => ['nullable', 'integer']]);
        Category::create(['name' => $data['name'], 'slug' => $this->uniqueSlug($data['name'], 'ecom_categories'), 'sort' => $data['sort'] ?? 0, 'is_active' => true]);

        return back()->with('success', 'Category added.');
    }

    public function destroyCategory(Category $category)
    {
        $this->need('ecommerce.manage_products');
        $category->delete();

        return back()->with('success', 'Category deleted.');
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
