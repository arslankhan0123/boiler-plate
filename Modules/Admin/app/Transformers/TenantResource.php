<?php

declare(strict_types=1);

namespace Modules\Admin\Transformers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Config;

/**
 * @mixin Tenant
 */
class TenantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'is_active' => $this->is_active,
            'database' => Config::string('tenancy.database.prefix').$this->id.Config::string('tenancy.database.suffix'),
            'created_at' => format_datetime($this->created_at),
            'updated_at' => format_datetime($this->updated_at),
        ];
    }
}
