<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Ecommerce\Models\Category;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\ProductImage;
use Modules\Ecommerce\Models\Service;

class CatalogController extends Controller
{
    public function index(Request $r): JsonResponse
    {
        return ApiResponse::successResponse('Categories retrieved.', Category::query()->with('parent')->when($r->boolean('with_inactive') === false, fn ($q) => $q->where('is_active', true))->orderBy('sort_order')->paginate($r->integer('per_page', 20)));
    }

    public function show(Category $category): JsonResponse
    {
        return ApiResponse::successResponse('Category retrieved.', $category->load('parent'));
    }

    public function store(Request $r): JsonResponse
    {
        $data = $r->validate(['parent_id' => ['nullable', 'integer', 'exists:ecommerce_categories,id'], 'name' => ['required', 'string', 'max:255'], 'slug' => ['nullable', 'string', 'max:255', 'unique:ecommerce_categories,slug'], 'description' => ['nullable', 'string'], 'image' => ['nullable', 'image', 'max:5120'], 'is_active' => ['sometimes', 'boolean'], 'sort_order' => ['sometimes', 'integer', 'min:0']]);
        if ($r->hasFile('image')) {
            $data['image'] = $r->file('image')->store('ecommerce/categories', 'public');
        }
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']).'-'.Str::lower(Str::random(6));
        $data['created_by'] = Auth::guard('api')->id();

        return ApiResponse::successResponse('Category created.', Category::create($data));
    }

    public function update(Request $r, Category $category): JsonResponse
    {
        $data = $r->validate(['parent_id' => ['nullable', 'integer', 'exists:ecommerce_categories,id', 'different:id'], 'name' => ['sometimes', 'string', 'max:255'], 'slug' => ['sometimes', 'string', 'max:255', 'unique:ecommerce_categories,slug,'.$category->id], 'description' => ['nullable', 'string'], 'image' => ['nullable', 'image', 'max:5120'], 'is_active' => ['sometimes', 'boolean'], 'sort_order' => ['sometimes', 'integer', 'min:0']]);
        if ($r->hasFile('image')) {
            $this->deleteFile($category->image);
            $data['image'] = $r->file('image')->store('ecommerce/categories', 'public');
        }
        $category->update($data);

        return ApiResponse::successResponse('Category updated.', $category->fresh());
    }

    public function destroy(Category $category): JsonResponse
    {
        $category->delete();

        return ApiResponse::successResponse('Category deleted.');
    }

