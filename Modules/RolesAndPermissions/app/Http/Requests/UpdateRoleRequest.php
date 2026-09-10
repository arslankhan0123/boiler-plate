<?php

declare(strict_types=1);

namespace Modules\RolesAndPermissions\Http\Requests;

use App\Models\Role;
use App\Support\Authorization\ResourceRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Renaming a default role is rejected in RoleService (403); here we only
     * enforce shape + uniqueness (ignoring the role being updated).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique((new Role)->getTable(), 'name')
                    ->where('guard_name', 'api')
                    ->ignore($this->route('role')),
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
