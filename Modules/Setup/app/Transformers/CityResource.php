<?php

declare(strict_types=1);

namespace Modules\Setup\Transformers;

use App\Http\Resources\Concerns\FiltersVisibleColumns;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Setup\Models\City;

/**
 * @mixin City
 */
class CityResource extends JsonResource
{
    use FiltersVisibleColumns;

    protected function visibilityResource(): string
    {
        return 'cities';
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
            'state_id' => $this->state_id,
            'state_name' => $this->state?->name,
            'country_id' => $this->state?->country_id,
            'country_name' => $this->state?->country?->name,
            'country_iso2' => $this->state?->country?->iso2,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'is_active' => $this->is_active,
            'is_default' => $this->is_default,
            'sort_order' => $this->sort_order,
            'created_at' => format_datetime($this->created_at),
            'updated_at' => format_datetime($this->updated_at),
            'deleted_at' => format_datetime($this->deleted_at),
        ], $request);
    }
}
