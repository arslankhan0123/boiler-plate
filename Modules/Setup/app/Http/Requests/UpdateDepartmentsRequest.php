<?php

declare(strict_types=1);

namespace Modules\Setup\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Setup\Models\Department;

/**
 * Update payload is a bare array of `{ id, ...editable fields }`. All fields are
 * tenant-owned and editable; the service further gates them by the per-user
 * column-visibility matrix.
 */
class UpdateDepartmentsRequest extends FormRequest
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
            '*.id' => ['required', 'integer', Rule::exists(Department::class, 'id')->whereNull('deleted_at')],
            '*.name' => ['sometimes', 'string', 'max:255'],
            '*.code' => ['sometimes', 'nullable', 'string', 'max:255'],
            '*.description' => ['sometimes', 'nullable', 'string', 'max:5000'],
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
