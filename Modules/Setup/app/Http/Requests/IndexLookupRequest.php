<?php

declare(strict_types=1);

namespace Modules\Setup\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexLookupRequest extends FormRequest
{
    /**
     * The lookup types a client may filter by.
     *
     * @var list<string>
     */
    public const TYPES = ['country', 'state', 'city', 'currency', 'continent', 'language', 'status'];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Accept the common boolean spellings for the `is_active` filter
     * (`true`/`false`/`1`/`0`/…) before validation — Laravel's `boolean` rule
     * alone rejects the string `"true"`.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('is_active')) {
            $this->merge([
                'is_active' => filter_var($this->input('is_active'), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['sometimes', 'string', Rule::in(self::TYPES)],
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
