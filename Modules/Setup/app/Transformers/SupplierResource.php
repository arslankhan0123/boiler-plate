<?php

declare(strict_types=1);

namespace Modules\Setup\Transformers;

use App\Http\Resources\Concerns\FiltersVisibleColumns;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Setup\Models\Supplier;

/**
 * @mixin Supplier
 */
class SupplierResource extends JsonResource
{
    use FiltersVisibleColumns;

    protected function visibilityResource(): string
    {
        return 'suppliers';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->filterVisibleColumns([
            'id' => $this->id,
            'company_name' => $this->company_name,
            'vat_number' => $this->vat_number,
            'contact_person' => $this->contact_person,
            'email' => $this->email,
            'phone' => $this->phone,
            'whatsapp' => $this->whatsapp,
            'website' => $this->website,
            'group_name' => $this->group_name,
            'currency_id' => $this->currency_id,
            'currency_code' => $this->currency?->code,
            'country_id' => $this->country_id,
            'country_name' => $this->country?->name,
            'state_id' => $this->state_id,
            'state_name' => $this->state?->name,
            'default_language' => $this->default_language,
            'street' => $this->street,
            'city' => $this->city,
            'zip' => $this->zip,
            'po_box' => $this->po_box,
            'mailing_address' => $this->mailing_address,
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
