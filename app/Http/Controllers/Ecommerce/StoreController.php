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
use App\Support\Ecommerce\Money;
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

    /** Count of items + money total (subtotal + tax) for the current session cart. */
    private function cartTotals(string $tenantId): array
    {
        $count = (int) collect(session($this->cartKey($tenantId), []))->sum();
        $priced = OrderService::price($this->cartLines($tenantId));

        return ['count' => $count, 'total' => round($priced['subtotal'] + $priced['tax'], 2)];
    }

    private function freeOver(): float
    {
        return (float) Settings::get('store_free_shipping_over', 0);
    }

    /** Flat shipping preview for the cart page (best guess from the cheapest active method). */
    private function shippingPreview(float $subtotal): float
    {
        $sm = ShippingMethod::where('is_active', true)->orderBy('sort')->first();
        if (! $sm) {
            return 0.0;
        }

        return (float) $sm->costFor($subtotal);
    }

    private function storeMeta(Tenant $t): array
    {
        $cur = strtoupper((string) Settings::get('default_currency', 'PKR'));
        $totals = $this->cartTotals($t->id);

        return [
            'tenant' => $t,
            'storeName' => Settings::get('store_name', Settings::get('company_name', 'Store')),
            'tagline' => Settings::get('store_tagline', ''),
            'storePhone' => Settings::get('store_phone', Settings::get('company_phone', '')),
            'storeEmail' => Settings::get('store_email', Settings::get('company_email', '')),
            'storeAddress' => Settings::get('store_address', Settings::get('company_address', '')),
            'social' => [
                'facebook' => Settings::get('store_facebook', ''),
                'instagram' => Settings::get('store_instagram', ''),
                'twitter' => Settings::get('store_twitter', ''),
                'whatsapp' => Settings::get('store_whatsapp', ''),
            ],
            'currency' => $cur,
            'curSymbol' => Money::symbol($cur),
            'curDecimals' => Money::decimals($cur),
            'freeOver' => $this->freeOver(),
            'navCategories' => Category::where('is_active', true)->whereNull('parent_id')->orderBy('sort')->with(['children' => fn ($q) => $q->where('is_active', true)->orderBy('sort')])->get(),
            'cartCount' => $totals['count'],
            'cartTotal' => $totals['total'],
        ];
    }

    public function index(string $tenant, Request $request)
    {
        $t = $this->boot($tenant);

        $selectedCategory = $request->category
            ? Category::where('slug', $request->category)->first()
            : null;

        $q = Product::query()->with(['item', 'category'])
            ->withCount(['reviews as reviews_count' => fn ($r) => $r->where('is_approved', true)])
            ->withAvg(['reviews as reviews_avg' => fn ($r) => $r->where('is_approved', true)], 'rating')
            ->where('is_published', true)
            ->when($request->q, fn ($qq, $s) => $qq->where('name', 'like', "%$s%"))
            ->when($selectedCategory, function ($qq) use ($selectedCategory) {
                // selected category + its direct children
                $ids = collect([$selectedCategory->id])->merge($selectedCategory->children()->pluck('id'))->all();
                $qq->whereIn('category_id', $ids);
            })
            ->when($request->filled('min_price'), fn ($qq) => $qq->where('price', '>=', (float) $request->min_price))
            ->when($request->filled('max_price'), fn ($qq) => $qq->where('price', '<=', (float) $request->max_price))
            ->when($request->on_sale, fn ($qq) => $qq->whereNotNull('compare_at_price')->whereColumn('compare_at_price', '>', 'price'));

        $q = match ($request->sort) {
            'price_asc' => $q->orderBy('price'),
            'price_desc' => $q->orderByDesc('price'),
            'name_asc' => $q->orderBy('name'),
            'discount' => $q->orderByRaw('COALESCE(compare_at_price,0) - price DESC'),
            default => $q->orderByDesc('is_featured')->orderBy('sort')->latest(),
        };

        $products = $q->paginate(12)->withQueryString();

        $bounds = Product::where('is_published', true)
            ->selectRaw('MIN(price) as min_p, MAX(price) as max_p')->first();

        return view('ecommerce.store.index', $this->storeMeta($t) + [
            'products' => $products,
            'categories' => Category::where('is_active', true)->whereNull('parent_id')->orderBy('sort')
                ->with(['children' => fn ($c) => $c->where('is_active', true)->orderBy('sort')])->get(),
            'selectedCategory' => $selectedCategory,
            'priceBounds' => $bounds,
        ]);
    }

    public function product(string $tenant, string $slug)
    {
        $t = $this->boot($tenant);
        $product = Product::query()->with(['item', 'category'])->where('slug', $slug)->where('is_published', true)->firstOrFail();

        $related = Product::where('is_published', true)
            ->where('id', '!=', $product->id)
            ->when($product->category_id, fn ($q) => $q->where('category_id', $product->category_id))
            ->withCount(['reviews as reviews_count' => fn ($r) => $r->where('is_approved', true)])
            ->withAvg(['reviews as reviews_avg' => fn ($r) => $r->where('is_approved', true)], 'rating')
            ->limit(5)->get();

        return view('ecommerce.store.product', $this->storeMeta($t) + [
            'product' => $product,
            'inStock' => $product->stock() > 0 || $product->item?->is_service,
            'reviews' => Review::where('product_id', $product->id)->where('is_approved', true)->latest()->get(),
            'avg' => $product->avgRating(),
            'related' => $related,
        ]);
    }

    public function addToCart(string $tenant, Request $request)
    {
        $this->boot($tenant);
        $data = $request->validate(['product_id' => ['required', 'integer'], 'qty' => ['nullable', 'integer', 'min:1'], 'quantity' => ['nullable', 'integer', 'min:1']]);
        $product = Product::where('id', $data['product_id'])->where('is_published', true)->first();
        abort_unless((bool) $product, 404);
        $key = $this->cartKey($tenant);
        $cart = session($key, []);
        $add = (int) ($data['qty'] ?? $data['quantity'] ?? 1);
        $cart[$data['product_id']] = ($cart[$data['product_id']] ?? 0) + $add;
        session([$key => $cart]);

        $totals = $this->cartTotals($tenant);
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['ok' => true, 'name' => $product->name] + $totals);
        }

        return back()->with('success', 'Added to cart.');
    }

    public function updateCart(string $tenant, Request $request)
    {
        $this->boot($tenant);
        $key = $this->cartKey($tenant);

        // Single-line AJAX update (product_id + quantity) — used by the cart page.
        if ($request->filled('product_id')) {
            $pid = (int) $request->input('product_id');
            $qty = max(0, (int) $request->input('quantity', 0));
            $cart = session($key, []);
            if ($qty <= 0) {
                unset($cart[$pid]);
            } else {
                $cart[$pid] = $qty;
            }
            session([$key => $cart]);

            $totals = $this->cartTotals($tenant);
            $line = OrderService::price([['product_id' => $pid, 'qty' => $qty]]);
            $lineTotal = $line['rows'][0]['line_total'] ?? 0;

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'ok' => true, 'quantity' => $qty, 'line_total' => $lineTotal,
                    'removed' => $qty <= 0, 'empty' => empty($cart),
                ] + $totals);
            }

            return back()->with('success', 'Cart updated.');
        }

        // Legacy bulk update (qty[] array).
        $cart = [];
        foreach ((array) $request->input('qty', []) as $pid => $qty) {
            if ((int) $qty > 0) {
                $cart[(int) $pid] = (int) $qty;
            }
        }
        session([$key => $cart]);

        return back()->with('success', 'Cart updated.');
    }

    public function removeFromCart(string $tenant, int $product, Request $request)
    {
        $this->boot($tenant);
        $key = $this->cartKey($tenant);
        $cart = session($key, []);
        unset($cart[$product]);
        session([$key => $cart]);

        $totals = $this->cartTotals($tenant);
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['ok' => true, 'empty' => empty($cart)] + $totals);
        }

        return back()->with('success', 'Item removed.');
    }

    public function cart(string $tenant)
    {
        $t = $this->boot($tenant);
        $priced = OrderService::price($this->cartLines($tenant));
        $subtotal = $priced['subtotal'];

        return view('ecommerce.store.cart', $this->storeMeta($t) + [
            'priced' => $priced,
            'shipPreview' => $this->shippingPreview($subtotal),
        ]);
    }

    public function checkout(string $tenant)
    {
        $t = $this->boot($tenant);
        $lines = $this->cartLines($tenant);
        $priced = OrderService::price($lines);
        if (! $priced['rows']) {
            return redirect()->route('store.index', $tenant)->with('error', 'Your cart is empty.');
        }

        // Payment-method map in the storefront card shape: code => [label, desc, icon, tag].
        $methods = [];
        if (Settings::get('store_cod', '1') === '1') {
            $methods['cod'] = ['label' => 'Cash on Delivery', 'desc' => 'Pay in cash when your order arrives.', 'icon' => 'fa-money-bill-wave', 'tag' => 'Popular'];
        }
        if (Settings::get('store_bank_transfer', '0') === '1') {
            $methods['bank'] = ['label' => 'Bank Transfer', 'desc' => 'Transfer to our account and upload the receipt.', 'icon' => 'fa-building-columns', 'tag' => null];
        }
        // Always offer at least COD so checkout is never dead.
        if (! $methods) {
            $methods['cod'] = ['label' => 'Cash on Delivery', 'desc' => 'Pay in cash when your order arrives.', 'icon' => 'fa-money-bill-wave', 'tag' => null];
        }

        return view('ecommerce.store.checkout', $this->storeMeta($t) + [
            'priced' => $priced,
            'shippingMethods' => ShippingMethod::where('is_active', true)->orderBy('sort')->get(),
            'methods' => $methods,
            'bankDetails' => Settings::get('store_bank_details', ''),
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

        // Normalise the payment code into a human label stored on the order.
        $labels = ['cod' => 'Cash on Delivery', 'bank' => 'Bank Transfer'];
        $data['payment_method'] = $labels[$data['payment_method'] ?? 'cod'] ?? 'Cash on Delivery';

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
