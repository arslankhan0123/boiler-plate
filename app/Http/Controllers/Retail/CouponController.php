<?php

declare(strict_types=1);

namespace App\Http\Controllers\Retail;

use App\Models\Retail\Coupon;
use App\Models\Retail\Customer;
use App\Models\Retail\CustomerCoupon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** Discount coupons (anyone can use the code) and customer coupons (one-time codes issued to customers). */
class CouponController extends RetailController
{
    public function index()
    {
        $this->need('sales.coupons');

        return view('retail.coupons.index', [
            'coupons' => Coupon::query()->withCount(['customerCoupons', 'customerCoupons as used_count' => fn ($q) => $q->whereNotNull('used_document_id')])->latest()->get(),
            'usage' => \App\Models\Retail\Document::query()->whereNotNull('coupon_id')->where('type', 'invoice')->groupBy('coupon_id')->selectRaw('coupon_id, COUNT(*) n, SUM(coupon_amt) amt')->get()->keyBy('coupon_id'),
            'editing' => request('edit') ? Coupon::query()->find(request('edit')) : null,
        ]);
    }

    public function store(Request $request)
    {
        $this->need('sales.coupons');
        Coupon::create($this->data($request));

        return back()->with('success', 'Coupon created.');
    }

    public function update(Request $request, Coupon $coupon)
    {
        $this->need('sales.coupons');
        $coupon->update($this->data($request, $coupon));

        return redirect()->route('sales.coupons.index')->with('success', 'Coupon saved.');
    }

    public function destroy(Coupon $coupon)
    {
        $this->need('sales.coupons');
        if (\App\Models\Retail\Document::query()->where('coupon_id', $coupon->id)->exists()) {
            $coupon->update(['is_active' => false]);

            return back()->with('success', 'Coupon was used on sales, so it was deactivated instead.');
        }
        $coupon->delete();

        return back()->with('success', 'Coupon deleted.');
    }

    /** Customer coupons list + generate form. */
    public function customer(Request $request)
    {
        $this->need('sales.coupons');

        return view('retail.coupons.customer', [
            'rows' => CustomerCoupon::query()->with('coupon', 'customer')
                ->when($request->coupon, fn ($q, $v) => $q->where('coupon_id', $v))
                ->when($request->state === 'used', fn ($q) => $q->whereNotNull('used_document_id'))
                ->when($request->state === 'unused', fn ($q) => $q->whereNull('used_document_id'))
                ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('code', 'like', "%$s%")->orWhereHas('customer', fn ($c) => $c->where('company_name', 'like', "%$s%"))))
                ->latest()->paginate(40)->withQueryString(),
            'coupons' => Coupon::query()->where('is_active', true)->orderBy('name')->get(),
            'customers' => Customer::query()->where('is_active', true)->where('is_default', false)->orderBy('company_name')->pluck('company_name', 'id'),
        ]);
    }

    /** Issue a coupon to the chosen customers (or all), each with its own code. */
    public function generate(Request $request)
    {
        $this->need('sales.coupons');
        $data = $request->validate([
            'coupon_id' => ['required', 'exists:rt_coupons,id'],
            'customer_ids' => ['nullable', 'array'], 'customer_ids.*' => ['integer'],
            'all' => ['nullable', 'boolean'],
            'expire_date' => ['nullable', 'date'],
        ]);
        $coupon = Coupon::query()->findOrFail($data['coupon_id']);
        $ids = $request->boolean('all')
            ? Customer::query()->where('is_active', true)->where('is_default', false)->pluck('id')->all()
            : ($data['customer_ids'] ?? []);
        if (! $ids) {
            return back()->with('error', 'Choose at least one customer.');
        }
        foreach ($ids as $cid) {
            do {
                $code = strtoupper(Str::limit(preg_replace('/\W/', '', $coupon->code), 6, '')).'-'.strtoupper(Str::random(6));
            } while (CustomerCoupon::query()->where('code', $code)->exists());
            CustomerCoupon::create(['coupon_id' => $coupon->id, 'customer_id' => $cid, 'code' => $code, 'expire_date' => $data['expire_date'] ?? $coupon->expire_date, 'is_active' => true]);
        }

        return back()->with('success', count($ids).' customer coupon(s) generated.');
    }

    public function toggleCustomer(CustomerCoupon $customerCoupon)
    {
        $this->need('sales.coupons');
        $customerCoupon->update(['is_active' => ! $customerCoupon->is_active]);

        return back()->with('success', 'Coupon '.($customerCoupon->is_active ? 'activated' : 'deactivated').'.');
    }

    public function destroyCustomer(CustomerCoupon $customerCoupon)
    {
        $this->need('sales.coupons');
        if ($customerCoupon->used_document_id) {
            return back()->with('error', 'A used coupon cannot be deleted.');
        }
        $customerCoupon->delete();

        return back()->with('success', 'Customer coupon deleted.');
    }

    /** Printable coupon cards. */
    public function print(Request $request)
    {
        $this->need('sales.coupons');

        return view('retail.coupons.print', [
            'rows' => CustomerCoupon::query()->with('coupon', 'customer')->whereIn('id', (array) $request->input('ids', []))->get(),
        ]);
    }

    private function data(Request $request, ?Coupon $c = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('rt_coupons', 'code')->ignore($c?->id)],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'type' => ['required', 'in:percentage,fixed'],
            'value' => ['required', 'numeric', 'gt:0', $request->type === 'percentage' ? 'max:100' : 'max:99999999'],
            'min_amount' => ['nullable', 'numeric', 'min:0'],
            'expire_date' => ['nullable', 'date'],
        ]) + ['is_active' => $request->boolean('is_active', true), 'min_amount' => (float) $request->input('min_amount', 0)];
    }
}
