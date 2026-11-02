<?php

declare(strict_types=1);

namespace App\Http\Controllers\Crm;

use App\Models\Crm\Company;
use App\Models\Crm\Contact;
use Illuminate\Http\Request;

/** People contacts, optionally linked to a company. */
class ContactController extends CrmController
{
    public function index(Request $request)
    {
        $this->need('crm.view', 'crm.manage_contacts');
        $contacts = Contact::query()->with('company', 'owner')
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%$s%")->orWhere('email', 'like', "%$s%")->orWhere('phone', 'like', "%$s%")->orWhere('title', 'like', "%$s%")))
            ->when($request->company_id, fn ($q, $v) => $q->where('company_id', $v))
            ->when($request->status === 'deleted', fn ($q) => $q->onlyTrashed())
            ->orderBy('name')->paginate(25)->withQueryString();

        return view('crm.contacts.index', [
            'contacts' => $contacts,
            'companies' => Company::orderBy('name')->pluck('name', 'id'),
            'canEdit' => $this->can('crm.manage_contacts'),
        ]);
    }

    public function create()
    {
        $this->need('crm.manage_contacts');

        return view('crm.contacts.form', ['contact' => new Contact(['owner_id' => auth()->id(), 'company_id' => request('company_id')]), 'companies' => Company::orderBy('name')->pluck('name', 'id'), 'owners' => $this->owners()]);
    }

    public function edit(Contact $contact)
    {
        $this->need('crm.manage_contacts');

        return view('crm.contacts.form', ['contact' => $contact, 'companies' => Company::orderBy('name')->pluck('name', 'id'), 'owners' => $this->owners()]);
    }

    public function store(Request $request)
    {
        $this->need('crm.manage_contacts');
        $c = Contact::create($this->data($request) + ['created_by' => auth()->id()]);

        return redirect()->route('crm.contacts.index')->with('success', 'Contact added.');
    }

    public function update(Request $request, Contact $contact)
    {
        $this->need('crm.manage_contacts');
        $contact->update($this->data($request));

        return redirect()->route('crm.contacts.index')->with('success', 'Contact saved.');
    }

    public function destroy(Contact $contact)
    {
        $this->need('crm.manage_contacts');
        $contact->delete();

        return back()->with('success', 'Contact deleted.');
    }

    public function restore(int $id)
    {
        $this->need('crm.manage_contacts');
        Contact::onlyTrashed()->findOrFail($id)->restore();

        return back()->with('success', 'Contact restored.');
    }

    private function data(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company_id' => ['nullable', 'integer', 'exists:crm_companies,id'],
            'title' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:40'],
            'owner_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
