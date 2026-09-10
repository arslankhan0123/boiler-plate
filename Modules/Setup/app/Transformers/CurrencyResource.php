<?php

declare(strict_types=1);

namespace Modules\Setup\Transformers;

use App\Http\Resources\Concerns\FiltersVisibleColumns;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Setup\Models\Currency;

/**
 * @mixin Currency
 */
class CurrencyResource extends JsonResource
{
    use FiltersVisibleColumns;

    protected function visibilityResource(): string
    {
        return 'currencies';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->filterVisibleColumns([
            'id' => $this->id,
            'lookup_id' => $this->lookup_id,
            'code' => $this->code,
            'name' => $this->name,
            'symbol' => $this->symbol,
            'numeric_code' => $this->numeric_code,
            'decimal_digits' => $this->decimal_digits,
            'is_active' => $this->is_active,
            'is_default' => $this->is_default,
            'sort_order' => $this->sort_order,
            'created_at' => format_datetime($this->created_at),
            'updated_at' => format_datetime($this->updated_at),
            'deleted_at' => format_datetime($this->deleted_at),
        ], $request);
    }
}
