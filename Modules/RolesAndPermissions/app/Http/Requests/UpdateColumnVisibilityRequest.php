<?php

declare(strict_types=1);

namespace Modules\RolesAndPermissions\Http\Requests;

use App\Models\ResourceColumn;
use App\Support\Authorization\ColumnExposure;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateColumnVisibilityRequest extends FormRequest
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
            'grants' => ['present', 'array'],
            'grants.*.resource' => ['required', 'string'],
            'grants.*.column' => ['required', 'string'],
            'grants.*.exposure' => ['required', Rule::enum(ColumnExposure::class)],
        ];
    }

    /**
     * Enforce that each grant targets a catalogued column and is raise-only
     * (exposure may not drop below the column's catalog base).
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $grants = $this->input('grants');

            if (! is_array($grants)) {
                return;
            }

            foreach ($grants as $index => $grant) {
                if (! is_array($grant)) {
                    continue;
                }

                $resource = $grant['resource'] ?? null;
                $column = $grant['column'] ?? null;
                $exposure = $grant['exposure'] ?? null;

                if (! is_string($resource) || ! is_string($column) || ! is_string($exposure)) {
                    continue;
                }

                $catalog = ResourceColumn::query()
                    ->where('resource', $resource)
                    ->where('column', $column)
                    ->first();

                if ($catalog === null) {
                    $validator->errors()->add("grants.{$index}.column", "The column {$resource}.{$column} is not catalogued.");

                    continue;
                }

                $enum = ColumnExposure::tryFrom($exposure);

                if ($enum !== null && $enum->rank() < $catalog->exposure->rank()) {
                    $validator->errors()->add(
                        "grants.{$index}.exposure",
                        "Exposure for {$resource}.{$column} cannot be lower than its base ({$catalog->exposure->value}).",
                    );
                }
            }
        });
    }

    /**
     * The validated grants, with exposure cast to the enum.
     *
     * @return list<array{resource: string, column: string, exposure: ColumnExposure}>
     */
    public function grants(): array
    {
        $validated = $this->validated();
        $grants = is_array($validated['grants'] ?? null) ? $validated['grants'] : [];

        $result = [];

        foreach ($grants as $grant) {
            if (! is_array($grant)) {
                continue;
            }

            $result[] = [
                'resource' => (string) $grant['resource'],
                'column' => (string) $grant['column'],
                'exposure' => ColumnExposure::from((string) $grant['exposure']),
            ];
        }

        return $result;
    }
}
