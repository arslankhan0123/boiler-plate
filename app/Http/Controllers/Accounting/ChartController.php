<?php

declare(strict_types=1);

namespace App\Http\Controllers\Accounting;

use App\Models\Accounting\Account;
use App\Support\Accounting\DefaultChart;
use App\Support\Accounting\Ledger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Chart of accounts. */
class ChartController extends AccountingController
{
    public function index(Request $request)
    {
        $this->need('accounting.view', 'accounting.manage_accounts');
        $accounts = Account::query()
            ->when($request->type, fn ($q, $v) => $q->where('type', $v))
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%$s%")->orWhere('code', 'like', "%$s%")))
            ->orderBy('code')->get();
        $balances = [];
        foreach ($accounts as $a) {
            $balances[$a->id] = Ledger::balance($a);
        }

        return view('accounting.chart.index', [
            'accounts' => $accounts, 'balances' => $balances, 'types' => Account::TYPES,
            'canEdit' => $this->can('accounting.manage_accounts'),
        ]);
    }

    public function create()
    {
        $this->need('accounting.manage_accounts');

        return view('accounting.chart.form', ['account' => new Account(['type' => 'asset', 'is_active' => true]), 'parents' => $this->parents()]);
    }

    public function edit(Account $account)
    {
        $this->need('accounting.manage_accounts');

        return view('accounting.chart.form', ['account' => $account, 'parents' => $this->parents($account->id)]);
    }

    public function store(Request $request)
    {
        $this->need('accounting.manage_accounts');
        Account::create($this->data($request) + ['created_by' => auth()->id()]);

        return redirect()->route('accounting.chart.index')->with('success', 'Account added.');
    }

    public function update(Request $request, Account $account)
    {
        $this->need('accounting.manage_accounts');
        $account->update($this->data($request, $account->id));

        return redirect()->route('accounting.chart.index')->with('success', 'Account saved.');
    }

    public function destroy(Account $account)
    {
        $this->need('accounting.manage_accounts');
        if ($account->lines()->exists()) {
            return back()->with('error', 'This account has journal entries and cannot be deleted. Deactivate it instead.');
        }
        $account->delete();

        return back()->with('success', 'Account deleted.');
    }

    /** Load a standard starter chart. */
    public function seed()
    {
        $this->need('accounting.manage_accounts');
        $n = DefaultChart::seed(auth()->id());

        return back()->with('success', $n > 0 ? "Added $n standard accounts." : 'Standard accounts already present.');
    }

    private function parents(?int $exclude = null): array
    {
        return Account::query()->when($exclude, fn ($q, $id) => $q->whereKeyNot($id))->orderBy('code')->get()
            ->mapWithKeys(fn ($a) => [$a->id => $a->code.' — '.$a->name])->all();
    }

    private function data(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:30', Rule::unique('acc_accounts', 'code')->ignore($id)],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(array_keys(Account::TYPES))],
            'parent_id' => ['nullable', 'integer', 'exists:acc_accounts,id'],
            'control_type' => ['nullable', 'in:receivable,payable'],
            'opening_balance' => ['nullable', 'numeric'],
            'description' => ['nullable', 'string', 'max:255'],
        ]) + [
            'is_group' => $request->boolean('is_group'),
            'is_bank' => $request->boolean('is_bank'),
            'is_active' => $request->boolean('is_active', true),
            'opening_balance' => (float) $request->input('opening_balance', 0),
        ];
    }
}
