<?php

declare(strict_types=1);

namespace Modules\Setup\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Setup\Models\Country;
use Modules\Setup\Models\Department;
use Modules\Setup\Models\Designation;
use Modules\Setup\Models\State;

/**
 * Create a single tenant-defined employee. Requires a parent department +
 * designation. Optional `documents` array is replace-synced into employee_documents
 * by the service (metadata only).
 */
class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['sometimes', 'nullable', 'string', 'max:255'],
            'department_id' => ['required', 'integer', Rule::exists(Department::class, 'id')->whereNull('deleted_at')],
            'designation_id' => ['required', 'integer', Rule::exists(Designation::class, 'id')->whereNull('deleted_at')],

            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:32'],
            'dob' => ['sometimes', 'nullable', 'date'],
            'gender' => ['sometimes', 'nullable', Rule::in(['male', 'female', 'others'])],
            'marital_status' => ['sometimes', 'nullable', Rule::in(['married', 'unmarried', 'divorced'])],
            'blood_group' => ['sometimes', 'nullable', Rule::in(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])],
            'religion' => ['sometimes', 'nullable', 'string', 'max:255'],

            'country_id' => ['sometimes', 'nullable', 'integer', Rule::exists(Country::class, 'id')->whereNull('deleted_at')],
            'state_id' => ['sometimes', 'nullable', 'integer', Rule::exists(State::class, 'id')->whereNull('deleted_at')],
            'street' => ['sometimes', 'nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'nullable', 'string', 'max:255'],
            'zip' => ['sometimes', 'nullable', 'string', 'max:32'],

            'national_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'iqama_no' => ['sometimes', 'nullable', 'string', 'max:255'],
            'iqama_no_expiry_date' => ['sometimes', 'nullable', 'date'],
            'passport' => ['sometimes', 'nullable', 'string', 'max:255'],
            'passport_expiry_date' => ['sometimes', 'nullable', 'date'],
            'driving_license_no' => ['sometimes', 'nullable', 'string', 'max:255'],
            'driving_license_expiry_date' => ['sometimes', 'nullable', 'date'],
            'tuv_no' => ['sometimes', 'nullable', 'string', 'max:255'],
            'tuv_no_expiry_date' => ['sometimes', 'nullable', 'date'],

            'join_date' => ['required', 'date'],
            'type' => ['sometimes', 'nullable', 'string', 'max:255'],
            'employment_type' => ['sometimes', 'nullable', 'string', 'max:255'],
            'duty_type' => ['sometimes', 'nullable', 'string', 'max:255'],

            'bank_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'bank_branch_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'bank_account_no' => ['sometimes', 'nullable', 'string', 'max:255'],
            'iban_num' => ['sometimes', 'nullable', 'string', 'max:255'],
            'company_name' => ['sometimes', 'nullable', 'string', 'max:255'],

            'basic_salary' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'transport_allowance' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'gross_salary' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'hourly_rate' => ['sometimes', 'nullable', 'numeric', 'min:0'],

            'data' => ['sometimes', 'nullable', 'array'],
            'is_active' => ['sometimes', 'boolean'],
            'is_default' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],

            'documents' => ['sometimes', 'array'],
            'documents.*.name' => ['required_with:documents', 'string', 'max:255'],
            'documents.*.file' => ['sometimes', 'nullable', 'string', 'max:255'],
            'documents.*.expiry_date' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
