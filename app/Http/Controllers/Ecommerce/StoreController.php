<?php

declare(strict_types=1);

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Category;
use App\Models\Ecommerce\Order;
use App\Models\Ecommerce\Product;
use App\Models\Ecommerce\Review;
use App\Models\Ecommerce\ShippingMethod;
use App\Models\Tenant;
use App\Support\Ecommerce\OrderService;
use App\Support\Settings;
use Illuminate\Http\Request;

/** Public storefront per tenant: /store/{tenant}. No login. */
class StoreController extends Controller
{
    private function boot(string $tenantId): Tenant
    {
        $tenant = Tenant::query()->find($tenantId);
        abort_unless($tenant && $tenant->is_active, 404);
        // E-commerce module must be enabled for this tenant — 403 (consistent with the
        // admin e-commerce pages) rather than a bare 404.
        abort_unless($tenant->hasModule('ecommerce'), 403, 'The online store is not enabled for this organization.');
        // The URL's tenant is authoritative for the public store — drop any tenant
        // context the logged-in user's middleware may have set, then switch to it.
        tenancy()->end();
        tenancy()->initialize($tenant);
        abort_unless(Settings::get('store_enabled', '1') === '1', 403, 'This store is currently closed.');

        return $tenant;
    }

    private function cartKey(string $tenantId): string { return "store_cart_$tenantId"; }

    private function cartLines(string $tenantId): array
    {
        $cart = session($this->cartKey($tenantId), []);

        return collect($cart)->map(fn ($qty, $pid) => ['product_id' => (int) $pid, 'qty' => (float) $qty])->values()->all();
    }

    private function storeMeta(Tenant $t): array
    {
        return [
            'tenant' => $t,
            'storeName' => Settings::get('store_name', Settings::get('company_name', 'Store')),
            'tagline' => Settings::get('store_tagline', ''),
            'currency' => Settings::get('default_currency', 'PKR'),
            'cartCount' => collect(session($this->cartKey($t->id), []))->sum(),
        ];
    }

    public function index(string $tenant, Request $request)
    {
        $t = $this->boot($tenant);
        $products = Product::query()->with('item')->where('is_published', true)
            ->when($request->q, fn ($q, $s) => $q->where('name', 'like', "%$s%"))
            ->when($request->category, fn ($q, $v) => $q->whereHas('category', fn ($c) => $c->where('slug', $v)))
            ->orderByDesc('is_featured')->orderBy('sort')->paginate(12)->withQueryString();

        return view('ecommerce.store.index', $this->storeMeta($t) + [
            'products' => $products,
            'categories' => Category::where('is_active', true)->orderBy('sort')->get(),
        ]);
    }

    public function product(string $tenant, string $slug)
    {
        $t = $this->boot($tenant);
        $product = Product::query()->with('item')->where('slug', $slug)->where('is_published', true)->firstOrFail();

        return view('ecommerce.store.product', $this->storeMeta($t) + [
            'product' => $product,
            'inStock' => $product->stock() > 0 || $product->item?->is_service,
            'reviews' => Review::where('product_id', $product->id)->where('is_approved', true)->latest()->get(),
            'avg' => $product->avgRating(),
        ]);
    }

    public function addToCart(string $tenant, Request $request)
    {
        $this->boot($tenant);
        $data = $request->validate(['product_id' => ['required', 'integer'], 'qty' => ['nullable', 'integer', 'min:1']]);
        abort_unless(Product::where('id', $data['product_id'])->where('is_published', true)->exists(), 404);
        $key = $this->cartKey($tenant);
        $cart = session($key, []);
        $cart[$data['product_id']] = ($cart[$data['product_id']] ?? 0) + ($data['qty'] ?? 1);
        session([$key => $cart]);

        return back()->with('success', 'Added to cart.');
    }

    public function updateCart(string $tenant, Request $request)
    {
        $this->boot($tenant);
        $key = $this->cartKey($tenant);
        $cart = [];
        foreach ((array) $request->input('qty', []) as $pid => $qty) {
            if ((int) $qty > 0) {
                $cart[(int) $pid] = (int) $qty;
            }
        }
        session([$key => $cart]);

        return back()->with('success', 'Cart updated.');
    }

    public function removeFromCart(string $tenant, int $product)
    {
        $this->boot($tenant);
        $key = $this->cartKey($tenant);
        $cart = session($key, []);
        unset($cart[$product]);
        session([$key => $cart]);

        return back()->with('success', 'Item removed.');
    }

    public function cart(string $tenant)
    {
        $t = $this->boot($tenant);
        $priced = OrderService::price($this->cartLines($tenant));

        return view('ecommerce.store.cart', $this->storeMeta($t) + ['priced' => $priced]);
    }

    public function checkout(string $tenant)
    {
        $t = $this->boot($tenant);
        $lines = $this->cartLines($tenant);
        $priced = OrderService::price($lines);
        if (! $priced['rows']) {
            return redirect()->route('store.index', $tenant)->with('error', 'Your cart is empty.');
        }

        return view('ecommerce.store.checkout', $this->storeMeta($t) + [
            'priced' => $priced,
            'shippingMethods' => ShippingMethod::where('is_active', true)->orderBy('sort')->get(),
            'cod' => Settings::get('store_cod', '1') === '1',
            'bank' => Settings::get('store_bank_transfer', '0') === '1',
        ]);
    }

    public function placeOrder(string $tenant, Request $request)
    {
        $t = $this->boot($tenant);
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:150'],
            'customer_email' => ['nullable', 'email', 'max:190'],
            'customer_phone' => ['required', 'string', 'max:40'],
            'shipping_address' => ['required', 'string', 'max:500'],
            'shipping_method_id' => ['nullable', 'integer', 'exists:ecom_shipping_methods,id'],
            'payment_method' => ['nullable', 'string', 'max:40'],
            'coupon_code' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:500'],
            'website' => ['prohibited'], // honeypot
        ]);
        $order = OrderService::createOrder($this->cartLines($tenant), $data);
        session()->forget($this->cartKey($tenant));

        return redirect()->route('store.confirm', [$tenant, $order->order_no]);
    }

    public function confirm(string $tenant, string $orderNo)
    {
        $t = $this->boot($tenant);
        $order = Order::where('order_no', $orderNo)->with('items')->firstOrFail();

        return view('ecommerce.store.confirm', $this->storeMeta($t) + ['order' => $order]);
    }

    public function submitReview(string $tenant, Request $request, int $product)
    {
        $this->boot($tenant);
        abort_unless(Product::where('id', $product)->where('is_published', true)->exists(), 404);
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:150'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:150'],
            'body' => ['nullable', 'string', 'max:2000'],
        ]);
        Review::create($data + ['product_id' => $product, 'is_approved' => false]);

        return back()->with('success', 'Thanks! Your review will appear once approved.');
    }
}
