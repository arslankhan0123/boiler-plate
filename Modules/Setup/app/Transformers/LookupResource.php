<?php

declare(strict_types=1);

namespace Modules\Setup\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Setup\Models\Lookup;

/**
 * @mixin Lookup
 */
class LookupResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'code' => $this->code,
            'name' => $this->name,
            'parent_id' => $this->parent_id,
            // Full hierarchical label, e.g. "Lahore, Punjab, Pakistan" (city) or
            // "Punjab, Pakistan" (state) — lets a picker disambiguate same-named rows.
            'full_name' => $this->fullName(),
            'image' => $this->image,
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
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'created_at' => format_datetime($this->created_at),
            'updated_at' => format_datetime($this->updated_at),
        ];
    }

    /**
     * Compose the hierarchical label from the eager-loaded parent chain
     * (self → parent → grandparent), skipping any missing level.
     */
    private function fullName(): string
    {
        $names = array_filter(
            [$this->name, $this->parent?->name, $this->parent?->parent?->name],
            static fn (?string $name): bool => $name !== null && $name !== '',
        );

        return implode(', ', $names);
    }
}
