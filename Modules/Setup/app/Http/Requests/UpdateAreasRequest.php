<?php

declare(strict_types=1);

namespace Modules\Setup\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Setup\Models\Area;

/**
 * Update payload is a bare array of `{ id, ...editable fields }`. All of an area's
 * fields are tenant-owned and editable (the parent `city_id` is fixed at create);
 * the service further gates them by the per-user column-visibility matrix.
 */
class UpdateAreasRequest extends FormRequest
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
            '*.id' => ['required', 'integer', Rule::exists(Area::class, 'id')->whereNull('deleted_at')],
            '*.name' => ['sometimes', 'string', 'max:255'],
            '*.address_line_1' => ['sometimes', 'nullable', 'string', 'max:255'],
            '*.address_line_2' => ['sometimes', 'nullable', 'string', 'max:255'],
            '*.area_code' => ['sometimes', 'nullable', 'string', 'max:32'],
            '*.phone' => ['sometimes', 'nullable', 'string', 'max:32'],
            '*.email' => ['sometimes', 'nullable', 'email', 'max:255'],
            '*.description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            '*.latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            '*.longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
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
