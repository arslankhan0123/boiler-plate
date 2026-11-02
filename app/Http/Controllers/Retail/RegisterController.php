<?php

declare(strict_types=1);

namespace App\Http\Controllers\Retail;

use App\Models\Inventory\Warehouse;
use App\Models\Retail\Register;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** POS cash drawer: open a shift with a float, record pay-ins/outs, close with a counted amount. */
class RegisterController extends RetailController
{
    public function index(Request $request)
    {
        $this->need('pos.open', 'pos.close_register');
        $rows = Register::query()->with('warehouse')
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->from, fn ($q, $v) => $q->whereDate('opened_at', '>=', $v))
            ->when($request->to, fn ($q, $v) => $q->whereDate('opened_at', '<=', $v))
            ->latest('id')->paginate(25)->withQueryString();

        return view('retail.registers.index', [
            'rows' => $rows,
            'users' => User::query()->whereIn('id', $rows->pluck('opened_by')->merge($rows->pluck('closed_by'))->filter()->unique())->pluck('name', 'id'),
            'current' => Register::openFor(auth()->id()),
            'canOpen' => $this->can('pos.open'),
        ]);
    }

    public function create()
    {
        $this->need('pos.open');
        if ($open = Register::openFor(auth()->id())) {
            return redirect()->route('pos.registers.show', $open)->with('error', 'You already have an open register. Close it first.');
        }

        return view('retail.registers.open', ['warehouses' => Warehouse::options(), 'default' => Warehouse::default()->id]);
    }

    public function open(Request $request)
    {
        $this->need('pos.open');
        if (Register::openFor(auth()->id())) {
            return back()->with('error', 'You already have an open register.');
        }
        $data = $request->validate([
            'warehouse_id' => ['required', 'integer', 'exists:inv_warehouses,id'],
            'opening_cash' => ['required', 'numeric', 'min:0'],
            'open_note' => ['nullable', 'string', 'max:500'],
        ]);
        $reg = Register::create($data + ['opened_by' => auth()->id(), 'opened_at' => now(), 'status' => 'open']);

        return redirect()->route('pos')->with('success', 'Register opened with '.number_format((float) $data['opening_cash'], 2).' float.');
    }

    public function show(Register $register)
    {
        $this->need('pos.open', 'pos.close_register');

        return view('retail.registers.show', [
            'reg' => $register->load('warehouse', 'movements', 'openedBy'),
            'live' => $register->status === 'open' ? $register->liveExpected() : null,
            'openedBy' => User::query()->whereKey($register->opened_by)->value('name'),
            'closedBy' => User::query()->whereKey($register->closed_by)->value('name'),
            'canClose' => $register->status === 'open'
                && (($register->opened_by === auth()->id() && $this->can('pos.open')) || $this->can('pos.close_register')),
            'isOwner' => $register->opened_by === auth()->id(),
        ]);
    }

    /** Manual cash pay-in / pay-out during the shift. */
    public function movement(Request $request, Register $register)
    {
        $this->need('pos.open');
        abort_unless($register->status === 'open', 422);
        $data = $request->validate([
            'direction' => ['required', 'in:in,out'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);
        $register->movements()->create($data + ['created_by' => auth()->id()]);

        return back()->with('success', ($data['direction'] === 'in' ? 'Pay-in' : 'Pay-out').' recorded.');
    }

    /** Close the shift: counts come in, over/short is computed and frozen. */
    public function close(Request $request, Register $register)
    {
        // Hybrid: the opener may close their own drawer (needs pos.open);
        // anyone with pos.close_register may close any drawer (manager override).
        $isOwner = $register->opened_by === auth()->id();
        abort_unless(
            ($isOwner && $this->can('pos.open')) || $this->can('pos.close_register'),
            403,
            'You can only close your own register. Ask a manager to close someone else\'s drawer.'
        );
        abort_unless($register->status === 'open', 422);
        $data = $request->validate([
            'counted_cash' => ['required', 'numeric', 'min:0'],
            'close_note' => ['nullable', 'string', 'max:500'],
        ]);
        DB::transaction(function () use ($register, $data) {
            $live = $register->liveExpected();
            $register->update([
                'status' => 'closed', 'closed_by' => auth()->id(), 'closed_at' => now(),
                'counted_cash' => $data['counted_cash'], 'expected_cash' => $live['expected'],
                'cash_sales' => $live['cash_sales'], 'cash_refunds' => $live['cash_refunds'], 'other_sales' => $live['other_sales'],
                'cash_in' => $live['cash_in'], 'cash_out' => $live['cash_out'],
                'over_short' => round($data['counted_cash'] - $live['expected'], 2), 'close_note' => $data['close_note'] ?? null,
            ]);
        });

        return redirect()->route('pos.registers.show', $register)->with('success', 'Register closed.');
    }

    public function print(Register $register)
    {
        $this->need('pos.open', 'pos.close_register');

        return view('retail.registers.report', [
            'reg' => $register->load('warehouse', 'movements'),
            'live' => $register->status === 'open' ? $register->liveExpected() : null,
            'openedBy' => User::query()->whereKey($register->opened_by)->value('name'),
            'closedBy' => User::query()->whereKey($register->closed_by)->value('name'),
        ]);
    }
}
