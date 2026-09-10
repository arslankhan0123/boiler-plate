<?php

declare(strict_types=1);

namespace Modules\RolesAndPermissions\Services;

use App\Support\Authorization\ResourceRegistry;

/**
 * Exposes the read-only permission catalog (the source of truth that
 * `permissions:sync` writes into each tenant DB), grouped by resource so a UI
 * can render permission pickers per resource.
 */
class PermissionCatalogService
{
    /**
     * @return list<array{resource: string, permissions: list<string>}>
     */
    public function grouped(): array
    {
        /** @var array<string, list<string>> $groups */
        $groups = [];

        foreach (ResourceRegistry::permissions() as $permission) {
            [$resource] = explode('.', $permission, 2);
            $groups[$resource][] = $permission;
        }

        $catalog = [];
        foreach ($groups as $resource => $permissions) {
            $catalog[] = [
                'resource' => $resource,
                'permissions' => $permissions,
            ];
        }

        return $catalog;
    }
}
