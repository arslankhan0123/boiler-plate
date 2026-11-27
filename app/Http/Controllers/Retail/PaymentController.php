<?php

declare(strict_types=1);

namespace App\Http\Controllers\Retail;

use App\Models\Retail\Payment;
use App\Models\User;
use App\Support\Retail\DocumentService;
use Illuminate\Http\Request;

/** Payment lists (received / paid), cheque tracking and payment receipts. */
class PaymentController extends RetailController
{
    public function index(Request $request, string $side)
    {
        $sale = $side === 'sale';
        $this->need($sale ? 'sales.payments' : 'purchase.payments');
        $base = Payment::query()->where('party_type', $sale ? 'customer' : 'supplier')
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('code', 'like', "%$s%")->orWhere('cheque_no', $s)->orWhereHas('document', fn ($d) => $d->where('number', 'like', "%$s%"))))
            ->when($request->method, fn ($q, $v) => $q->where('method', $v))
            ->when($request->kind, fn ($q, $v) => $q->where('kind', $v))
            ->when($request->cheques, fn ($q) => $q->whereNotNull('cheque_no'))
            ->when($request->cheque_status, fn ($q, $v) => $q->where('cheque_status', $v))
            ->when($request->from, fn ($q, $v) => $q->whereDate('date', '>=', $v))
            ->when($request->to, fn ($q, $v) => $q->whereDate('date', '<=', $v));

        $rows = (clone $base)->with('document', 'party')->latest('date')->latest('id')->paginate(30)->withQueryString();

        return view('retail.payments.index', [
            'side' => $side, 'rows' => $rows,
            'totals' => (clone $base)->selectRaw("SUM(CASE WHEN direction = 'in' THEN amount ELSE 0 END) i, SUM(CASE WHEN direction = 'out' THEN amount ELSE 0 END) o")->first(),
            'methods' => Payment::query()->distinct()->orderBy('method')->pluck('method'),
            'users' => User::query()->whereIn('id', $rows->pluck('created_by')->filter())->pluck('name', 'id'),
        ]);
    }

    /** Cheque cleared / bounced / pending. A bounced cheque no longer counts as paid. */
    public function cheque(Request $request, Payment $payment)
    {
        $this->need($payment->party_type === 'customer' ? 'sales.payments' : 'purchase.payments');
        $status = $request->validate(['cheque_status' => ['required', 'in:pending,cleared,bounced']])['cheque_status'];
        $payment->update(['cheque_status' => $status]);
        if ($payment->document) {
            DocumentService::refreshPaid($payment->document);
        }

        return back()->with('success', 'Cheque marked '.$status.'.');
    }

    public function receipt(Payment $payment)
    {
        $this->need($payment->party_type === 'customer' ? 'sales.payments' : 'purchase.payments', 'sales.view', 'purchase.view');

        return view('retail.payments.receipt', ['p' => $payment->load('document', 'party'), 'by' => User::query()->whereKey($payment->created_by)->value('name')]);
    }

    public function destroy(Payment $payment)
    {
        $this->need($payment->party_type === 'customer' ? 'sales.payments' : 'purchase.payments');
        $doc = $payment->document;
        $payment->delete();
        if ($doc) {
            DocumentService::refreshPaid($doc);
        }

        return back()->with('success', 'Payment deleted.');
    }
}
