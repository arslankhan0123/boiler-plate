<?php

declare(strict_types=1);

namespace App\Http\Controllers\Crm;

use App\Models\Crm\Company;
use Illuminate\Http\Request;

/** Companies / accounts in the CRM. */
class CompanyController extends CrmController
{
    public function index(Request $request)
    {
        $this->need('crm.view', 'crm.manage_contacts');
        $companies = Company::query()->withCount('contacts')->with('owner')
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%$s%")->orWhere('email', 'like', "%$s%")->orWhere('phone', 'like', "%$s%")->orWhere('city', 'like', "%$s%")))
            ->when($request->status === 'deleted', fn ($q) => $q->onlyTrashed())
            ->orderBy('name')->paginate(25)->withQueryString();

        return view('crm.companies.index', ['companies' => $companies, 'canEdit' => $this->can('crm.manage_contacts')]);
    }

    public function create()
    {
        $this->need('crm.manage_contacts');

        return view('crm.companies.form', ['company' => new Company(['owner_id' => auth()->id()]), 'owners' => $this->owners()]);
    }

    public function edit(Company $company)
    {
        $this->need('crm.manage_contacts');

        return view('crm.companies.form', ['company' => $company, 'owners' => $this->owners()]);
    }

    public function store(Request $request)
    {
        $this->need('crm.manage_contacts');
        $c = Company::create($this->data($request) + ['created_by' => auth()->id()]);

        return $request->wantsJson()
            ? response()->json(['id' => $c->id, 'name' => $c->name])
            : redirect()->route('crm.companies.show', $c)->with('success', 'Company added.');
    }

    public function update(Request $request, Company $company)
    {
        $this->need('crm.manage_contacts');
        $company->update($this->data($request));

        return redirect()->route('crm.companies.show', $company)->with('success', 'Company saved.');
    }

    public function show(Company $company)
    {
        $this->need('crm.view', 'crm.manage_contacts');

        return view('crm.companies.show', [
            'company' => $company->load('owner', 'contacts', 'opportunities'),
            'canEdit' => $this->can('crm.manage_contacts'),
        ]);
    }

    public function destroy(Company $company)
    {
        $this->need('crm.manage_contacts');
        $company->delete();

        return redirect()->route('crm.companies.index')->with('success', 'Company deleted.');
    }

    public function restore(int $id)
    {
        $this->need('crm.manage_contacts');
        Company::onlyTrashed()->findOrFail($id)->restore();

        return back()->with('success', 'Company restored.');
    }

    private function data(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'industry' => ['nullable', 'string', 'max:100'],
            'website' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:150'],
            'city' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'owner_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
