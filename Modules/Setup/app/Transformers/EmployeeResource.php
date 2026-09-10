<?php

declare(strict_types=1);

namespace Modules\Setup\Transformers;

use App\Http\Resources\Concerns\FiltersVisibleColumns;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Setup\Models\Employee;
use Modules\Setup\Models\EmployeeDocument;

/**
 * @mixin Employee
 */
class EmployeeResource extends JsonResource
{
    use FiltersVisibleColumns;

    protected function visibilityResource(): string
    {
        return 'employees';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->filterVisibleColumns([
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'department_id' => $this->department_id,
            'department_name' => $this->department?->name,
            'designation_id' => $this->designation_id,
            'designation_name' => $this->designation?->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'dob' => $this->dob?->format('Y-m-d'),
            'gender' => $this->gender,
            'marital_status' => $this->marital_status,
            'blood_group' => $this->blood_group,
            'religion' => $this->religion,
            'country_id' => $this->country_id,
            'country_name' => $this->country?->name,
            'state_id' => $this->state_id,
            'state_name' => $this->state?->name,
            'street' => $this->street,
            'city' => $this->city,
            'zip' => $this->zip,
            'national_id' => $this->national_id,
            'iqama_no' => $this->iqama_no,
            'iqama_no_expiry_date' => $this->iqama_no_expiry_date?->format('Y-m-d'),
            'passport' => $this->passport,
            'passport_expiry_date' => $this->passport_expiry_date?->format('Y-m-d'),
            'driving_license_no' => $this->driving_license_no,
            'driving_license_expiry_date' => $this->driving_license_expiry_date?->format('Y-m-d'),
            'tuv_no' => $this->tuv_no,
            'tuv_no_expiry_date' => $this->tuv_no_expiry_date?->format('Y-m-d'),
            'join_date' => $this->join_date?->format('Y-m-d'),
            'type' => $this->type,
            'employment_type' => $this->employment_type,
            'duty_type' => $this->duty_type,
            'bank_name' => $this->bank_name,
            'bank_branch_name' => $this->bank_branch_name,
            'bank_account_no' => $this->bank_account_no,
            'iban_num' => $this->iban_num,
            'company_name' => $this->company_name,
            'basic_salary' => $this->basic_salary,
            'transport_allowance' => $this->transport_allowance,
            'gross_salary' => $this->gross_salary,
            'hourly_rate' => $this->hourly_rate,
            'documents' => $this->documents->map(static fn (EmployeeDocument $doc): array => [
                'id' => $doc->id,
                'name' => $doc->name,
                'file' => $doc->file,
                'expiry_date' => $doc->expiry_date?->format('Y-m-d'),
            ])->all(),
            'data' => $this->data,
            'is_active' => $this->is_active,
            'is_default' => $this->is_default,
            'sort_order' => $this->sort_order,
            'created_at' => format_datetime($this->created_at),
            'updated_at' => format_datetime($this->updated_at),
            'deleted_at' => format_datetime($this->deleted_at),
        ], $request);
    }
}
