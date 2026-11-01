<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\Tenant;
use App\Services\TenantMediaCleaner;
use Stancl\Tenancy\Events\DeletingTenant;

/** Runs before the tenant row + database are deleted. */
class DeleteTenantMedia
{
    public function __construct(private readonly TenantMediaCleaner $cleaner) {}

    public function handle(DeletingTenant $event): void
    {
        if ($event->tenant instanceof Tenant) {
            $this->cleaner->clean($event->tenant);
        }
    }
}
