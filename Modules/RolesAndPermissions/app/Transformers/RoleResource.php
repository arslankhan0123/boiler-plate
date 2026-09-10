<?php

declare(strict_types=1);

namespace Modules\RolesAndPermissions\Transformers;

use App\Models\Role;
use App\Support\Authorization\DefaultRoles;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Role
 */
class RoleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'guard_name' => $this->guard_name,
            'is_default' => DefaultRoles::isProtected($this->name),
            'permissions' => $this->permissions->pluck('name')->values()->all(),
            'created_at' => format_datetime($this->created_at),
            'updated_at' => format_datetime($this->updated_at),
        ];
    }
}
