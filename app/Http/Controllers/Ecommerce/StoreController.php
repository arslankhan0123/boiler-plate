<?php

declare(strict_types=1);

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Category;
use App\Models\Ecommerce\Coupon;
use App\Models\Ecommerce\Order;
use App\Models\Ecommerce\Product;
use App\Models\Ecommerce\Review;
use App\Models\Ecommerce\ShippingMethod;
use App\Models\Tenant;
use App\Support\Ecommerce\Money;
use App\Support\Ecommerce\OrderService;
use App\Support\Ecommerce\PayPal;
use App\Support\Ecommerce\Payments;
use App\Support\Settings;
use Illuminate\Http\Request;

/**
 * Public storefront. Served two ways with identical behaviour:
 *   • platform:  /store/{tenant}/...        (route names store.*)
 *   • custom domain: brand.com/...          (route names shop.*; tenant from the host)
 * All in-page links go through sroute()/surl so they stay correct in both modes.
 */
class StoreController extends Controller
{
    private ?string $tid = null;

    private function boot(?string $tenant): Tenant
    {
        // Platform passes the {tenant} id; on a custom domain the middleware has
        // already initialized tenancy, so fall back to the active tenant.
        $tenantId = $tenant ?: (tenant() ? tenant()->getTenantKey() : null);
        abort_unless($tenantId, 404);

        $t = Tenant::query()->find($tenantId);
        abort_unless($t && $t->is_active, 404);
        abort_unless($t->hasModule('ecommerce'), 403, 'The online store is not enabled for this organization.');
        tenancy()->end();
        tenancy()->initialize($t);
        abort_unless(Settings::get('store_enabled', '1') === '1', 403, 'This store is currently closed.');

        $this->tid = $t->getTenantKey();

        return $t;
    }

    private function onDomain(): bool
    {
        return (bool) request()->attributes->get('isCustomStore');
    }

    /** Build a store URL correct for the current mode (platform vs custom domain). */
    private function sroute(string $name, array $params = []): string
    {
        unset($params['tenant']);
        if ($this->onDomain()) {
            $short = substr($name, strlen('store.'));
            if ($short === 'index') {
                return url('/').($params ? '?'.http_build_query($params) : '');
            }

            return route('shop.'.$short, $params);
        }

        return route($name, ['tenant' => $this->tid] + $params);
    }

    private function cartKey(): string { return "store_cart_{$this->tid}"; }

    private function couponKey(): string { return "store_coupon_{$this->tid}"; }

    private function intentKey(): string { return "store_stripe_intent_{$this->tid}"; }

    private function lastOrderKey(): string { return "store_last_order_{$this->tid}"; }

    private function cartLines(): array
    {
        $cart = session($this->cartKey(), []);

        return collect($cart)->map(fn ($qty, $pid) => ['product_id' => (int) $pid, 'qty' => (float) $qty])->values()->all();
    }

    private function cartTotals(): array
    {
        $count = (int) collect(session($this->cartKey(), []))->sum();
        $priced = OrderService::price($this->cartLines());

        return ['count' => $count, 'total' => round($priced['subtotal'] + $priced['tax'], 2)];
    }

    private function freeOver(): float
    {
        return (float) Settings::get('store_free_shipping_over', 0);
    }

    private function shippingPreview(float $subtotal): float
    {
        $sm = ShippingMethod::where('is_active', true)->orderBy('sort')->first();

        return $sm ? (float) $sm->costFor($subtotal) : 0.0;
    }

    private function sessionCoupon(float $subtotal): ?Coupon
    {
        $code = session($this->couponKey());
        if (! $code) {
            return null;
        }
        $c = Coupon::where('code', $code)->first();

        return $c && $c->isValid($subtotal) ? $c : null;
    }

