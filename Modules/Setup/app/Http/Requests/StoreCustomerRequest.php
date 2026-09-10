<?php

declare(strict_types=1);

namespace Modules\Setup\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Setup\Models\Country;
use Modules\Setup\Models\Currency;
use Modules\Setup\Models\State;

/**
 * Create a single tenant-defined customer. A flat object of custom fields; the
 * optional country/state/currency links are validated to exist in the tenant's
 * own Setup tables when provided.
 */
class StoreCustomerRequest extends FormRequest
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
            'company_name' => ['required', 'string', 'max:255'],
            'code' => ['sometimes', 'nullable', 'string', 'max:255'],
            'vat_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:32'],
            'mobile' => ['sometimes', 'nullable', 'string', 'max:32'],
            'fax' => ['sometimes', 'nullable', 'string', 'max:32'],
            'whatsapp' => ['sometimes', 'nullable', 'string', 'max:32'],
            'website' => ['sometimes', 'nullable', 'string', 'max:255'],
            'short_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'vendor_code' => ['sometimes', 'nullable', 'string', 'max:255'],
            'group_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'currency_id' => ['sometimes', 'nullable', 'integer', Rule::exists(Currency::class, 'id')->whereNull('deleted_at')],
            'country_id' => ['sometimes', 'nullable', 'integer', Rule::exists(Country::class, 'id')->whereNull('deleted_at')],
            'state_id' => ['sometimes', 'nullable', 'integer', Rule::exists(State::class, 'id')->whereNull('deleted_at')],
            'default_language' => ['sometimes', 'nullable', 'string', 'max:8'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'nullable', 'string', 'max:255'],
            'zip' => ['sometimes', 'nullable', 'string', 'max:32'],
            'location_url' => ['sometimes', 'nullable', 'string', 'max:255'],
            'opening_balance' => ['sometimes', 'nullable', 'numeric'],
            'data' => ['sometimes', 'nullable', 'array'],
            'is_active' => ['sometimes', 'boolean'],
            'is_default' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
