<?php

declare(strict_types=1);

namespace App\Support\Accounting;

use App\Models\Accounting\Journal;
use App\Models\Accounting\Period;
use Illuminate\Validation\ValidationException;

/** Posts and un-posts journal entries with double-entry + closed-period guards. */
class Posting
{
    public static function post(Journal $journal): void
    {
        $journal->loadMissing('lines');
        if (! $journal->isBalanced()) {
            throw ValidationException::withMessages(['lines' => 'Debits and credits must balance and be greater than zero before posting.']);
        }
        if (Period::isDateLocked($journal->date->toDateString())) {
            throw ValidationException::withMessages(['date' => 'This date falls in a closed accounting period.']);
        }
        $journal->update(['status' => 'posted', 'posted_at' => now(), 'posted_by' => auth()->id()]);
    }

    public static function unpost(Journal $journal): void
    {
        if (Period::isDateLocked($journal->date->toDateString())) {
            throw ValidationException::withMessages(['date' => 'This entry is in a closed period and cannot be reopened.']);
        }
        $journal->update(['status' => 'draft', 'posted_at' => null, 'posted_by' => null]);
    }

    /** Guard before editing/deleting: posted entries in a closed period are frozen. */
    public static function assertEditable(Journal $journal): void
    {
        if (Period::isDateLocked($journal->date->toDateString())) {
            throw ValidationException::withMessages(['date' => 'This entry is in a closed accounting period.']);
        }
    }
}
