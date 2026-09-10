<?php

declare(strict_types=1);

namespace Modules\Setup\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Create payload is a bare array of master references: `[{ "lookup_id": 52 }, ...]`.
 * The service resolves each id against the central lookups master (type=city) and
 * snapshots it.
 */
class StoreCitiesRequest extends FormRequest
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
            '*.lookup_id' => ['required', 'integer', 'min:1'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->all() === []) {
                $validator->errors()->add('0', 'Provide at least one city to add.');
            }
        });
    }

    /**
     * @return list<int>
     */
    public function lookupIds(): array
    {
        /** @var list<array{lookup_id: int|string}> $validated */
        $validated = $this->validated();

        return array_map(static fn (array $row): int => (int) $row['lookup_id'], $validated);
    }
}
