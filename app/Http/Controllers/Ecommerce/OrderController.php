<?php

declare(strict_types=1);

namespace App\Http\Controllers\Ecommerce;

use App\Models\Ecommerce\Order;
use App\Support\Ecommerce\OrderService;
use Illuminate\Http\Request;

/** Online orders: manage status, payment, and convert into a Sales invoice. */
class OrderController extends EcommerceController
{
    public function index(Request $request)
    {
        $this->need('ecommerce.view', 'ecommerce.manage_orders');
        $orders = Order::query()->withCount('items')
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('order_no', 'like', "%$s%")->orWhere('customer_name', 'like', "%$s%")->orWhere('customer_email', 'like', "%$s%")))
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->payment, fn ($q, $v) => $q->where('payment_status', $v))
            ->latest('id')->paginate(20)->withQueryString();

        return view('ecommerce.orders.index', [
            'orders' => $orders,
            'statuses' => Order::STATUSES,
            'counts' => Order::query()->selectRaw('status, COUNT(*) n')->groupBy('status')->pluck('n', 'status'),
            'revenue' => Order::whereNotIn('status', ['cancelled'])->sum('grand_total'),
            'canManage' => $this->can('ecommerce.manage_orders'),
        ]);
    }

    public function show(Order $order)
    {
        $this->need('ecommerce.view', 'ecommerce.manage_orders');

        return view('ecommerce.orders.show', [
            'order' => $order->load('items', 'customer'),
            'statuses' => Order::STATUSES,
            'saleDoc' => $order->sale_document_id ? \App\Models\Retail\Document::find($order->sale_document_id) : null,
            'canManage' => $this->can('ecommerce.manage_orders'),
        ]);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $this->need('ecommerce.manage_orders');
        $data = $request->validate([
            'status' => ['required', 'in:pending,confirmed,processing,shipped,delivered,cancelled'],
            'payment_status' => ['required', 'in:unpaid,paid,refunded'],
        ]);
        $order->update($data);

        return back()->with('success', 'Order updated.');
    }

    public function convert(Order $order)
    {
        $this->need('ecommerce.manage_orders');
        $doc = OrderService::convertToSale($order);

        return redirect()->route('sales.invoices.show', $doc)->with('success', 'Order converted to a sales invoice. Stock has been updated.');
    }

    public function destroy(Order $order)
    {
        $this->need('ecommerce.manage_orders');
        if ($order->isConverted()) {
            return back()->with('error', 'This order has a linked sales invoice and cannot be deleted.');
        }
        $order->items()->delete();
        $order->delete();

        return redirect()->route('ecommerce.orders.index')->with('success', 'Order deleted.');
    }
}
