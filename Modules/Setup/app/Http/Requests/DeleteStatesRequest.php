<?php

declare(strict_types=1);

namespace Modules\Setup\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Setup\Models\State;

/**
 * Delete payload is a bare array of ids: `[1, 2, 3]` (soft delete). Wrapped under
 * `ids` for validation (Laravel's `*` wildcard mishandles a flat scalar list).
 */
class DeleteStatesRequest extends FormRequest
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
            'ids.*' => ['integer', Rule::exists(State::class, 'id')->whereNull('deleted_at')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ids.required' => 'Provide at least one id to delete.',
            'ids.array' => 'Provide a list of ids to delete.',
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
