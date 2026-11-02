<?php

declare(strict_types=1);

namespace App\Http\Controllers\Accounting;

use App\Models\Accounting\Account;
use App\Models\Accounting\Journal;
use App\Support\Accounting\Posting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Quick expense & receipt vouchers — the everyday "money out / money in" workflow,
 * without hand-building a journal. Each voucher posts a balanced double-entry.
 *   Expense : Dr expense account   / Cr bank-or-cash
 *   Receipt : Dr bank-or-cash      / Cr income account
 */
class VoucherController extends AccountingController
{
    public function index(Request $request)
    {
        $this->need('accounting.view', 'accounting.post_journal');
        $vouchers = Journal::query()->whereIn('source', ['expense', 'receipt'])->withSum('lines as total', 'debit')
            ->when($request->type, fn ($q, $v) => $q->where('source', $v))
            ->latest('date')->latest('id')->paginate(25)->withQueryString();

        return view('accounting.vouchers.index', [
            'vouchers' => $vouchers,
            'canPost' => $this->can('accounting.post_journal'),
        ]);
    }

    public function create(Request $request)
    {
        $this->need('accounting.post_journal');
        $type = $request->type === 'receipt' ? 'receipt' : 'expense';

        return view('accounting.vouchers.form', [
            'type' => $type,
            'banks' => $this->accountOptions(fn ($q) => $q->where('is_bank', true)),
            'categories' => $this->accountOptions(fn ($q) => $q->where('type', $type === 'expense' ? 'expense' : 'income')),
        ]);
    }

    public function store(Request $request)
    {
        $this->need('accounting.post_journal');
        $data = $request->validate([
            'type' => ['required', 'in:expense,receipt'],
            'date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'category_id' => ['required', 'integer', 'exists:acc_accounts,id'],   // expense or income account
            'paid_through_id' => ['required', 'integer', 'exists:acc_accounts,id'], // bank/cash
            'party_type' => ['nullable', 'in:customer,supplier'],
            'party_id' => ['nullable', 'integer'],
            'memo' => ['nullable', 'string', 'max:255'],
        ]);
        $amount = round((float) $data['amount'], 2);
        $prefix = $data['type'] === 'expense' ? 'EXP' : 'RCT';

        $journal = DB::connection('tenant')->transaction(function () use ($data, $amount, $prefix) {
            $j = Journal::create([
                'reference' => $prefix.'-'.str_pad((string) ((int) (Journal::where('source', $data['type'])->max('id') ?? 0) + 1), 4, '0', STR_PAD_LEFT),
                'date' => $data['date'], 'memo' => $data['memo'] ?? null, 'status' => 'draft',
                'source' => $data['type'], 'created_by' => auth()->id(),
            ]);
            if ($data['type'] === 'expense') {
                $j->lines()->create(['account_id' => $data['category_id'], 'debit' => $amount, 'credit' => 0, 'memo' => $data['memo'] ?? null, 'party_type' => $data['party_type'] ?? null, 'party_id' => $data['party_id'] ?? null]);
                $j->lines()->create(['account_id' => $data['paid_through_id'], 'debit' => 0, 'credit' => $amount]);
            } else {
                $j->lines()->create(['account_id' => $data['paid_through_id'], 'debit' => $amount, 'credit' => 0]);
                $j->lines()->create(['account_id' => $data['category_id'], 'debit' => 0, 'credit' => $amount, 'memo' => $data['memo'] ?? null, 'party_type' => $data['party_type'] ?? null, 'party_id' => $data['party_id'] ?? null]);
            }
            Posting::post($j->fresh('lines'));

            return $j;
        });

        return redirect()->route('accounting.journals.show', $journal)
            ->with('success', ucfirst($data['type']).' voucher posted.');
    }

    private function accountOptions(callable $scope): array
    {
        return Account::query()->where('is_group', false)->where('is_active', true)->where($scope)
            ->orderBy('code')->get()->mapWithKeys(fn ($a) => [$a->id => $a->code.' — '.$a->name])->all();
    }
}
