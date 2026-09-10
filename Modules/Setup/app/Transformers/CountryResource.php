<?php

declare(strict_types=1);

namespace Modules\Setup\Transformers;

use App\Http\Resources\Concerns\FiltersVisibleColumns;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Setup\Models\Country;

/**
 * @mixin Country
 */
class CountryResource extends JsonResource
{
    use FiltersVisibleColumns;

    protected function visibilityResource(): string
    {
        return 'countries';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->filterVisibleColumns([
            'id' => $this->id,
            'lookup_id' => $this->lookup_id,
            'name' => $this->name,
            'iso2' => $this->iso2,
            'iso3' => $this->iso3,
            'numeric_code' => $this->numeric_code,
            'phone_code' => $this->phone_code,
            'capital' => $this->capital,
            'region' => $this->region,
            'currency_code' => $this->currency_code,
            'currency_symbol' => $this->currency_symbol,
            'locale' => $this->locale,
            'timezone' => $this->timezone,
            'flag_image' => $this->flag_image,
            'is_active' => $this->is_active,
            'is_default' => $this->is_default,
            'sort_order' => $this->sort_order,
            'created_at' => format_datetime($this->created_at),
            'updated_at' => format_datetime($this->updated_at),
            'deleted_at' => format_datetime($this->deleted_at),
        ], $request);
    }
}
