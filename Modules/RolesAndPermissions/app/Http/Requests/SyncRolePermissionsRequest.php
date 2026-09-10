<?php

declare(strict_types=1);

namespace Modules\RolesAndPermissions\Http\Requests;

use App\Support\Authorization\ResourceRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncRolePermissionsRequest extends FormRequest
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
            'permissions' => ['present', 'array'],
            'permissions.*' => ['string', Rule::in(ResourceRegistry::permissions())],
        ];
    }
}
