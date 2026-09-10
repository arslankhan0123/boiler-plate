<?php

declare(strict_types=1);

namespace Modules\Setup\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Setup\Models\Country;
use Modules\Setup\Models\Currency;
use Modules\Setup\Models\State;
use Modules\Setup\Models\Supplier;

/**
 * Update payload is a bare array of `{ id, ...editable fields }`. All fields are
 * tenant-owned and editable; the service further gates them by the per-user
 * column-visibility matrix.
 */
class UpdateSuppliersRequest extends FormRequest
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
            '*.id' => ['required', 'integer', Rule::exists(Supplier::class, 'id')->whereNull('deleted_at')],
            '*.company_name' => ['sometimes', 'string', 'max:255'],
            '*.vat_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            '*.contact_person' => ['sometimes', 'nullable', 'string', 'max:255'],
            '*.email' => ['sometimes', 'nullable', 'email', 'max:255'],
            '*.phone' => ['sometimes', 'nullable', 'string', 'max:32'],
            '*.whatsapp' => ['sometimes', 'nullable', 'string', 'max:32'],
            '*.website' => ['sometimes', 'nullable', 'string', 'max:255'],
            '*.group_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            '*.currency_id' => ['sometimes', 'nullable', 'integer', Rule::exists(Currency::class, 'id')->whereNull('deleted_at')],
            '*.country_id' => ['sometimes', 'nullable', 'integer', Rule::exists(Country::class, 'id')->whereNull('deleted_at')],
            '*.state_id' => ['sometimes', 'nullable', 'integer', Rule::exists(State::class, 'id')->whereNull('deleted_at')],
            '*.default_language' => ['sometimes', 'nullable', 'string', 'max:8'],
            '*.street' => ['sometimes', 'nullable', 'string', 'max:255'],
            '*.city' => ['sometimes', 'nullable', 'string', 'max:255'],
            '*.zip' => ['sometimes', 'nullable', 'string', 'max:32'],
            '*.po_box' => ['sometimes', 'nullable', 'string', 'max:32'],
            '*.mailing_address' => ['sometimes', 'nullable', 'string', 'max:255'],
            '*.opening_balance' => ['sometimes', 'nullable', 'numeric'],
            '*.data' => ['sometimes', 'nullable', 'array'],
            '*.is_active' => ['sometimes', 'boolean'],
            '*.is_default' => ['sometimes', 'boolean'],
            '*.sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function rows(): array
    {
        /** @var list<array<string, mixed>> $validated */
        $validated = $this->validated();

        return $validated;
    }
}
