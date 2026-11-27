<?php

declare(strict_types=1);

namespace App\Http\Controllers\Crm;

use App\Models\Crm\Activity;
use App\Models\Crm\Company;
use App\Models\Crm\Contact;
use App\Models\Crm\Lead;
use App\Models\Crm\Opportunity;
use Illuminate\Http\Request;

/** Read-only customer history: a timeline of leads, opportunities and activities per contact/company. */
class HistoryController extends CrmController
{
    public function index(Request $request)
    {
        $this->need('crm.view');
        $contacts = Contact::query()->with('company')
            ->when($request->q, fn ($q, $s) => $q->where('name', 'like', "%$s%")->orWhere('email', 'like', "%$s%"))
            ->orderBy('name')->paginate(25)->withQueryString();

        return view('crm.history.index', ['contacts' => $contacts]);
    }

    public function show(Contact $contact)
    {
        $this->need('crm.view');
        $acts = Activity::query()->where('related_type', 'contact')->where('related_id', $contact->id)->get();
        $opps = Opportunity::query()->where('contact_id', $contact->id)->get();
        $leads = Lead::query()->where('converted_contact_id', $contact->id)->get();

        // Merge into one timeline, newest first.
        $timeline = collect();
        foreach ($acts as $a) {
            $timeline->push(['when' => $a->created_at, 'icon' => $a->icon(), 'tone' => $a->isOverdue() ? 'danger' : 'primary', 'title' => $a->typeLabel().': '.$a->subject, 'meta' => $a->done ? 'Done' : ($a->due_at?->format('d M Y') ?? 'No due date')]);
        }
        foreach ($opps as $o) {
            $timeline->push(['when' => $o->created_at, 'icon' => 'graph-up-arrow', 'tone' => $o->stageTone(), 'title' => 'Opportunity: '.$o->title, 'meta' => $o->stageLabel().' · '.number_format($o->amount, 2)]);
        }
        foreach ($leads as $l) {
            $timeline->push(['when' => $l->created_at, 'icon' => 'person-plus', 'tone' => 'info', 'title' => 'Lead: '.$l->name, 'meta' => $l->statusLabel()]);
        }

        return view('crm.history.show', [
            'contact' => $contact->load('company', 'owner'),
            'timeline' => $timeline->sortByDesc('when')->values(),
            'company' => $contact->company,
        ]);
    }
}
