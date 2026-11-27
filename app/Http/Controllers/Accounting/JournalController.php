<?php

declare(strict_types=1);

namespace App\Http\Controllers\Accounting;

use App\Models\Accounting\Account;
use App\Models\Accounting\Journal;
use App\Support\Accounting\Posting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Manual journal entries (double-entry). */
class JournalController extends AccountingController
{
    public function index(Request $request)
    {
        $this->need('accounting.view');
        $journals = Journal::query()->withCount('lines')
            ->withSum('lines as total', 'debit')
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('reference', 'like', "%$s%")->orWhere('memo', 'like', "%$s%")))
            ->latest('date')->latest('id')->paginate(25)->withQueryString();

        return view('accounting.journals.index', [
            'journals' => $journals,
            'canPost' => $this->can('accounting.post_journal'),
        ]);
    }

    public function create()
    {
        $this->need('accounting.post_journal');

        return view('accounting.journals.form', ['journal' => new Journal(['date' => today(), 'reference' => Journal::nextReference()]), 'accounts' => $this->accounts(), 'lines' => []]);
    }

    public function edit(Journal $journal)
    {
        $this->need('accounting.post_journal');
        Posting::assertEditable($journal);

        return view('accounting.journals.form', ['journal' => $journal, 'accounts' => $this->accounts(), 'lines' => $journal->lines()->get()->all()]);
    }

    public function store(Request $request)
    {
        $this->need('accounting.post_journal');
        $data = $this->validateData($request);
        $journal = DB::connection('tenant')->transaction(function () use ($data, $request) {
            $j = Journal::create([
                'reference' => ($data['reference'] ?? '') ?: Journal::nextReference(),
                'date' => $data['date'], 'memo' => $data['memo'] ?? null, 'status' => 'draft',
                'source' => 'manual', 'created_by' => auth()->id(),
            ]);
            $this->syncLines($j, $data['lines']);
            if ($request->input('action') === 'post') {
                Posting::post($j->fresh('lines'));
            }

            return $j;
        });

        return redirect()->route('accounting.journals.show', $journal)->with('success', 'Journal entry saved.');
    }

    public function update(Request $request, Journal $journal)
    {
        $this->need('accounting.post_journal');
        Posting::assertEditable($journal);
        $data = $this->validateData($request);
        DB::connection('tenant')->transaction(function () use ($journal, $data, $request) {
            $journal->update(['reference' => ($data['reference'] ?? '') ?: $journal->reference, 'date' => $data['date'], 'memo' => $data['memo'] ?? null]);
            $journal->lines()->delete();
            $this->syncLines($journal, $data['lines']);
            if ($request->input('action') === 'post' && ! $journal->isPosted()) {
                Posting::post($journal->fresh('lines'));
            }
        });

        return redirect()->route('accounting.journals.show', $journal)->with('success', 'Journal entry updated.');
    }

    public function show(Journal $journal)
    {
        $this->need('accounting.view');

        return view('accounting.journals.show', [
            'journal' => $journal->load('lines.account'),
            'canPost' => $this->can('accounting.post_journal'),
        ]);
    }

    public function post(Journal $journal)
    {
        $this->need('accounting.post_journal');
        Posting::post($journal->load('lines'));

        return back()->with('success', 'Journal posted.');
    }

    public function unpost(Journal $journal)
    {
        $this->need('accounting.post_journal');
        Posting::unpost($journal);

        return back()->with('success', 'Journal reopened as draft.');
    }

    public function destroy(Journal $journal)
    {
        $this->need('accounting.post_journal');
        Posting::assertEditable($journal);
        $journal->lines()->delete();
        $journal->delete();

        return redirect()->route('accounting.journals.index')->with('success', 'Journal deleted.');
    }

    private function accounts(): array
    {
        return Account::query()->where('is_group', false)->where('is_active', true)->orderBy('code')->get()
            ->mapWithKeys(fn ($a) => [$a->id => $a->code.' — '.$a->name])->all();
    }

    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'reference' => ['nullable', 'string', 'max:30'],
            'date' => ['required', 'date'],
            'memo' => ['nullable', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_id' => ['required', 'integer', 'exists:acc_accounts,id'],
            'lines.*.debit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.credit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.memo' => ['nullable', 'string', 'max:255'],
        ]);
        // keep only lines with an amount
        $data['lines'] = array_values(array_filter($data['lines'], fn ($l) => ((float) ($l['debit'] ?? 0)) > 0 || ((float) ($l['credit'] ?? 0)) > 0));
        $td = array_sum(array_map(fn ($l) => (float) ($l['debit'] ?? 0), $data['lines']));
        $tc = array_sum(array_map(fn ($l) => (float) ($l['credit'] ?? 0), $data['lines']));
        if (count($data['lines']) < 2 || abs($td - $tc) > 0.005 || $td <= 0) {
            throw \Illuminate\Validation\ValidationException::withMessages(['lines' => 'Debits must equal credits (and be greater than zero), with at least two lines.']);
        }

        return $data;
    }

    private function syncLines(Journal $journal, array $lines): void
    {
        foreach ($lines as $l) {
            $journal->lines()->create([
                'account_id' => $l['account_id'],
                'debit' => round((float) ($l['debit'] ?? 0), 2),
                'credit' => round((float) ($l['credit'] ?? 0), 2),
                'memo' => $l['memo'] ?? null,
                'party_type' => $l['party_type'] ?? null,
                'party_id' => $l['party_id'] ?? null,
            ]);
        }
    }
}
