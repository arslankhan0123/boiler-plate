<?php

declare(strict_types=1);

namespace Modules\Setup\Transformers;

use App\Http\Resources\Concerns\FiltersVisibleColumns;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Setup\Models\Customer;

/**
 * @mixin Customer
 */
class CustomerResource extends JsonResource
{
    use FiltersVisibleColumns;

    protected function visibilityResource(): string
    {
        return 'customers';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->filterVisibleColumns([
            'id' => $this->id,
            'company_name' => $this->company_name,
            'code' => $this->code,
            'vat_number' => $this->vat_number,
            'email' => $this->email,
            'phone' => $this->phone,
            'mobile' => $this->mobile,
            'fax' => $this->fax,
            'whatsapp' => $this->whatsapp,
            'website' => $this->website,
            'short_name' => $this->short_name,
            'vendor_code' => $this->vendor_code,
            'group_name' => $this->group_name,
            'currency_id' => $this->currency_id,
            'currency_code' => $this->currency?->code,
            'country_id' => $this->country_id,
            'country_name' => $this->country?->name,
            'state_id' => $this->state_id,
            'state_name' => $this->state?->name,
            'default_language' => $this->default_language,
            'address' => $this->address,
            'city' => $this->city,
            'zip' => $this->zip,
            'location_url' => $this->location_url,
            'opening_balance' => $this->opening_balance,
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
