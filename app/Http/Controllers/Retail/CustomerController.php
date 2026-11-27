<?php

declare(strict_types=1);

namespace App\Http\Controllers\Retail;

use App\Models\Retail\Customer;
use App\Models\Retail\CustomerCoupon;
use App\Models\Retail\Document;
use App\Models\Retail\Payment;
use App\Support\Inventory\StockService;
use App\Support\Retail\DocumentService;
use App\Support\Retail\Ledger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Customers: opening balance, credit limit, price level, shipping address, dues, advances, statement. */
class CustomerController extends RetailController
{
    public function index(Request $request)
    {
        $this->need('sales.customers', 'sales.view');
        $customers = Customer::query()
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('company_name', 'like', "%$s%")->orWhere('mobile', 'like', "%$s%")->orWhere('phone', 'like', "%$s%")->orWhere('code', $s)->orWhere('email', 'like', "%$s%")))
            ->when($request->status === 'deleted', fn ($q) => $q->onlyTrashed())
            ->when($request->status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when(! in_array($request->status, ['inactive', 'all', 'deleted'], true), fn ($q) => $q->where('is_active', true))
            ->orderByDesc('is_default')->orderBy('company_name')->paginate(30)->withQueryString();
        $dues = Ledger::dues('customer', $customers->getCollection());
        if ($request->only === 'due') {
            $customers->setCollection($customers->getCollection()->filter(fn ($c) => ($dues[$c->id] ?? 0) > 0)->values());
        }

        return view('retail.parties.index', [
            'side' => 'customer', 'parties' => $customers, 'dues' => $dues,
            'totalDue' => array_sum(Ledger::dues('customer', Customer::query()->get(['id', 'opening_balance']))),
            'canEdit' => $this->can('sales.customers'),
        ]);
    }

    public function create()
    {
        $this->need('sales.customers');

        return view('retail.parties.form', ['side' => 'customer', 'party' => new Customer(['is_active' => true, 'code' => StockService::nextNumber(Customer::class, 'code', 'CU')])]);
    }

    public function edit(Customer $customer)
    {
        $this->need('sales.customers');

        return view('retail.parties.form', ['side' => 'customer', 'party' => $customer]);
    }

    public function store(Request $request)
    {
        $this->need('sales.customers');
        $c = Customer::create($this->data($request) + ['created_by' => auth()->id()]);

        return $request->wantsJson()
            ? response()->json(['id' => $c->id, 'name' => $c->company_name, 'mobile' => $c->mobile, 'due' => 0, 'advance' => 0, 'price_level_type' => $c->price_level_type, 'price_level' => $c->price_level])
            : redirect()->route('sales.customers.show', $c)->with('success', 'Customer added.');
    }

    public function update(Request $request, Customer $customer)
    {
        $this->need('sales.customers');
        $customer->update($this->data($request, $customer));

        return redirect()->route('sales.customers.show', $customer)->with('success', 'Customer saved.');
    }

    public function destroy(Customer $customer)
    {
        $this->need('sales.customers');
        if ($customer->is_default) {
            return back()->with('error', 'The walk-in customer cannot be deleted.');
        }
        $customer->delete(); // soft delete — can be restored

        return redirect()->route('sales.customers.index')->with('success', 'Customer deleted. You can restore it from the Deleted filter.');
    }

    public function restore(int $id)
    {
        $this->need('sales.customers');
        Customer::onlyTrashed()->findOrFail($id)->restore();

        return back()->with('success', 'Customer restored.');
    }

    public function show(Customer $customer)
    {
        $this->need('sales.customers', 'sales.view');

        return view('retail.parties.show', [
            'side' => 'customer', 'party' => $customer,
            'due' => Ledger::customerDue($customer->id), 'openingDue' => Ledger::customerOpeningDue($customer), 'advance' => Ledger::customerAdvance($customer->id),
            'documents' => Document::query()->where('customer_id', $customer->id)->whereIn('type', ['invoice', 'sale_return', 'quotation', 'delivery'])->latest('date')->latest('id')->limit(30)->get(),
            'payments' => Payment::query()->with('document')->where('party_type', 'customer')->where('party_id', $customer->id)->latest('date')->latest('id')->limit(30)->get(),
            'coupons' => CustomerCoupon::query()->with('coupon')->where('customer_id', $customer->id)->latest()->get(),
            'stats' => Document::query()->where('customer_id', $customer->id)->where('type', 'invoice')->where('status', 'final')
                ->selectRaw('COUNT(*) n, SUM(grand_total) total, SUM(paid_amount) paid')->first(),
            'methods' => self::methods(), 'canPay' => $this->can('sales.payments'), 'canEdit' => $this->can('sales.customers'),
        ]);
    }

    /**
     * Receive money from a customer against their dues: opening balance first, then the oldest unpaid invoices.
     * Anything left over is kept as an advance.
     */
    public function receive(Request $request, Customer $customer)
    {
        $this->need('sales.payments');
        $data = $request->validate($this->paymentRules());
        $left = (float) $data['amount'];

        DB::transaction(function () use ($customer, $data, &$left) {
            // Only the customer's net due can be settled (unrefunded returns already reduce it); the rest becomes advance.
            $applicable = min($left, max(0, Ledger::customerDue($customer->id)));
            $extra = $left - $applicable;
            $left = $applicable;
            $opening = Ledger::customerOpeningDue($customer);
            if ($opening > 0 && $left > 0) {
                $pay = min($opening, $left);
                Payment::create(array_merge($data, ['amount' => $pay, 'code' => DocumentService::paymentCode('in'), 'direction' => 'in', 'kind' => 'opening',
                    'party_type' => 'customer', 'party_id' => $customer->id, 'cheque_status' => ! empty($data['cheque_no']) ? 'pending' : null, 'created_by' => auth()->id()]));
                $left -= $pay;
            }
            $invoices = Document::query()->where('customer_id', $customer->id)->where('type', 'invoice')->where('status', 'final')
                ->whereColumn('paid_amount', '<', 'grand_total')->orderBy('date')->orderBy('id')->get();
            foreach ($invoices as $inv) {
                if ($left <= 0.004) {
                    break;
                }
                $pay = min($inv->due(), $left);
                DocumentService::addPayment($inv, array_merge($data, ['amount' => $pay]));
                DocumentService::refreshPaid($inv);
                $left -= $pay;
            }
            $left += $extra;
            if ($left > 0.004) {
                Payment::create(array_merge($data, ['amount' => round($left, 4), 'code' => DocumentService::paymentCode('in'), 'direction' => 'in', 'kind' => 'advance',
                    'party_type' => 'customer', 'party_id' => $customer->id, 'note' => trim(($data['note'] ?? '').' (extra kept as advance)'), 'created_by' => auth()->id()]));
            }
        });

        return back()->with('success', 'Payment received.'.($left > 0.004 ? ' '.number_format($left, 2).' was kept as advance.' : ''));
    }

    /** Customer advance (money received before an invoice). */
    public function advance(Request $request, Customer $customer)
    {
        $this->need('sales.payments');
        $data = $request->validate($this->paymentRules());
        Payment::create($data + ['code' => DocumentService::paymentCode('in'), 'direction' => 'in', 'kind' => 'advance', 'party_type' => 'customer',
            'party_id' => $customer->id, 'created_by' => auth()->id()]);

        return back()->with('success', 'Advance recorded.');
    }

    public function statement(Request $request, Customer $customer)
    {
        $this->need('sales.customers', 'sales.view', 'sales.reports');

        return view('retail.parties.statement', ['side' => 'customer'] + Ledger::statement('customer', $customer->id, $request->from, $request->to));
    }

    /** JSON for the customer picker on sales / POS. */
    public function search(Request $request)
    {
        $this->need('sales.view', 'pos.sell', 'sales.quotations', 'sales.delivery_notes', 'sales.returns');
        $q = trim((string) $request->q);
        $rows = Customer::query()->where('is_active', true)
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('company_name', 'like', "%$q%")->orWhere('mobile', 'like', "%$q%")->orWhere('phone', 'like', "%$q%")->orWhere('code', $q)))
            ->orderByDesc('is_default')->orderBy('company_name')->limit(15)->get();
        $dues = Ledger::dues('customer', $rows);

        return response()->json($rows->map(fn ($c) => [
            'id' => $c->id, 'name' => $c->company_name, 'mobile' => $c->mobile ?: $c->phone, 'code' => $c->code,
            'due' => $dues[$c->id] ?? 0, 'advance' => Ledger::customerAdvance($c->id), 'credit_limit' => $c->credit_limit,
            'price_level_type' => $c->price_level_type, 'price_level' => $c->price_level, 'is_default' => $c->is_default,
        ]));
    }

    private function data(Request $request, ?Customer $c = null): array
    {
        return $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'contact_person' => ['nullable', 'string', 'max:150'],
            'mobile' => ['nullable', 'string', 'max:32'],
            'phone' => ['nullable', 'string', 'max:32'],
            'whatsapp' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:150'],
            'vat_number' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'zip' => ['nullable', 'string', 'max:32'],
            'location_url' => ['nullable', 'string', 'max:500'],
            'ship_address' => ['nullable', 'string', 'max:255'],
            'ship_city' => ['nullable', 'string', 'max:100'],
            'ship_zip' => ['nullable', 'string', 'max:32'],
            'opening_balance' => ['nullable', 'numeric'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'price_level_type' => ['nullable', 'in:increase,decrease'],
            'price_level' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]) + ['is_active' => $request->boolean('is_active', true), 'opening_balance' => (float) $request->input('opening_balance', 0),
            'credit_limit' => (float) $request->input('credit_limit', 0), 'price_level' => (float) $request->input('price_level', 0)];
    }
}
