<?php

declare(strict_types=1);

namespace App\Http\Controllers\Retail;

use App\Models\Retail\Document;
use App\Models\Retail\Payment;
use App\Models\Retail\Supplier;
use App\Support\Retail\DocumentService;
use App\Support\Retail\Ledger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Suppliers: opening balance, payable dues, payments and statement. */
class SupplierController extends RetailController
{
    public function index(Request $request)
    {
        $this->need('purchase.suppliers', 'purchase.view');
        $suppliers = Supplier::query()
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('company_name', 'like', "%$s%")->orWhere('phone', 'like', "%$s%")->orWhere('email', 'like', "%$s%")->orWhere('contact_person', 'like', "%$s%")))
            ->when($request->status === 'deleted', fn ($q) => $q->onlyTrashed())
            ->when($request->status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when(! in_array($request->status, ['inactive', 'all', 'deleted'], true), fn ($q) => $q->where('is_active', true))
            ->orderBy('company_name')->paginate(30)->withQueryString();
        $dues = Ledger::dues('supplier', $suppliers->getCollection());
        if ($request->only === 'due') {
            $suppliers->setCollection($suppliers->getCollection()->filter(fn ($c) => ($dues[$c->id] ?? 0) > 0)->values());
        }

        return view('retail.parties.index', [
            'side' => 'supplier', 'parties' => $suppliers, 'dues' => $dues,
            'totalDue' => array_sum(Ledger::dues('supplier', Supplier::query()->get(['id', 'opening_balance']))),
            'canEdit' => $this->can('purchase.suppliers'),
        ]);
    }

    public function create()
    {
        $this->need('purchase.suppliers');

        return view('retail.parties.form', ['side' => 'supplier', 'party' => new Supplier(['is_active' => true])]);
    }

    public function edit(Supplier $supplier)
    {
        $this->need('purchase.suppliers');

        return view('retail.parties.form', ['side' => 'supplier', 'party' => $supplier]);
    }

    public function store(Request $request)
    {
        $this->need('purchase.suppliers');
        $s = Supplier::create($this->data($request) + ['created_by' => auth()->id()]);

        return $request->wantsJson()
            ? response()->json(['id' => $s->id, 'name' => $s->company_name, 'due' => 0])
            : redirect()->route('purchase.suppliers.show', $s)->with('success', 'Supplier added.');
    }

    public function update(Request $request, Supplier $supplier)
    {
        $this->need('purchase.suppliers');
        $supplier->update($this->data($request));

        return redirect()->route('purchase.suppliers.show', $supplier)->with('success', 'Supplier saved.');
    }

    public function destroy(Supplier $supplier)
    {
        $this->need('purchase.suppliers');
        $supplier->delete();

        return redirect()->route('purchase.suppliers.index')->with('success', 'Supplier deleted. You can restore it from the Deleted filter.');
    }

    public function restore(int $id)
    {
        $this->need('purchase.suppliers');
        Supplier::onlyTrashed()->findOrFail($id)->restore();

        return back()->with('success', 'Supplier restored.');
    }

    public function show(Supplier $supplier)
    {
        $this->need('purchase.suppliers', 'purchase.view');

        return view('retail.parties.show', [
            'side' => 'supplier', 'party' => $supplier,
            'due' => Ledger::supplierDue($supplier->id), 'openingDue' => Ledger::supplierOpeningDue($supplier), 'advance' => 0,
            'documents' => Document::query()->where('supplier_id', $supplier->id)->whereIn('type', ['purchase', 'purchase_return', 'order'])->latest('date')->latest('id')->limit(30)->get(),
            'payments' => Payment::query()->with('document')->where('party_type', 'supplier')->where('party_id', $supplier->id)->latest('date')->latest('id')->limit(30)->get(),
            'coupons' => collect(),
            'stats' => Document::query()->where('supplier_id', $supplier->id)->where('type', 'purchase')
                ->selectRaw('COUNT(*) n, SUM(grand_total) total, SUM(paid_amount) paid')->first(),
            'methods' => self::methods(), 'canPay' => $this->can('purchase.payments'), 'canEdit' => $this->can('purchase.suppliers'),
        ]);
    }

    /** Pay a supplier: opening balance first, then the oldest unpaid purchases. */
    public function pay(Request $request, Supplier $supplier)
    {
        $this->need('purchase.payments');
        $data = $request->validate($this->paymentRules());
        $left = (float) $data['amount'];
        $due = Ledger::supplierDue($supplier->id);
        if ($left > $due + 0.004) {
            return back()->with('error', 'Amount is more than the total due ('.number_format($due, 2).').');
        }

        DB::transaction(function () use ($supplier, $data, &$left) {
            $opening = Ledger::supplierOpeningDue($supplier);
            if ($opening > 0 && $left > 0) {
                $pay = min($opening, $left);
                Payment::create(array_merge($data, ['amount' => $pay, 'code' => DocumentService::paymentCode('out'), 'direction' => 'out', 'kind' => 'opening',
                    'party_type' => 'supplier', 'party_id' => $supplier->id, 'cheque_status' => ! empty($data['cheque_no']) ? 'pending' : null, 'created_by' => auth()->id()]));
                $left -= $pay;
            }
            foreach (Document::query()->where('supplier_id', $supplier->id)->where('type', 'purchase')->whereColumn('paid_amount', '<', 'grand_total')->orderBy('date')->orderBy('id')->get() as $doc) {
                if ($left <= 0.004) {
                    break;
                }
                $pay = min($doc->due(), $left);
                DocumentService::addPayment($doc, array_merge($data, ['amount' => $pay]));
                DocumentService::refreshPaid($doc);
                $left -= $pay;
            }
        });

        return back()->with('success', 'Payment recorded.');
    }

    public function statement(Request $request, Supplier $supplier)
    {
        $this->need('purchase.suppliers', 'purchase.view', 'purchase.reports');

        return view('retail.parties.statement', ['side' => 'supplier'] + Ledger::statement('supplier', $supplier->id, $request->from, $request->to));
    }

    public function search(Request $request)
    {
        $this->need('purchase.view', 'purchase.orders', 'purchase.returns');
        $q = trim((string) $request->q);
        $rows = Supplier::query()->where('is_active', true)
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('company_name', 'like', "%$q%")->orWhere('phone', 'like', "%$q%")))
            ->orderBy('company_name')->limit(15)->get();
        $dues = Ledger::dues('supplier', $rows);

        return response()->json($rows->map(fn ($s) => ['id' => $s->id, 'name' => $s->company_name, 'mobile' => $s->phone, 'due' => $dues[$s->id] ?? 0]));
    }

    private function data(Request $request): array
    {
        return $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:32'],
            'whatsapp' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:150'],
            'website' => ['nullable', 'string', 'max:255'],
            'vat_number' => ['nullable', 'string', 'max:100'],
            'street' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'zip' => ['nullable', 'string', 'max:32'],
            'opening_balance' => ['nullable', 'numeric'],
        ]) + ['is_active' => $request->boolean('is_active', true), 'opening_balance' => (float) $request->input('opening_balance', 0)];
    }
}
