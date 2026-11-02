<?php

declare(strict_types=1);

namespace App\Support\Accounting;

use App\Models\Accounting\Account;
use Illuminate\Support\Facades\DB;

/** Balances and financial statements computed from POSTED journal lines + opening balances. */
class Ledger
{
    /** Posted debit/credit movement per account id, optionally within a date range. */
    public static function movements(?string $from = null, ?string $to = null): array
    {
        $q = DB::connection('tenant')->table('acc_journal_lines as l')
            ->join('acc_journals as j', 'j.id', '=', 'l.journal_id')
            ->where('j.status', 'posted');
        if ($from) {
            $q->whereDate('j.date', '>=', $from);
        }
        if ($to) {
            $q->whereDate('j.date', '<=', $to);
        }

        return $q->groupBy('l.account_id')
            ->selectRaw('l.account_id, SUM(l.debit) d, SUM(l.credit) c')
            ->get()->keyBy('account_id')->map(fn ($r) => ['d' => (float) $r->d, 'c' => (float) $r->c])->all();
    }

    /** Running balance of one account on its normal side (opening + posted movement up to $to). */
    public static function balance(Account $a, ?string $to = null): float
    {
        $m = self::movements(null, $to)[$a->id] ?? ['d' => 0, 'c' => 0];

        return round($a->opening_balance + $a->sign() * ($m['d'] - $m['c']), 2);
    }

    /** Trial balance rows: [account, debit, credit] with totals. */
    public static function trialBalance(?string $to = null): array
    {
        $mv = self::movements(null, $to);
        $rows = [];
        $td = $tc = 0.0;
        foreach (Account::query()->where('is_group', false)->orderBy('code')->get() as $a) {
            $m = $mv[$a->id] ?? ['d' => 0, 'c' => 0];
            // net debit position (opening expressed as debit) + posted debit movement
            $rawDebit = ($a->isDebitNormal() ? $a->opening_balance : -$a->opening_balance) + ($m['d'] - $m['c']);
            $rawDebit = round($rawDebit, 2);
            if (abs($rawDebit) < 0.005) {
                continue;
            }
            $debit = $rawDebit > 0 ? $rawDebit : 0.0;
            $credit = $rawDebit < 0 ? -$rawDebit : 0.0;
            $rows[] = ['account' => $a, 'debit' => $debit, 'credit' => $credit];
            $td += $debit;
            $tc += $credit;
        }

        return ['rows' => $rows, 'total_debit' => round($td, 2), 'total_credit' => round($tc, 2), 'balanced' => abs($td - $tc) < 0.01];
    }

    /** Profit & loss for a date range. */
    public static function profitLoss(?string $from = null, ?string $to = null): array
    {
        $mv = self::movements($from, $to);
        $income = $expense = [];
        $ti = $te = 0.0;
        foreach (Account::query()->where('is_group', false)->whereIn('type', ['income', 'expense'])->orderBy('code')->get() as $a) {
            $m = $mv[$a->id] ?? ['d' => 0, 'c' => 0];
            $amt = round($a->sign() * ($m['d'] - $m['c']), 2); // normal-side amount
            if (abs($amt) < 0.005) {
                continue;
            }
            if ($a->type === 'income') {
                $income[] = ['account' => $a, 'amount' => $amt];
                $ti += $amt;
            } else {
                $expense[] = ['account' => $a, 'amount' => $amt];
                $te += $amt;
            }
        }

        return ['income' => $income, 'expense' => $expense, 'total_income' => round($ti, 2), 'total_expense' => round($te, 2), 'net' => round($ti - $te, 2)];
    }

    /** Balance sheet as of a date. Equity includes retained earnings (net P&L to date). */
    public static function balanceSheet(?string $to = null): array
    {
        $mv = self::movements(null, $to);
        $buckets = ['asset' => [], 'liability' => [], 'equity' => []];
        $totals = ['asset' => 0.0, 'liability' => 0.0, 'equity' => 0.0];
        $retained = 0.0;
        foreach (Account::query()->where('is_group', false)->orderBy('code')->get() as $a) {
            $m = $mv[$a->id] ?? ['d' => 0, 'c' => 0];
            $amt = round($a->opening_balance + $a->sign() * ($m['d'] - $m['c']), 2);
            if (in_array($a->type, ['income', 'expense'], true)) {
                $retained += $a->type === 'income' ? $amt : -$amt;

                continue;
            }
            if (abs($amt) < 0.005) {
                continue;
            }
            $buckets[$a->type][] = ['account' => $a, 'amount' => $amt];
            $totals[$a->type] += $amt;
        }
        $retained = round($retained, 2);
        $totals['equity'] += $retained;

        return [
            'assets' => $buckets['asset'], 'liabilities' => $buckets['liability'], 'equity' => $buckets['equity'],
            'retained' => $retained,
            'total_assets' => round($totals['asset'], 2),
            'total_liabilities' => round($totals['liability'], 2),
            'total_equity' => round($totals['equity'], 2),
            'balanced' => abs($totals['asset'] - ($totals['liability'] + $totals['equity'])) < 0.01,
        ];
    }

    /** Party balances for AR (receivable) or AP (payable) from posted lines on control accounts. */
    public static function partyBalances(string $controlType): array
    {
        $accountIds = Account::query()->where('control_type', $controlType)->pluck('id');
        if ($accountIds->isEmpty()) {
            return ['rows' => [], 'total' => 0.0];
        }
        $sign = $controlType === 'receivable' ? 1 : -1; // AR debit-normal, AP credit-normal
        $rows = DB::connection('tenant')->table('acc_journal_lines as l')
            ->join('acc_journals as j', 'j.id', '=', 'l.journal_id')
            ->where('j.status', 'posted')->whereIn('l.account_id', $accountIds)
            ->groupBy('l.party_type', 'l.party_id')
            ->selectRaw('l.party_type, l.party_id, SUM(l.debit) d, SUM(l.credit) c')
            ->get()->map(fn ($r) => [
                'party_type' => $r->party_type, 'party_id' => $r->party_id,
                'balance' => round($sign * ((float) $r->d - (float) $r->c), 2),
            ])->filter(fn ($r) => abs($r['balance']) > 0.005)->values()->all();

        return ['rows' => $rows, 'total' => round(array_sum(array_column($rows, 'balance')), 2)];
    }
}
