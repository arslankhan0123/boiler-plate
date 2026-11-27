<?php

declare(strict_types=1);

namespace App\Http\Controllers\Ecommerce;

use App\Models\Ecommerce\ShippingMethod;
use Illuminate\Http\Request;

/** Store shipping & delivery methods. */
class ShippingController extends EcommerceController
{
    public function index()
    {
        $this->need('ecommerce.manage_shipping');

        return view('ecommerce.shipping.index', ['methods' => ShippingMethod::orderBy('sort')->orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $this->need('ecommerce.manage_shipping');
        ShippingMethod::create($this->data($request));

        return back()->with('success', 'Shipping method added.');
    }

    public function update(Request $request, ShippingMethod $method)
    {
        $this->need('ecommerce.manage_shipping');
        $method->update($this->data($request));

        return back()->with('success', 'Shipping method saved.');
    }

    public function destroy(ShippingMethod $method)
    {
        $this->need('ecommerce.manage_shipping');
        $method->delete();

        return back()->with('success', 'Shipping method deleted.');
    }

    private function data(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'rate' => ['required', 'numeric', 'min:0'],
            'free_over' => ['nullable', 'numeric', 'min:0'],
            'sort' => ['nullable', 'integer'],
        ]) + ['is_active' => $request->boolean('is_active', true), 'rate' => (float) $request->input('rate'), 'free_over' => $request->filled('free_over') ? (float) $request->input('free_over') : null];
    }
}
