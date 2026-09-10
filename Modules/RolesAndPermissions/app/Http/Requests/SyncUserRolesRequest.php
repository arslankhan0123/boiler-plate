<?php

declare(strict_types=1);

namespace Modules\RolesAndPermissions\Http\Requests;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncUserRolesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Role names are validated against the current tenant's `roles` table
     * (tenancy is initialized before validation runs).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'roles' => ['present', 'array'],
            'roles.*' => [
                'string',
                Rule::exists((new Role)->getTable(), 'name')->where('guard_name', 'api'),
            ],
        ];
    }
}