    private function computeTotals(int|string|null $shippingMethodId = null): array
    {
        $shippingMethodId = ($shippingMethodId === null || $shippingMethodId === '') ? null : (int) $shippingMethodId;
        $priced = OrderService::price($this->cartLines());
        $subtotal = $priced['subtotal'];
        $tax = $priced['tax'];

        $sm = $shippingMethodId
            ? ShippingMethod::where('is_active', true)->find($shippingMethodId)
            : ShippingMethod::where('is_active', true)->orderBy('sort')->first();
        $shipping = $sm ? (float) $sm->costFor($subtotal) : 0.0;

        $coupon = $this->sessionCoupon($subtotal);
        $discount = $coupon ? $coupon->discountFor($subtotal) : 0.0;

        $grand = round(max(0, $subtotal + $tax + $shipping - $discount), 2);

        return [
            'rows' => $priced['rows'], 'subtotal' => $subtotal, 'tax' => $tax,
            'shipping' => $shipping, 'discount' => $discount, 'grand' => $grand,
            'coupon' => $coupon?->code,
        ];
    }

    private function storeMeta(Tenant $t): array
    {
        $cur = strtoupper((string) Settings::get('default_currency', 'PKR'));
        $totals = $this->cartTotals();

        return [
            'tenant' => $t,
            'surl' => fn (string $name, $params = []) => $this->sroute($name, is_array($params) ? $params : []),
            'onDomain' => $this->onDomain(),
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
            'themeCss' => \App\Support\Ecommerce\StoreTheme::css(),
            'themeBodyClass' => \App\Support\Ecommerce\StoreTheme::bodyClass(),
        ];
    }

    public function index(?string $tenant = null, ?Request $request = null)
    {
        $t = $this->boot($tenant);
        $request ??= request();

        $selectedCategory = $request->category ? Category::where('slug', $request->category)->first() : null;

        $q = Product::query()->with(['item', 'category'])
            ->withCount(['reviews as reviews_count' => fn ($r) => $r->where('is_approved', true)])
            ->withAvg(['reviews as reviews_avg' => fn ($r) => $r->where('is_approved', true)], 'rating')
            ->where('is_published', true)
            ->when($request->q, fn ($qq, $s) => $qq->where('name', 'like', "%$s%"))
            ->when($selectedCategory, function ($qq) use ($selectedCategory) {
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
        $bounds = Product::where('is_published', true)->selectRaw('MIN(price) as min_p, MAX(price) as max_p')->first();

        return view('ecommerce.store.index', $this->storeMeta($t) + [
            'products' => $products,
            'categories' => Category::where('is_active', true)->whereNull('parent_id')->orderBy('sort')
                ->with(['children' => fn ($c) => $c->where('is_active', true)->orderBy('sort')])->get(),
            'selectedCategory' => $selectedCategory,
            'priceBounds' => $bounds,
        ]);
    }

    public function product(?string $tenant = null, ?string $slug = null)
    {
        $t = $this->boot($tenant);
        $product = Product::query()->with(['item', 'category'])->where('slug', $slug)->where('is_published', true)->firstOrFail();

        $related = Product::where('is_published', true)->where('id', '!=', $product->id)
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

    public function addToCart(?string $tenant, Request $request)
    {
        $this->boot($tenant);
        $data = $request->validate(['product_id' => ['required', 'integer'], 'qty' => ['nullable', 'integer', 'min:1'], 'quantity' => ['nullable', 'integer', 'min:1']]);
        $product = Product::where('id', $data['product_id'])->where('is_published', true)->first();
        abort_unless((bool) $product, 404);
        $key = $this->cartKey();
        $cart = session($key, []);
        $add = (int) ($data['qty'] ?? $data['quantity'] ?? 1);
        $cart[$data['product_id']] = ($cart[$data['product_id']] ?? 0) + $add;
        session([$key => $cart]);

        $totals = $this->cartTotals();
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['ok' => true, 'name' => $product->name] + $totals);
        }

        return back()->with('success', 'Added to cart.');
    }

    public function updateCart(?string $tenant, Request $request)
    {
        $this->boot($tenant);
        $key = $this->cartKey();

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

            $totals = $this->cartTotals();
            $line = OrderService::price([['product_id' => $pid, 'qty' => $qty]]);
            $lineTotal = $line['rows'][0]['line_total'] ?? 0;

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['ok' => true, 'quantity' => $qty, 'line_total' => $lineTotal, 'removed' => $qty <= 0, 'empty' => empty($cart)] + $totals);
            }

