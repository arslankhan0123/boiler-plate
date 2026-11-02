<?php

declare(strict_types=1);

namespace App\Http\Controllers\Crm;

use App\Models\Crm\Company;
use App\Models\Crm\Contact;
use App\Models\Crm\Opportunity;
use Illuminate\Http\Request;

/** Sales pipeline: opportunities moving through stages to won/lost. */
class OpportunityController extends CrmController
{
    public function index(Request $request)
    {
        $this->need('crm.view', 'crm.manage_pipeline');
        $view = $request->view === 'list' ? 'list' : 'board';
        $base = Opportunity::query()->with('company', 'contact', 'owner')
            ->when($request->owner_id, fn ($q, $v) => $q->where('owner_id', $v));

        if ($view === 'list') {
            $rows = (clone $base)->latest('id')->paginate(30)->withQueryString();

            return view('crm.opportunities.list', ['rows' => $rows, 'stages' => Opportunity::STAGES, 'owners' => $this->owners(), 'canEdit' => $this->can('crm.manage_pipeline')]);
        }

        $cards = (clone $base)->orderByDesc('amount')->get()->groupBy('stage');
        $totals = [];
        foreach (array_keys(Opportunity::STAGES) as $st) {
            $col = $cards[$st] ?? collect();
            $totals[$st] = ['count' => $col->count(), 'amount' => $col->sum('amount')];
        }

        return view('crm.opportunities.board', [
            'cards' => $cards, 'stages' => Opportunity::STAGES, 'totals' => $totals,
            'owners' => $this->owners(), 'canEdit' => $this->can('crm.manage_pipeline'),
            'openValue' => $base->clone()->whereIn('stage', Opportunity::OPEN_STAGES)->sum('amount'),
        ]);
    }

    public function create()
    {
        $this->need('crm.manage_pipeline');

        return view('crm.opportunities.form', $this->formData(new Opportunity(['stage' => 'qualification', 'probability' => 20, 'owner_id' => auth()->id()])));
    }

    public function edit(Opportunity $opportunity)
    {
        $this->need('crm.manage_pipeline');

        return view('crm.opportunities.form', $this->formData($opportunity));
    }

    public function store(Request $request)
    {
        $this->need('crm.manage_pipeline');
        $opp = Opportunity::create($this->data($request) + ['created_by' => auth()->id()]);

        return redirect()->route('crm.opportunities.show', $opp)->with('success', 'Opportunity added.');
    }

    public function update(Request $request, Opportunity $opportunity)
    {
        $this->need('crm.manage_pipeline');
        $opportunity->update($this->data($request));

        return redirect()->route('crm.opportunities.show', $opportunity)->with('success', 'Opportunity saved.');
    }

    public function show(Opportunity $opportunity)
    {
        $this->need('crm.view', 'crm.manage_pipeline');

        return view('crm.opportunities.show', [
            'opp' => $opportunity->load('company', 'contact', 'owner'),
            'activities' => \App\Models\Crm\Activity::query()->where('related_type', 'opportunity')->where('related_id', $opportunity->id)->latest('id')->get(),
            'canEdit' => $this->can('crm.manage_pipeline'),
        ]);
    }

    /** Move an opportunity to another stage (board drag / dropdown). */
    public function stage(Request $request, Opportunity $opportunity)
    {
        $this->need('crm.manage_pipeline');
        $data = $request->validate([
            'stage' => ['required', 'in:qualification,proposal,negotiation,won,lost'],
            'lost_reason' => ['nullable', 'string', 'max:255'],
        ]);
        $attrs = ['stage' => $data['stage']];
        if (in_array($data['stage'], ['won', 'lost'], true)) {
            $attrs['closed_at'] = now();
            $attrs['probability'] = $data['stage'] === 'won' ? 100 : 0;
            $attrs['lost_reason'] = $data['stage'] === 'lost' ? ($data['lost_reason'] ?? null) : null;
        } else {
            $attrs['closed_at'] = null;
        }
        $opportunity->update($attrs);

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'stage' => $opportunity->stage]);
        }

        return back()->with('success', 'Opportunity moved to '.$opportunity->stageLabel().'.');
    }

    public function destroy(Opportunity $opportunity)
    {
        $this->need('crm.manage_pipeline');
        $opportunity->delete();

        return redirect()->route('crm.opportunities.index')->with('success', 'Opportunity deleted.');
    }

    private function formData(Opportunity $opp): array
    {
        return [
            'opp' => $opp,
            'companies' => Company::orderBy('name')->pluck('name', 'id'),
            'contacts' => Contact::orderBy('name')->pluck('name', 'id'),
            'stages' => Opportunity::STAGES,
            'owners' => $this->owners(),
        ];
    }

    private function data(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'company_id' => ['nullable', 'integer', 'exists:crm_companies,id'],
            'contact_id' => ['nullable', 'integer', 'exists:crm_contacts,id'],
            'stage' => ['required', 'in:qualification,proposal,negotiation,won,lost'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'probability' => ['nullable', 'integer', 'min:0', 'max:100'],
            'close_date' => ['nullable', 'date'],
            'source' => ['nullable', 'string', 'max:60'],
            'owner_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]) + ['amount' => (float) $request->input('amount', 0), 'probability' => (int) $request->input('probability', 10)];
    }
}
