<?php

declare(strict_types=1);

namespace App\Support\Accounting;

use App\Models\Accounting\Account;

/** A standard starter chart of accounts. */
class DefaultChart
{
    /** [code, name, type, is_bank, control_type] */
    public const ACCOUNTS = [
        ['1000', 'Cash in Hand', 'asset', true, null],
        ['1010', 'Bank Account', 'asset', true, null],
        ['1200', 'Accounts Receivable', 'asset', false, 'receivable'],
        ['1300', 'Inventory', 'asset', false, null],
        ['1500', 'Fixed Assets', 'asset', false, null],
        ['2000', 'Accounts Payable', 'liability', false, 'payable'],
        ['2100', 'Tax Payable', 'liability', false, null],
        ['2200', 'Loans Payable', 'liability', false, null],
        ['3000', 'Owner Capital', 'equity', false, null],
        ['3100', 'Retained Earnings', 'equity', false, null],
        ['3200', 'Drawings', 'equity', false, null],
        ['4000', 'Sales Revenue', 'income', false, null],
        ['4100', 'Other Income', 'income', false, null],
        ['5000', 'Cost of Goods Sold', 'expense', false, null],
        ['6000', 'Salaries & Wages', 'expense', false, null],
        ['6100', 'Rent Expense', 'expense', false, null],
        ['6200', 'Utilities', 'expense', false, null],
        ['6300', 'Office Expense', 'expense', false, null],
        ['6400', 'Bank Charges', 'expense', false, null],
    ];

    public static function seed(?int $userId = null): int
    {
        $n = 0;
        foreach (self::ACCOUNTS as [$code, $name, $type, $bank, $control]) {
            $created = Account::firstOrCreate(
                ['code' => $code],
                ['name' => $name, 'type' => $type, 'is_bank' => $bank, 'control_type' => $control, 'is_active' => true, 'created_by' => $userId]
            );
            if ($created->wasRecentlyCreated) {
                $n++;
            }
        }

        return $n;
    }
}
