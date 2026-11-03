<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Billing\Subscription;
use App\Support\Billing\BillingManager;
use Illuminate\Console\Command;

/**
 * Expire subscriptions whose paid/trial period has ended. Recurring card
 * subscriptions have their period pushed forward by the gateway webhooks before
 * they reach this point, so anything still past ends_at here is genuinely over:
 * it is expired and the tenant's business modules are revoked.
 *
 * Schedule: hourly (see routes/console.php).
 */
class ExpireSubscriptions extends Command
{
    protected $signature = 'subscriptions:expire';

    protected $description = 'Expire ended trials/subscriptions and revoke tenant access';

    public function handle(): int
    {
        $due = Subscription::query()
            ->where('status', Subscription::STATUS_ACTIVE)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', now())
            ->get();

        $count = 0;
        foreach ($due as $sub) {
            BillingManager::expire($sub);
            $count++;
        }

        $this->info("Expired {$count} subscription(s).");

        return self::SUCCESS;
    }
}
