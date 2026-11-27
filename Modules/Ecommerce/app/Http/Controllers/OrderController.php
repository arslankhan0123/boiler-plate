<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Ecommerce\Models\Invoice;
use Modules\Ecommerce\Models\Order;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\Service;

class OrderController extends Controller
{
    public function index(Request $r): JsonResponse
    {
        return ApiResponse::successResponse('Orders retrieved.', Order::query()->with('invoice')->when($r->filled('status'), fn ($q) => $q->where('status', $r->string('status')))->when($r->filled('payment_status'), fn ($q) => $q->where('payment_status', $r->string('payment_status')))->latest()->paginate($r->integer('per_page', 20)));
    }

    public function show(Order $order): JsonResponse
    {
        return ApiResponse::successResponse('Order retrieved.', $order->load(['items', 'tracking', 'invoice.payments']));
    }

    public function store(Request $r): JsonResponse
    {
        $data = $this->orderData($r);
        $order = DB::transaction(function () use ($data) {
            $number = 'ORD-'.now()->format('Ymd').'-'.str_pad((string) (Order::query()->count() + 1), 5, '0', STR_PAD_LEFT);
            $order = Order::create(['number' => $number, 'customer_name' => $data['customer_name'], 'customer_email' => $data['customer_email'] ?? null, 'customer_phone' => $data['customer_phone'] ?? null, 'billing_address' => $data['billing_address'] ?? null, 'shipping_address' => $data['shipping_address'] ?? null, 'status' => $data['status'] ?? 'pending', 'payment_status' => 'unpaid', 'shipping_total' => $data['shipping_total'] ?? 0, 'notes' => $data['notes'] ?? null, 'placed_at' => now(), 'created_by' => Auth::guard('api')->id(), 'subtotal' => 0, 'discount_total' => $data['discount_total'] ?? 0, 'tax_total' => 0, 'grand_total' => 0]);
            $subtotal = 0;
            $tax = 0;
            foreach ($data['items'] as $row) {
                $item = $this->resolveItem($row);
                $qty = (int) $row['quantity'];
                if ($item instanceof Product && $item->track_inventory && $item->stock_quantity < $qty) {
                    throw ValidationException::withMessages(['items' => 'Insufficient stock for '.$item->name.'.']);
                }
                $price = (float) ($row['unit_price'] ?? $item->sale_price);
                $discount = (float) ($row['discount_total'] ?? 0);
                $rate = (float) ($row['tax_rate'] ?? $item->tax_rate);
                $line = round(($price * $qty) - $discount, 2);
                $lineTax = round($line * $rate / 100, 2);
                $order->items()->create(['item_type' => $item instanceof Product ? 'product' : 'service', 'product_id' => $item instanceof Product ? $item->id : null, 'service_id' => $item instanceof Service ? $item->id : null, 'name' => $item->name, 'sku' => $item->sku, 'quantity' => $qty, 'unit_price' => $price, 'cost_price' => $item->cost_price, 'tax_rate' => $rate, 'tax_total' => $lineTax, 'discount_total' => $discount, 'line_total' => $line, 'meta' => $row['meta'] ?? null]);
                if ($item instanceof Product && $item->track_inventory) {
                    $item->decrement('stock_quantity', $qty);
                }
                $subtotal += $line;
                $tax += $lineTax;
            }
            $order->update(['subtotal' => $subtotal, 'tax_total' => $tax, 'grand_total' => $subtotal + $tax - (float) $order->discount_total + (float) $order->shipping_total]);

            return $order;
        });

        return ApiResponse::successResponse('Order created.', $order->load('items'));
    }

    public function update(Request $r, Order $order): JsonResponse
    {
        $data = $r->validate(['status' => ['sometimes', 'in:pending,confirmed,processing,shipped,delivered,cancelled,refunded'], 'payment_status' => ['sometimes', 'in:unpaid,partial,paid,refunded'], 'customer_name' => ['sometimes', 'string', 'max:255'], 'customer_email' => ['nullable', 'email'], 'customer_phone' => ['nullable', 'string', 'max:50'], 'billing_address' => ['nullable', 'array'], 'shipping_address' => ['nullable', 'array'], 'shipping_total' => ['sometimes', 'numeric', 'min:0'], 'notes' => ['nullable', 'string']]);
        $order->update($data);

        return ApiResponse::successResponse('Order updated.', $order->fresh());
    }

