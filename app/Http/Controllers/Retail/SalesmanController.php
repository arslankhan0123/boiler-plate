<?php

declare(strict_types=1);

namespace App\Http\Controllers\Retail;

use App\Models\Retail\Document;
use App\Models\Retail\DocumentItem;
use App\Models\Retail\Salesman;
use App\Models\Retail\SalesmanPayment;
use App\Support\Inventory\StockService;
use Illuminate\Http\Request;

/**
 * Salesmen: earn seller points on the items they sell (+ optional commission % of sales),
 * get paid / take advances; balance = opening + earned − paid − advances.
 */
class SalesmanController extends RetailController
{
    public function index()
    {
        $this->need('sales.salesmen');
        $rows = Salesman::query()->orderBy('name')->get();

        return view('retail.salesmen.index', [
            'rows' => $rows, 'stats' => $rows->mapWithKeys(fn ($s) => [$s->id => $this->summary($s)]),
            'editing' => request('edit') ? Salesman::query()->find(request('edit')) : null,
            'nextCode' => StockService::nextNumber(Salesman::class, 'code', 'SM'),
        ]);
    }

    public function store(Request $request)
    {
        $this->need('sales.salesmen');
        Salesman::create($this->data($request));

        return back()->with('success', 'Salesman added.');
    }

    public function update(Request $request, Salesman $salesman)
    {
        $this->need('sales.salesmen');
        $salesman->update($this->data($request));

        return redirect()->route('sales.salesmen.index')->with('success', 'Salesman saved.');
    }

    public function destroy(Salesman $salesman)
    {
        $this->need('sales.salesmen');
        $salesman->delete();

        return back()->with('success', 'Salesman deleted.');
    }

    public function show(Request $request, Salesman $salesman)
    {
        $this->need('sales.salesmen');

        return view('retail.salesmen.show', [
            's' => $salesman, 'sum' => $this->summary($salesman, $request->from, $request->to),
            'sales' => Document::query()->with('customer')->where('salesman_id', $salesman->id)->where('type', 'invoice')->where('status', 'final')
                ->when($request->from, fn ($q, $v) => $q->whereDate('date', '>=', $v))->when($request->to, fn ($q, $v) => $q->whereDate('date', '<=', $v))
                ->withSum('items', 'seller_points')->latest('date')->paginate(25)->withQueryString(),
            'payments' => $salesman->payments()->latest('date')->get(),
            'methods' => self::methods(),
        ]);
    }

    public function pay(Request $request, Salesman $salesman)
    {
        $this->need('sales.salesmen');
        $data = $request->validate([
            'kind' => ['required', 'in:payment,advance'], 'date' => ['required', 'date'], 'amount' => ['required', 'numeric', 'gt:0'],
            'method' => ['required', 'string', 'max:50'], 'note' => ['nullable', 'string', 'max:500'],
        ]);
        $salesman->payments()->create($data + ['created_by' => auth()->id()]);

        return back()->with('success', ucfirst($data['kind']).' recorded.');
    }

    public function deletePayment(SalesmanPayment $payment)
    {
        $this->need('sales.salesmen');
        $payment->delete();

        return back()->with('success', 'Deleted.');
    }

    private function summary(Salesman $s, ?string $from = null, ?string $to = null): array
    {
        $docs = Document::query()->where('salesman_id', $s->id)->where('type', 'invoice')->where('status', 'final')
            ->when($from, fn ($q) => $q->whereDate('date', '>=', $from))->when($to, fn ($q) => $q->whereDate('date', '<=', $to));
        $sales = (float) (clone $docs)->sum('grand_total');
        $points = (float) DocumentItem::query()->whereIn('document_id', (clone $docs)->select('id'))->sum('seller_points');
        $commission = round($sales * $s->commission_rate / 100, 2);
        $paid = (float) $s->payments()->where('kind', 'payment')->sum('amount');
        $advance = (float) $s->payments()->where('kind', 'advance')->sum('amount');

        return [
            'invoices' => (clone $docs)->count(), 'sales' => $sales, 'points' => $points, 'commission' => $commission,
            'earned' => $points + $commission, 'paid' => $paid, 'advance' => $advance,
            'balance' => round($s->opening_balance + $points + $commission - $paid - $advance, 2),
        ];
    }

    private function data(Request $request): array
    {
        return $request->validate([
            'code' => ['nullable', 'string', 'max:30'], 'name' => ['required', 'string', 'max:150'],
            'mobile' => ['nullable', 'string', 'max:50'], 'email' => ['nullable', 'email', 'max:150'], 'address' => ['nullable', 'string', 'max:255'],
            'opening_balance' => ['nullable', 'numeric'], 'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]) + ['is_active' => $request->boolean('is_active', true), 'opening_balance' => (float) $request->input('opening_balance', 0), 'commission_rate' => (float) $request->input('commission_rate', 0)];
    }
}
