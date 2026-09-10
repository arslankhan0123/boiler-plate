<?php

declare(strict_types=1);

namespace Modules\Setup\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Setup\Models\Customer;

/**
 * Force-delete payload is a bare array of ids: `[1, 2, 3]`. Only already
 * soft-deleted rows are eligible. Wrapped under `ids` for validation.
 */
class ForceDeleteCustomersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->replace(['ids' => array_values($this->all())]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', Rule::exists(Customer::class, 'id')->whereNotNull('deleted_at')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ids.required' => 'Provide at least one soft-deleted id to remove permanently.',
            'ids.array' => 'Provide a list of ids to remove permanently.',
        ];
    }

    /**
     * @return list<int>
     */
    public function ids(): array
    {
        /** @var array{ids: list<int|string>} $validated */
        $validated = $this->validated();

        return array_map(static fn (int|string $id): int => (int) $id, $validated['ids']);
    }
}