    public function addTracking(Request $r, Order $order): JsonResponse
    {
        $data = $r->validate(['status' => ['required', 'in:confirmed,processing,shipped,delivered,cancelled,returned'], 'message' => ['nullable', 'string'], 'carrier' => ['nullable', 'string', 'max:100'], 'tracking_number' => ['nullable', 'string', 'max:255'], 'location' => ['nullable', 'string', 'max:255'], 'occurred_at' => ['nullable', 'date']]);
        $data['occurred_at'] = $data['occurred_at'] ?? now();
        $data['created_by'] = Auth::guard('api')->id();
        $tracking = $order->tracking()->create($data);
        $order->update(['status' => $data['status']]);

        return ApiResponse::successResponse('Tracking event added.', $tracking);
    }

    public function createInvoice(Request $r, Order $order): JsonResponse
    {
        if ($order->invoice) {
            throw ValidationException::withMessages(['order' => 'An invoice already exists for this order.']);
        }
        $data = $r->validate(['due_at' => ['nullable', 'date', 'after_or_equal:today'], 'notes' => ['nullable', 'string']]);
        $invoice = $order->invoice()->create(['number' => 'INV-'.now()->format('Ymd').'-'.str_pad((string) (Invoice::query()->count() + 1), 5, '0', STR_PAD_LEFT), 'status' => 'issued', 'issued_at' => today(), 'due_at' => $data['due_at'] ?? null, 'subtotal' => $order->subtotal, 'tax_total' => $order->tax_total, 'discount_total' => $order->discount_total, 'grand_total' => $order->grand_total, 'paid_total' => 0, 'notes' => $data['notes'] ?? null, 'created_by' => Auth::guard('api')->id()]);

        return ApiResponse::successResponse('Invoice created.', $invoice);
    }

    public function addPayment(Request $r, Invoice $invoice): JsonResponse
    {
        $data = $r->validate(['amount' => ['required', 'numeric', 'gt:0'], 'method' => ['required', 'string', 'max:100'], 'reference' => ['nullable', 'string', 'max:255'], 'paid_at' => ['nullable', 'date'], 'notes' => ['nullable', 'string']]);
        if ((float) $data['amount'] > ((float) $invoice->grand_total - (float) $invoice->paid_total)) {
            throw ValidationException::withMessages(['amount' => 'Payment exceeds the invoice balance.']);
        }
        $payment = $invoice->payments()->create($data + ['paid_at' => $data['paid_at'] ?? now(), 'created_by' => Auth::guard('api')->id()]);
        $paid = (float) $invoice->paid_total + (float) $data['amount'];
        $invoice->update(['paid_total' => $paid, 'status' => $paid >= (float) $invoice->grand_total ? 'paid' : 'partial']);
        $invoice->order->update(['payment_status' => $invoice->status]);

        return ApiResponse::successResponse('Payment recorded.', $payment);
    }

    private function orderData(Request $r): array
    {
        return $r->validate(['customer_name' => ['required', 'string', 'max:255'], 'customer_email' => ['nullable', 'email'], 'customer_phone' => ['nullable', 'string', 'max:50'], 'billing_address' => ['nullable', 'array'], 'shipping_address' => ['nullable', 'array'], 'status' => ['nullable', 'in:pending,confirmed,processing,shipped,delivered,cancelled'], 'shipping_total' => ['nullable', 'numeric', 'min:0'], 'discount_total' => ['nullable', 'numeric', 'min:0'], 'notes' => ['nullable', 'string'], 'items' => ['required', 'array', 'min:1'], 'items.*.type' => ['required', 'in:product,service'], 'items.*.id' => ['required', 'integer'], 'items.*.quantity' => ['required', 'integer', 'min:1'], 'items.*.unit_price' => ['nullable', 'numeric', 'min:0'], 'items.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'], 'items.*.discount_total' => ['nullable', 'numeric', 'min:0'], 'items.*.meta' => ['nullable', 'array']]);
    }

    private function resolveItem(array $row): Product|Service
    {
        return $row['type'] === 'product' ? Product::query()->where('is_active', true)->findOrFail($row['id']) : Service::query()->where('is_active', true)->findOrFail($row['id']);
    }
}
