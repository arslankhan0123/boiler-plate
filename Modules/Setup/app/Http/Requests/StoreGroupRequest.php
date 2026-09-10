<?php

declare(strict_types=1);

namespace Modules\Setup\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Setup\Models\Group;

/**
 * Create a single tenant-defined group. May nest under an existing group via the
 * optional `parent_id`.
 */
class StoreGroupRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'code' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'color' => ['sometimes', 'nullable', 'string', 'max:32'],
            'parent_id' => ['sometimes', 'nullable', 'integer', Rule::exists(Group::class, 'id')->whereNull('deleted_at')],
            'data' => ['sometimes', 'nullable', 'array'],
            'is_active' => ['sometimes', 'boolean'],
            'is_default' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
