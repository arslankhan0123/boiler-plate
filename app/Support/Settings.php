<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Settings\Setting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Tenant settings (key/value in the tenant DB).
 *   Settings::get('company_name', 'Acme');  Settings::set(['company_name' => 'Acme']);
 */
class Settings
{
    /** Default brand logo shown when a tenant has not uploaded its own. */
    public const DEFAULT_LOGO = 'logo/logo-0d940195.png';

    /** Defaults used until the tenant saves its own values. */
    public const DEFAULTS = [
        'company_name' => null, 'legal_name' => null, 'email' => null, 'phone' => null, 'website' => null,
        'address' => null, 'city' => null, 'country' => 'Pakistan', 'ntn' => null, 'strn' => null, 'logo' => null,
        'fiscal_year_start' => '7', 'date_format' => 'd M Y', 'default_currency' => 'PKR',
        // Notifications (email)
        'notify_leave_request' => '1', 'notify_leave_decision' => '1', 'notify_payslip' => '1',
        'notify_task_assigned' => '1', 'notify_interview_assigned' => '1', 'notify_announcement' => '0',
        'mail_from_name' => null, 'mail_from_address' => null,
        // Tenant's own SMTP (password stored encrypted)
        'smtp_enabled' => '0', 'mail_mailer' => 'default', 'smtp_host' => null, 'smtp_port' => '587', 'smtp_encryption' => 'tls',
        'smtp_username' => null, 'smtp_password' => null, 'smtp_verify_peer' => '1',
    ];

    private static array $cache = [];

    private static function key(): string
    {
        return (string) (function_exists('tenant') && tenant() ? tenant()->getTenantKey() : 'central');
    }

    public static function all(): array
    {
        $k = self::key();
        if (! isset(self::$cache[$k])) {
            $stored = [];
            if ($k !== 'central') {
                try {
                    if (Schema::hasTable('tenant_settings')) {
                        $stored = Setting::query()->pluck('value', 'key')->all();
                    }
                } catch (Throwable) {
                    $stored = [];
                }
            }
            self::$cache[$k] = array_merge(self::DEFAULTS, $stored);
        }

        return self::$cache[$k];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $v = self::all()[$key] ?? null;

        return $v === null || $v === '' ? $default : $v;
    }

    public static function enabled(string $key): bool
    {
        return (string) self::get($key, '0') === '1';
    }

    public static function set(array $values): void
    {
        foreach ($values as $key => $value) {
            Setting::query()->updateOrCreate(['key' => $key], ['value' => is_bool($value) ? ($value ? '1' : '0') : $value]);
        }
        unset(self::$cache[self::key()]);
    }

    /**
     * Mailer to use for this tenant's emails. When the tenant has its own SMTP switched on,
     * registers a per-tenant "tenant_smtp_{id}" mailer from its settings; otherwise null (= app default).
     */
    public static function mailer(): ?string
    {
        if (! self::get('smtp_host')) {
            return null; // tenant has not configured its SMTP — no emails are sent
        }
        $name = 'tenant_smtp_'.self::key();
        $password = null;
        if ($enc = self::get('smtp_password')) {
            try {
                $password = Crypt::decryptString($enc);
            } catch (Throwable) {
                $password = null;
            }
        }
        $port = (int) self::get('smtp_port', 587);
        $encryption = self::get('smtp_encryption', 'tls');
        Config::set('mail.mailers.'.$name, [
            'transport' => 'smtp',
            'scheme' => ($encryption === 'ssl' || $port === 465) ? 'smtps' : 'smtp',
            'host' => self::get('smtp_host'),
            'port' => $port,
            'username' => self::get('smtp_username'),
            'password' => $password,
            'timeout' => 20,
            'verify_peer' => self::get('smtp_verify_peer', '1') === '1',
        ]);
        Mail::purge($name); // drop any cached transport built with old credentials

        return $name;
    }

    /** From address/name for this tenant's emails. */
    public static function fromAddress(): array
    {
        return [
            self::get('mail_from_address') ?: self::get('smtp_username'),
            self::get('mail_from_name') ?: self::companyName(),
        ];
    }

    /** Company name for headers, payslips, careers page… */
    public static function companyName(): string
    {
        return (string) (self::get('company_name') ?: (function_exists('tenant') && tenant() ? tenant('name') : config('app.name')));
    }

    /** The tenant's uploaded logo URL, or null when none has been uploaded. */
    public static function logoUrl(): ?string
    {
        $logo = self::get('logo');

        return $logo ? asset('storage/'.$logo) : null;
    }

    /**
     * Logo for every display surface (sidebar, storefront, invoices, receipts):
     * the tenant's own uploaded logo, or the shared app default when none is set.
     * Always returns a usable URL.
     */
    public static function brandLogoUrl(): string
    {
        return self::logoUrl() ?? asset(self::DEFAULT_LOGO);
    }
}
