<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;
use App\Notifications\AppNotice;
use Illuminate\Support\Facades\Log;
use Modules\Setup\Models\Employee;
use Throwable;

/**
 * Send an email only if the tenant switched that notification on
 * (Settings → Notifications). Never breaks the request if mail fails.
 *
 *   Notify::send('notify_task_assigned', $employee, 'New task', ['…'], 'Open task', $url);
 */
class Notify
{
    public static function send(string $setting, User|Employee|null $to, string $subject, array $lines, ?string $actionText = null, ?string $actionUrl = null): void
    {
        if (! $to || ! Settings::enabled($setting) || ! Settings::get('smtp_host')) {
            return;
        }

        $user = $to instanceof Employee ? ($to->user_id ? User::query()->find($to->user_id) : null) : $to;
        if (! $user || ! $user->email) {
            return;
        }

        try {
            $user->notify(new AppNotice($subject, $lines, $actionText, $actionUrl));
        } catch (Throwable $e) {
            Log::warning('Notification failed: '.$e->getMessage(), ['setting' => $setting, 'user' => $user->id]);
        }
    }
}