    public function services(Request $r): JsonResponse
    {
        return ApiResponse::successResponse('Services retrieved.', Service::query()->with('category')->when($r->filled('category_id'), fn ($q) => $q->where('category_id', $r->integer('category_id')))->when($r->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$r->string('search').'%'))->paginate($r->integer('per_page', 20)));
    }

    public function storeService(Request $r): JsonResponse
    {
        $data = $this->serviceData($r);
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']).'-'.Str::lower(Str::random(6));
        $data['created_by'] = Auth::guard('api')->id();

        return ApiResponse::successResponse('Service created.', Service::create($data)->load('category'));
    }

    public function updateService(Request $r, Service $service): JsonResponse
    {
        $service->update($this->serviceData($r, $service));

        return ApiResponse::successResponse('Service updated.', $service->fresh('category'));
    }

    public function destroyService(Service $service): JsonResponse
    {
        $service->delete();

        return ApiResponse::successResponse('Service deleted.');
    }

    public function products(Request $r): JsonResponse
    {
        return ApiResponse::successResponse('Products retrieved.', Product::query()->with(['category', 'images'])->when($r->filled('category_id'), fn ($q) => $q->where('category_id', $r->integer('category_id')))->when($r->boolean('featured'), fn ($q) => $q->where('is_featured', true))->when($r->filled('search'), fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', '%'.$r->string('search').'%')->orWhere('sku', 'like', '%'.$r->string('search').'%')))->paginate($r->integer('per_page', 20)));
    }

    public function showProduct(Product $product): JsonResponse
    {
        return ApiResponse::successResponse('Product retrieved.', $product->load(['category', 'images']));
    }

    public function storeProduct(Request $r): JsonResponse
    {
        $data = $this->productData($r);
        if ($r->hasFile('feature_image')) {
            $data['feature_image'] = $r->file('feature_image')->store('ecommerce/products', 'public');
        }
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']).'-'.Str::lower(Str::random(6));
        $data['created_by'] = Auth::guard('api')->id();
        $product = Product::create($data);
        $this->storeGallery($r, $product);

        return ApiResponse::successResponse('Product created.', $product->load(['category', 'images']));
    }

    public function updateProduct(Request $r, Product $product): JsonResponse
    {
        $data = $this->productData($r, $product);
        if ($r->hasFile('feature_image')) {
            $this->deleteFile($product->feature_image);
            $data['feature_image'] = $r->file('feature_image')->store('ecommerce/products', 'public');
        }
        $product->update($data);
        $this->storeGallery($r, $product);

        return ApiResponse::successResponse('Product updated.', $product->fresh(['category', 'images']));
    }

    public function destroyProduct(Product $product): JsonResponse
    {
        $product->delete();

        return ApiResponse::successResponse('Product deleted.');
    }

    public function addGalleryImage(Request $r, Product $product): JsonResponse
    {
        $r->validate(['image' => ['required', 'image', 'max:5120'], 'alt_text' => ['nullable', 'string', 'max:255'], 'sort_order' => ['nullable', 'integer', 'min:0']]);
        $image = $product->images()->create(['path' => $r->file('image')->store('ecommerce/products/gallery', 'public'), 'alt_text' => $r->input('alt_text'), 'sort_order' => $r->integer('sort_order')]);

        return ApiResponse::successResponse('Gallery image added.', $image);
    }

    public function removeGalleryImage(Product $product, ProductImage $image): JsonResponse
    {
        abort_unless($image->product_id === $product->id, 404);
        $this->deleteFile($image->path);
        $image->delete();

        return ApiResponse::successResponse('Gallery image removed.');
    }

    private function serviceData(Request $r, ?Service $s = null): array
    {
        return $r->validate(['category_id' => ['required', 'integer', 'exists:ecommerce_categories,id'], 'name' => [$s ? 'sometimes' : 'required', 'string', 'max:255'], 'slug' => ['nullable', 'string', 'max:255', 'unique:ecommerce_services,slug'.($s ? ','.$s->id : '')], 'sku' => ['nullable', 'string', 'max:100'], 'description' => ['nullable', 'string'], 'duration_minutes' => ['nullable', 'integer', 'min:1'], 'cost_price' => ['sometimes', 'numeric', 'min:0'], 'sale_price' => [$s ? 'sometimes' : 'required', 'numeric', 'min:0'], 'tax_rate' => ['sometimes', 'numeric', 'min:0', 'max:100'], 'is_active' => ['sometimes', 'boolean']]);
    }

    private function productData(Request $r, ?Product $p = null): array
    {
        return $r->validate(['category_id' => ['required', 'integer', 'exists:ecommerce_categories,id'], 'name' => [$p ? 'sometimes' : 'required', 'string', 'max:255'], 'slug' => ['nullable', 'string', 'max:255', 'unique:ecommerce_products,slug'.($p ? ','.$p->id : '')], 'sku' => ['nullable', 'string', 'max:100'], 'barcode' => ['nullable', 'string', 'max:100'], 'description' => ['nullable', 'string'], 'feature_image' => ['nullable', 'image', 'max:5120'], 'gallery_images' => ['nullable', 'array', 'max:10'], 'gallery_images.*' => ['image', 'max:5120'], 'cost_price' => ['sometimes', 'numeric', 'min:0'], 'sale_price' => [$p ? 'sometimes' : 'required', 'numeric', 'min:0'], 'compare_at_price' => ['nullable', 'numeric', 'min:0'], 'tax_rate' => ['sometimes', 'numeric', 'min:0', 'max:100'], 'stock_quantity' => ['sometimes', 'integer'], 'low_stock_threshold' => ['sometimes', 'integer', 'min:0'], 'track_inventory' => ['sometimes', 'boolean'], 'is_active' => ['sometimes', 'boolean'], 'is_featured' => ['sometimes', 'boolean'], 'weight' => ['nullable', 'numeric', 'min:0'], 'dimensions' => ['nullable', 'array']]);
    }

    private function storeGallery(Request $r, Product $p): void
    {
        foreach ($r->file('gallery_images', []) as $i => $file) {
            $p->images()->create(['path' => $file->store('ecommerce/products/gallery', 'public'), 'sort_order' => $p->images()->count() + $i]);
        }
    }

    private function deleteFile(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
