<?php

declare(strict_types=1);

namespace App\Http\Controllers\Ecommerce;

use App\Models\Ecommerce\Coupon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Store coupons & promotions. */
class PromotionController extends EcommerceController
{
    public function index()
    {
        $this->need('ecommerce.manage_promotions');

        return view('ecommerce.promotions.index', ['coupons' => Coupon::latest('id')->paginate(25)]);
    }

    public function create()
    {
        $this->need('ecommerce.manage_promotions');

        return view('ecommerce.promotions.form', ['coupon' => new Coupon(['type' => 'percent', 'is_active' => true])]);
    }

    public function edit(Coupon $coupon)
    {
        $this->need('ecommerce.manage_promotions');

        return view('ecommerce.promotions.form', ['coupon' => $coupon]);
    }

    public function store(Request $request)
    {
        $this->need('ecommerce.manage_promotions');
        Coupon::create($this->data($request));

        return redirect()->route('ecommerce.promotions.index')->with('success', 'Coupon created.');
    }

    public function update(Request $request, Coupon $coupon)
    {
        $this->need('ecommerce.manage_promotions');
        $coupon->update($this->data($request, $coupon->id));

        return redirect()->route('ecommerce.promotions.index')->with('success', 'Coupon saved.');
    }

    public function destroy(Coupon $coupon)
    {
        $this->need('ecommerce.manage_promotions');
        $coupon->delete();

        return back()->with('success', 'Coupon deleted.');
    }

    private function data(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:40', Rule::unique('ecom_coupons', 'code')->ignore($id)],
            'type' => ['required', 'in:percent,fixed'],
            'value' => ['required', 'numeric', 'min:0'],
            'min_order' => ['nullable', 'numeric', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
        ]) + ['is_active' => $request->boolean('is_active', true), 'value' => (float) $request->input('value'), 'min_order' => (float) $request->input('min_order', 0)];
    }
}
