<?php

declare(strict_types=1);

namespace App\Http\Controllers\Crm;

use App\Models\Crm\Campaign;
use Illuminate\Http\Request;

/** Marketing campaigns register. */
class CampaignController extends CrmController
{
    public function index(Request $request)
    {
        $this->need('crm.manage_campaigns');
        $campaigns = Campaign::query()
            ->when($request->q, fn ($q, $s) => $q->where('name', 'like', "%$s%"))
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->latest('id')->paginate(25)->withQueryString();

        return view('crm.campaigns.index', [
            'campaigns' => $campaigns,
            'statuses' => Campaign::STATUSES,
            'spend' => Campaign::sum('budget'),
            'canEdit' => $this->can('crm.manage_campaigns'),
        ]);
    }

    public function create()
    {
        $this->need('crm.manage_campaigns');

        return view('crm.campaigns.form', ['campaign' => new Campaign(['status' => 'planned', 'channel' => 'email'])]);
    }

    public function edit(Campaign $campaign)
    {
        $this->need('crm.manage_campaigns');

        return view('crm.campaigns.form', ['campaign' => $campaign]);
    }

    public function store(Request $request)
    {
        $this->need('crm.manage_campaigns');
        Campaign::create($this->data($request) + ['created_by' => auth()->id()]);

        return redirect()->route('crm.campaigns.index')->with('success', 'Campaign added.');
    }

    public function update(Request $request, Campaign $campaign)
    {
        $this->need('crm.manage_campaigns');
        $campaign->update($this->data($request));

        return redirect()->route('crm.campaigns.index')->with('success', 'Campaign saved.');
    }

    public function destroy(Campaign $campaign)
    {
        $this->need('crm.manage_campaigns');
        $campaign->delete();

        return back()->with('success', 'Campaign deleted.');
    }

    private function data(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'channel' => ['required', 'in:email,sms,social,event,other'],
            'status' => ['required', 'in:planned,active,completed,cancelled'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]) + ['budget' => (float) $request->input('budget', 0)];
    }
}
