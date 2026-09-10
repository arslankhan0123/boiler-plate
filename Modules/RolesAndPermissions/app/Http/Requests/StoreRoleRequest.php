<?php

declare(strict_types=1);

namespace Modules\RolesAndPermissions\Http\Requests;

use App\Models\Role;
use App\Support\Authorization\ResourceRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique((new Role)->getTable(), 'name')->where('guard_name', 'api'),
            ],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', Rule::in(ResourceRegistry::permissions())],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('name')) {
            $this->merge(['name' => trim((string) $this->input('name'))]);
        }
    }
}
