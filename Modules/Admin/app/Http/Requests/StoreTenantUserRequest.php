<?php

declare(strict_types=1);

namespace Modules\Admin\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTenantUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Role names (optional) are validated against the target tenant's database in
     * TenantService (they live in the tenant DB, not central).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique((new User)->getTable(), 'email'),
            ],
            'password' => ['required', 'string', 'min:8'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge(['email' => mb_strtolower(trim((string) $this->input('email')))]);
        }
    }
}
