<?php

declare(strict_types=1);

namespace App\Http\Controllers\Ecommerce;

use App\Models\Ecommerce\Product;
use App\Support\Settings;
use Illuminate\Http\Request;

/** SEO & marketing: per-product meta + store-level meta. */
class SeoController extends EcommerceController
{
    public function index()
    {
        $this->need('ecommerce.manage_seo');

        return view('ecommerce.seo.index', [
            'products' => Product::orderBy('name')->paginate(25),
            'storeTitle' => Settings::get('store_seo_title', ''),
            'storeDescription' => Settings::get('store_seo_description', ''),
        ]);
    }

    public function updateStore(Request $request)
    {
        $this->need('ecommerce.manage_seo');
        $data = $request->validate([
            'store_seo_title' => ['nullable', 'string', 'max:255'],
            'store_seo_description' => ['nullable', 'string', 'max:500'],
        ]);
        Settings::set($data);

        return back()->with('success', 'Store SEO saved.');
    }

    public function updateProduct(Request $request, Product $product)
    {
        $this->need('ecommerce.manage_seo');
        $product->update($request->validate([
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
        ]));

        return back()->with('success', 'Product SEO saved.');
    }
}
