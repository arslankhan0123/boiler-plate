<?php

declare(strict_types=1);

namespace App\Http\Controllers\Crm;

use App\Models\Crm\Contact;
use App\Models\Crm\Lead;
use App\Models\Crm\Opportunity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Sales leads: capture, qualify and convert into a contact + opportunity. */
class LeadController extends CrmController
{
    public function index(Request $request)
    {
        $this->need('crm.view', 'crm.manage_leads');
        $leads = Lead::query()->with('owner')
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%$s%")->orWhere('company', 'like', "%$s%")->orWhere('email', 'like', "%$s%")->orWhere('phone', 'like', "%$s%")))
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->status === 'deleted', fn ($q) => $q->onlyTrashed())
            ->latest('id')->paginate(25)->withQueryString();

        return view('crm.leads.index', [
            'leads' => $leads,
            'statuses' => Lead::STATUSES,
            'counts' => Lead::query()->selectRaw('status, COUNT(*) n')->groupBy('status')->pluck('n', 'status'),
            'canEdit' => $this->can('crm.manage_leads'),
        ]);
    }

    public function create()
    {
        $this->need('crm.manage_leads');

        return view('crm.leads.form', ['lead' => new Lead(['status' => 'new', 'owner_id' => auth()->id()]), 'owners' => $this->owners()]);
    }

    public function edit(Lead $lead)
    {
        $this->need('crm.manage_leads');

        return view('crm.leads.form', ['lead' => $lead, 'owners' => $this->owners()]);
    }

    public function store(Request $request)
    {
        $this->need('crm.manage_leads');
        $lead = Lead::create($this->data($request) + ['created_by' => auth()->id()]);

        return redirect()->route('crm.leads.show', $lead)->with('success', 'Lead added.');
    }

    public function update(Request $request, Lead $lead)
    {
        $this->need('crm.manage_leads');
        $lead->update($this->data($request));

        return redirect()->route('crm.leads.show', $lead)->with('success', 'Lead saved.');
    }

    public function show(Lead $lead)
    {
        $this->need('crm.view', 'crm.manage_leads');

        return view('crm.leads.show', [
            'lead' => $lead->load('owner'),
            'opportunity' => $lead->converted_opportunity_id ? Opportunity::find($lead->converted_opportunity_id) : null,
            'activities' => \App\Models\Crm\Activity::query()->where('related_type', 'lead')->where('related_id', $lead->id)->latest('id')->get(),
            'canEdit' => $this->can('crm.manage_leads'),
            'canPipeline' => $this->can('crm.manage_pipeline'),
        ]);
    }

    public function destroy(Lead $lead)
    {
        $this->need('crm.manage_leads');
        $lead->delete();

        return redirect()->route('crm.leads.index')->with('success', 'Lead deleted.');
    }

    public function restore(int $id)
    {
        $this->need('crm.manage_leads');
        Lead::onlyTrashed()->findOrFail($id)->restore();

        return back()->with('success', 'Lead restored.');
    }

    /** Qualify a lead into a contact + an open opportunity. Needs pipeline permission. */
    public function convert(Request $request, Lead $lead)
    {
        $this->need('crm.manage_pipeline');
        if ($lead->status === 'converted') {
            return back()->with('error', 'This lead is already converted.');
        }
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'close_date' => ['nullable', 'date'],
        ]);

        DB::transaction(function () use ($lead, $data) {
            $contact = Contact::create([
                'name' => $lead->name, 'email' => $lead->email, 'phone' => $lead->phone,
                'owner_id' => $lead->owner_id, 'created_by' => auth()->id(),
            ]);
            $opp = Opportunity::create([
                'title' => $data['title'], 'contact_id' => $contact->id, 'stage' => 'qualification',
                'amount' => $data['amount'] ?? $lead->value, 'probability' => 20, 'close_date' => $data['close_date'] ?? null,
                'source' => $lead->source, 'owner_id' => $lead->owner_id, 'created_by' => auth()->id(),
            ]);
            $lead->update(['status' => 'converted', 'converted_contact_id' => $contact->id, 'converted_opportunity_id' => $opp->id, 'converted_at' => now()]);
        });

        return redirect()->route('crm.leads.show', $lead)->with('success', 'Lead converted to a contact and an opportunity.');
    }

    private function data(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:40'],
            'source' => ['nullable', 'string', 'max:60'],
            'status' => ['required', 'in:new,contacted,qualified,unqualified,converted'],
            'value' => ['nullable', 'numeric', 'min:0'],
            'owner_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]) + ['value' => (float) $request->input('value', 0)];
    }
}