            return back()->with('success', 'Cart updated.');
        }

        $cart = [];
        foreach ((array) $request->input('qty', []) as $pid => $qty) {
            if ((int) $qty > 0) {
                $cart[(int) $pid] = (int) $qty;
            }
        }
        session([$key => $cart]);

        return back()->with('success', 'Cart updated.');
    }

    public function removeFromCart(?string $tenant, int $product, Request $request)
    {
        $this->boot($tenant);
        $key = $this->cartKey();
        $cart = session($key, []);
        unset($cart[$product]);
        session([$key => $cart]);

        $totals = $this->cartTotals();
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['ok' => true, 'empty' => empty($cart)] + $totals);
        }

        return back()->with('success', 'Item removed.');
    }

    public function cart(?string $tenant = null)
    {
        $t = $this->boot($tenant);
        $priced = OrderService::price($this->cartLines());

        return view('ecommerce.store.cart', $this->storeMeta($t) + [
            'priced' => $priced,
            'shipPreview' => $this->shippingPreview($priced['subtotal']),
        ]);
    }

    public function applyCoupon(?string $tenant, Request $request)
    {
        $this->boot($tenant);
        $code = trim((string) $request->input('coupon_code', ''));
        $priced = OrderService::price($this->cartLines());
        $subtotal = $priced['subtotal'];

        if ($code === '') {
            session()->forget($this->couponKey());

            return response()->json(['ok' => true, 'removed' => true] + $this->totalsPayload());
        }

        $coupon = Coupon::where('code', $code)->first();
        if (! $coupon || ! $coupon->isValid($subtotal)) {
            $msg = $coupon && $subtotal < $coupon->min_order
                ? 'This coupon needs a minimum order of '.Money::fmt($coupon->min_order).'.'
                : 'That coupon code is not valid.';

            return response()->json(['ok' => false, 'message' => $msg], 422);
        }

        session([$this->couponKey() => $coupon->code]);

        return response()->json(['ok' => true, 'code' => $coupon->code] + $this->totalsPayload());
    }

    private function totalsPayload(int|string|null $shippingMethodId = null): array
    {
        $t = $this->computeTotals($shippingMethodId);

        return [
            'subtotal' => $t['subtotal'], 'tax' => $t['tax'], 'shipping' => $t['shipping'],
            'discount' => $t['discount'], 'grand' => $t['grand'], 'coupon' => $t['coupon'],
            'fmt' => [
                'subtotal' => Money::fmt($t['subtotal']), 'tax' => Money::fmt($t['tax']),
                'shipping' => $t['shipping'] > 0 ? Money::fmt($t['shipping']) : 'Free',
                'discount' => Money::fmt($t['discount']), 'grand' => Money::fmt($t['grand']),
            ],
        ];
    }

    public function checkout(?string $tenant = null)
    {
        $t = $this->boot($tenant);
        $totals = $this->computeTotals();
        if (! $totals['rows']) {
            return redirect()->to($this->sroute('store.index'))->with('error', 'Your cart is empty.');
        }

        return view('ecommerce.store.checkout', $this->storeMeta($t) + [
            'totals' => $totals,
            'shippingMethods' => ShippingMethod::where('is_active', true)->orderBy('sort')->get(),
            'methods' => Payments::enabledMethods(),
            'bankDetails' => Settings::get('store_bank_details', ''),
            'stripeKey' => Payments::stripeReady() ? Payments::stripePublishableKey() : null,
            'stripeTest' => Payments::stripeReady() && Payments::stripeTestMode(),
            'paypalClient' => PayPal::enabled() ? PayPal::clientId() : null,
            'paypalCurrency' => PayPal::enabled() ? PayPal::currency() : null,
        ]);
    }

    public function stripeIntent(?string $tenant, Request $request)
    {
        $this->boot($tenant);
        if (! Payments::stripeReady()) {
            return response()->json(['message' => 'Card payments are not available.'], 422);
        }
        $totals = $this->computeTotals();
        if (! $totals['rows']) {
            return response()->json(['message' => 'Your cart is empty.'], 422);
        }
        $currency = strtoupper((string) Settings::get('default_currency', 'USD'));
        $amount = Payments::minorUnits($totals['grand'], $currency);
        if ($amount < 1) {
            return response()->json(['message' => 'Order total is below the card payment minimum.'], 422);
        }

        try {
            $existing = session($this->intentKey());
            if ($existing && ($existing['currency'] ?? '') === $currency) {
                $pi = Payments::updateIntent($existing['id'], ['amount' => $amount]);
            } else {
                $pi = Payments::createIntent($amount, $currency, ['session' => substr(session()->getId(), 0, 40)]);
            }
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        session([$this->intentKey() => ['id' => $pi['id'], 'currency' => $currency, 'amount' => $amount]]);

        return response()->json(['clientSecret' => $pi['client_secret'], 'amount' => $amount, 'currency' => $currency]);
    }

    public function placeOrder(?string $tenant, Request $request)
    {
        $this->boot($tenant);
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:150'],
            'customer_email' => ['nullable', 'email', 'max:190'],
            'customer_phone' => ['required', 'string', 'max:40'],
            'shipping_address' => ['required', 'string', 'max:500'],
            'shipping_method_id' => ['nullable', 'integer', 'exists:ecom_shipping_methods,id'],
            'payment_method' => ['required', 'string', 'in:'.implode(',', array_keys(Payments::enabledMethods()))],
            'notes' => ['nullable', 'string', 'max:500'],
            'website' => ['prohibited'],
        ]);

        $wantsJson = $request->wantsJson() || $request->ajax();
        $method = $data['payment_method'];
        $isCard = $method === Payments::STRIPE;
        $isPayPal = $method === Payments::PAYPAL;

        $totals = $this->computeTotals($data['shipping_method_id'] ?? null);
        if (! $totals['rows']) {
            return $wantsJson
                ? response()->json(['message' => 'Your cart is empty.'], 422)
                : redirect()->to($this->sroute('store.index'))->with('error', 'Your cart is empty.');
        }

        $intent = session($this->intentKey());
        if ($isCard && (! Payments::stripeReady() || empty($intent['id']))) {
            $msg = 'Card payment could not be started. Please reload the page and try again.';

            return $wantsJson ? response()->json(['message' => $msg], 422) : back()->withInput()->with('error', $msg);
        }

        $checkout = [
            'customer_name' => $data['customer_name'],
            'customer_email' => $data['customer_email'] ?? null,
            'customer_phone' => $data['customer_phone'],
            'shipping_address' => $data['shipping_address'],
            'shipping_method_id' => $data['shipping_method_id'] ?? null,
            'coupon_code' => $totals['coupon'],
            'notes' => $data['notes'] ?? null,
            'payment_method' => Payments::label($method),
        ];

        try {
            $order = OrderService::createOrder($this->cartLines(), $checkout);
        } catch (\Throwable $e) {
            return $wantsJson ? response()->json(['message' => $e->getMessage()], 422) : back()->withInput()->with('error', $e->getMessage());
        }

        if ($isCard) {
            $order->forceFill(['payment_intent_id' => $intent['id']])->save();
            session([$this->lastOrderKey() => $order->id]);
            $currency = strtoupper((string) Settings::get('default_currency', 'USD'));
            $amount = Payments::minorUnits((float) $order->grand_total, $currency);
            try {
                Payments::updateIntent($intent['id'], [
                    'amount' => $amount,
                    'receipt_email' => $order->customer_email ?: '',
                    'metadata[order_no]' => $order->order_no,
                    'description' => Settings::get('store_name', 'Store').' order '.$order->order_no,
                ]);
            } catch (\RuntimeException $e) {
                Payments::cancelAndRelease($order);

                return $wantsJson ? response()->json(['message' => $e->getMessage()], 422) : back()->with('error', $e->getMessage());
            }
            $return = $this->sroute('store.stripe.return', ['orderNo' => $order->order_no]);

            return $wantsJson
                ? response()->json(['ok' => true, 'confirm' => true, 'return_url' => $return, 'order' => $order->order_no])
                : redirect()->to($return);
        }

        if ($isPayPal) {
            session([$this->lastOrderKey() => $order->id]);
            try {
                $ppId = PayPal::createOrder(
                    $order,
                    $this->sroute('store.paypal.return', ['orderNo' => $order->order_no]),
                    $this->sroute('store.paypal.cancel', ['orderNo' => $order->order_no]),
                );
                if ($ppId === '') {
                    throw new \RuntimeException('PayPal did not return an order id.');
                }
            } catch (\RuntimeException $e) {
                Payments::cancelAndRelease($order);

                return $wantsJson ? response()->json(['message' => $e->getMessage()], 422) : back()->with('error', $e->getMessage());
            }
            $order->forceFill(['payment_intent_id' => $ppId])->save();

            return $wantsJson
                ? response()->json(['ok' => true, 'paypal' => true, 'paypal_order_id' => $ppId, 'order' => $order->order_no])
                : redirect()->to($this->sroute('store.checkout'))->with('error', 'Please enable JavaScript to pay with PayPal.');
        }

        $this->clearCart();

        return $wantsJson
            ? response()->json(['ok' => true, 'redirect' => $this->sroute('store.confirm', ['orderNo' => $order->order_no])])
            : redirect()->to($this->sroute('store.confirm', ['orderNo' => $order->order_no]));
    }

    public function stripeReturn(?string $tenant, string $orderNo)
    {
        $this->boot($tenant);
        $order = Order::where('order_no', $orderNo)->firstOrFail();
        abort_unless((int) session($this->lastOrderKey()) === (int) $order->id, 403);

        if ($order->payment_status === 'paid') {
            $this->clearCart();

            return redirect()->to($this->sroute('store.confirm', ['orderNo' => $order->order_no]));
        }
        if (! $order->payment_intent_id) {
            return redirect()->to($this->sroute('store.checkout'))->with('error', 'No card payment was started for this order.');
        }

        try {
            $pi = Payments::retrieveIntent($order->payment_intent_id);
        } catch (\RuntimeException $e) {
            return redirect()->to($this->sroute('store.checkout'))->with('error', 'We could not verify your payment yet. If your card was charged, we will confirm the order shortly.');
        }

        $status = $pi['status'] ?? 'unknown';
        if ($status === 'succeeded') {
            Payments::markPaid($order, $pi['id']);
            $this->clearCart();

            return redirect()->to($this->sroute('store.confirm', ['orderNo' => $order->order_no]));
        }
        if ($status === 'processing') {
            $this->clearCart();

            return redirect()->to($this->sroute('store.confirm', ['orderNo' => $order->order_no]))->with('info', 'Your payment is processing. We will confirm shortly.');
        }

        Payments::cancelAndRelease($order);
        session()->forget($this->intentKey());
        $reason = $pi['last_payment_error']['message'] ?? 'Your card was not charged.';

        return redirect()->to($this->sroute('store.checkout'))->with('error', 'Payment was not completed — '.$reason.' Please try again or choose another method.');
    }

    public function paypalCapture(?string $tenant, Request $request)
    {
        $this->boot($tenant);
        $request->validate(['order' => 'required|string', 'paypal_order_id' => 'required|string']);
        $order = Order::where('order_no', $request->order)->firstOrFail();
        if ((int) session($this->lastOrderKey()) !== (int) $order->id || $order->payment_intent_id !== $request->paypal_order_id) {
            return response()->json(['message' => 'This payment does not belong to your session.'], 403);
        }

        return $this->finishPayPal($order, true);
    }

    public function paypalReturn(?string $tenant, string $orderNo)
    {
        $this->boot($tenant);
        $order = Order::where('order_no', $orderNo)->firstOrFail();
        abort_unless((int) session($this->lastOrderKey()) === (int) $order->id, 403);

        return $this->finishPayPal($order, false);
    }

    public function paypalCancel(?string $tenant, string $orderNo, Request $request)
    {
        $this->boot($tenant);
        $order = Order::where('order_no', $orderNo)->firstOrFail();
        if ((int) session($this->lastOrderKey()) === (int) $order->id && $order->payment_status !== 'paid') {
            Payments::cancelAndRelease($order);
        }

        return $request->wantsJson()
            ? response()->json(['ok' => true])
            : redirect()->to($this->sroute('store.checkout'))->with('info', 'PayPal payment was cancelled — your cart is still here if you want to try again.');
    }

    private function finishPayPal(Order $order, bool $json)
    {
        $done = fn () => $json
            ? response()->json(['ok' => true, 'redirect' => $this->sroute('store.confirm', ['orderNo' => $order->order_no])])
            : redirect()->to($this->sroute('store.confirm', ['orderNo' => $order->order_no]));

        if ($order->payment_status === 'paid') {
            $this->clearCart();

            return $done();
        }
        if (! $order->payment_intent_id) {
            $msg = 'No PayPal payment was started for this order.';

            return $json ? response()->json(['message' => $msg], 422) : redirect()->to($this->sroute('store.checkout'))->with('error', $msg);
        }

        try {
            $po = PayPal::getOrder($order->payment_intent_id);
            if (($po['status'] ?? '') === 'APPROVED') {
                $po = PayPal::captureOrder($order->payment_intent_id);
            }
        } catch (\RuntimeException $e) {
            $msg = $e->getMessage();
            if (stripos($msg, 'ORDER_ALREADY_CAPTURED') !== false) {
                $po = PayPal::getOrder($order->payment_intent_id);
            } else {
                return $json ? response()->json(['message' => 'PayPal could not complete the payment: '.$msg], 422)
                    : redirect()->to($this->sroute('store.checkout'))->with('error', 'PayPal could not complete the payment: '.$msg);
            }
        }

        $sum = PayPal::summarize($po);
        if ($sum['status'] === 'COMPLETED') {
            Payments::markPaid($order, $order->payment_intent_id);
            $this->clearCart();

            return $done();
        }
        if ($sum['status'] === 'PENDING') {
            $this->clearCart();

            return $done();
        }

        Payments::cancelAndRelease($order);
        $reason = $sum['reason'] ?: ('PayPal status: '.$sum['status']);
        $msg = 'PayPal payment was not completed ('.$reason.'). Please try again or choose another method.';

        return $json ? response()->json(['message' => $msg], 422) : redirect()->to($this->sroute('store.checkout'))->with('error', $msg);
    }

    public function confirm(?string $tenant, string $orderNo)
    {
        $t = $this->boot($tenant);
        $order = Order::where('order_no', $orderNo)->with('items')->firstOrFail();

        if (in_array($order->payment_method, ['Credit / Debit Card', 'PayPal'], true) && $order->payment_status !== 'paid' && $order->status !== 'cancelled' && ! session('info')) {
            return $order->payment_method === 'PayPal'
                ? redirect()->to($this->sroute('store.paypal.return', ['orderNo' => $order->order_no]))
                : redirect()->to($this->sroute('store.stripe.return', ['orderNo' => $order->order_no]));
        }

        return view('ecommerce.store.confirm', $this->storeMeta($t) + ['order' => $order]);
    }

    public function submitReview(?string $tenant, Request $request, int $product)
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

    private function clearCart(): void
    {
        session()->forget([$this->cartKey(), $this->couponKey(), $this->intentKey()]);
    }
}
