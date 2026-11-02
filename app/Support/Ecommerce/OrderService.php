<?php

declare(strict_types=1);

namespace App\Support\Ecommerce;

use App\Models\Ecommerce\Coupon;
use App\Models\Ecommerce\Order;
use App\Models\Ecommerce\Product;
use App\Models\Ecommerce\ShippingMethod;
use App\Models\Inventory\Warehouse;
use App\Models\Retail\Customer;
use App\Support\Retail\DocumentService;
use Illuminate\Support\Facades\DB;

/** Order totals, order creation from storefront, and conversion into a Sales invoice. */
class OrderService
{
    /**
     * Build totals for a set of cart lines.
     *
     * @param  array<int,array{product_id:int,qty:float}>  $lines
     * @return array{rows:array,subtotal:float,tax:float}
     */
    public static function price(array $lines): array
    {
        $rows = [];
        $subtotal = 0.0;
        $tax = 0.0;
        foreach ($lines as $l) {
            $p = Product::with('item.tax')->find($l['product_id'] ?? 0);
            if (! $p || ! $p->is_published) {
                continue;
            }
            $qty = max(1, (float) ($l['qty'] ?? 1));
            $price = (float) $p->price;
            $lineNet = $price * $qty;
            $rate = $p->item?->tax?->rate ?? 0;
            $lineTax = round($lineNet * $rate / 100, 2);
            $rows[] = [
                'product_id' => $p->id, 'item_id' => $p->item_id, 'name' => $p->name,
                'slug' => $p->slug, 'image_url' => $p->image_url,
                'qty' => $qty, 'price' => $price, 'tax' => $lineTax, 'tax_id' => $p->item?->tax_id,
                'line_total' => round($lineNet + $lineTax, 2),
            ];
            $subtotal += $lineNet;
            $tax += $lineTax;
        }

        return ['rows' => $rows, 'subtotal' => round($subtotal, 2), 'tax' => round($tax, 2)];
    }

    /** Create an order from cart lines + checkout details. */
    public static function createOrder(array $lines, array $checkout): Order
    {
        $priced = self::price($lines);
        if (! $priced['rows']) {
            throw \Illuminate\Validation\ValidationException::withMessages(['cart' => 'Your cart is empty.']);
        }
        $subtotal = $priced['subtotal'];
        $tax = $priced['tax'];

        $shippingCost = 0.0;
        if (! empty($checkout['shipping_method_id']) && $sm = ShippingMethod::find($checkout['shipping_method_id'])) {
            $shippingCost = $sm->costFor($subtotal);
        }
        $discount = 0.0;
        $couponCode = null;
        if (! empty($checkout['coupon_code']) && $c = Coupon::where('code', $checkout['coupon_code'])->first()) {
            $discount = $c->discountFor($subtotal);
            if ($discount > 0) {
                $couponCode = $c->code;
            }
        }
        $grand = round($subtotal + $tax + $shippingCost - $discount, 2);

        return DB::connection('tenant')->transaction(function () use ($priced, $checkout, $subtotal, $tax, $shippingCost, $discount, $couponCode, $grand) {
            $order = Order::create([
                'order_no' => Order::nextNumber(),
                'customer_id' => $checkout['customer_id'] ?? null,
                'customer_name' => $checkout['customer_name'],
                'customer_email' => $checkout['customer_email'] ?? null,
                'customer_phone' => $checkout['customer_phone'] ?? null,
                'shipping_address' => $checkout['shipping_address'] ?? null,
                'status' => 'pending', 'payment_status' => 'unpaid',
                'payment_method' => $checkout['payment_method'] ?? 'Cash on Delivery',
                'shipping_method_id' => $checkout['shipping_method_id'] ?? null,
                'subtotal' => $subtotal, 'shipping_cost' => $shippingCost, 'discount' => $discount, 'tax' => $tax,
                'grand_total' => $grand, 'coupon_code' => $couponCode, 'notes' => $checkout['notes'] ?? null,
                'placed_at' => now(), 'created_by' => auth()->id(),
            ]);
            foreach ($priced['rows'] as $r) {
                $order->items()->create([
                    'item_id' => $r['item_id'], 'product_id' => $r['product_id'], 'name' => $r['name'],
                    'qty' => $r['qty'], 'price' => $r['price'], 'tax' => $r['tax'], 'line_total' => $r['line_total'],
                ]);
            }
            if ($couponCode) {
                Coupon::where('code', $couponCode)->increment('used');
            }

            return $order;
        });
    }

    /** Convert a confirmed order into a retail Sales invoice (stock moves out via DocumentService). */
    public static function convertToSale(Order $order): \App\Models\Retail\Document
    {
        if ($order->isConverted()) {
            throw \Illuminate\Validation\ValidationException::withMessages(['order' => 'This order has already been converted to a sale.']);
        }

        return DB::connection('tenant')->transaction(function () use ($order) {
            // Match or create a retail customer.
            $customerId = $order->customer_id;
            if (! $customerId) {
                $cust = Customer::query()
                    ->when($order->customer_email, fn ($q) => $q->orWhere('email', $order->customer_email))
                    ->when($order->customer_phone, fn ($q) => $q->orWhere('phone', $order->customer_phone))
                    ->first()
                    ?? Customer::create(['company_name' => $order->customer_name, 'email' => $order->customer_email, 'phone' => $order->customer_phone, 'address' => $order->shipping_address, 'is_active' => true]);
                $customerId = $cust->id;
                $order->update(['customer_id' => $customerId]);
            }

            $rows = $order->items->filter(fn ($i) => $i->item_id)->map(fn ($i) => [
                'item_id' => $i->item_id, 'qty' => $i->qty, 'unit_price' => $i->price,
                'tax_id' => \App\Models\Inventory\Item::whereKey($i->item_id)->value('tax_id'),
            ])->values()->all();

            $header = [
                'customer_id' => $customerId,
                'warehouse_id' => Warehouse::default()->id,
                'date' => now()->toDateString(),
                'status' => 'final',
                'reference_no' => $order->order_no,
                'shipping' => $order->shipping_cost,
                'discount_input' => $order->discount,
                'note' => 'Online order '.$order->order_no,
            ];
            $doc = DocumentService::save(new \App\Models\Retail\Document(), 'invoice', $header, $rows);
            $order->update(['sale_document_id' => $doc->id, 'status' => $order->status === 'pending' ? 'confirmed' : $order->status]);

            return $doc;
        });
    }
}
