<?php

declare(strict_types=1);

namespace App\Support\Billing;

use App\Models\Billing\PlatformSetting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Platform-level (master admin) settings, stored centrally in platform_settings.
 *
 *   PlatformSettings::get('platform_currency', 'USD');
 *   PlatformSettings::set(['pay_stripe_enabled' => '1']);
 *
 * Secret values (Stripe secret / webhook secret, PayPal secret) are encrypted at
 * rest and only ever used server-side. This is the billing counterpart to the
 * per-tenant App\Support\Settings — it is never tenant-scoped.
 */
class PlatformSettings
{
    public const DEFAULTS = [
        'platform_currency' => 'USD',

        // Stripe (platform account used to charge tenants)
        'pay_stripe_enabled' => '0',
        'stripe_publishable_key' => null,
        'stripe_secret_key' => null,        // encrypted
        'stripe_webhook_secret' => null,    // encrypted

        // PayPal
        'pay_paypal_enabled' => '0',
        'paypal_mode' => 'sandbox',
        'paypal_client_id' => null,
        'paypal_secret' => null,            // encrypted
        'paypal_webhook_id' => null,
    ];

    /** Keys whose values are stored encrypted. */
    public const SECRET_KEYS = ['stripe_secret_key', 'stripe_webhook_secret', 'paypal_secret'];

    /** Currencies Stripe can charge in (uppercased ISO codes). Used to validate plan/billing currency. */
    public const SUPPORTED_CURRENCIES = [
        'USD', 'AED', 'AFN', 'ALL', 'AMD', 'ANG', 'AOA', 'ARS', 'AUD', 'AWG', 'AZN', 'BAM', 'BBD', 'BDT', 'BGN',
        'BHD', 'BIF', 'BMD', 'BND', 'BOB', 'BRL', 'BSD', 'BWP', 'BYN', 'BZD', 'CAD', 'CDF', 'CHF', 'CLP', 'CNY',
        'COP', 'CRC', 'CVE', 'CZK', 'DJF', 'DKK', 'DOP', 'DZD', 'EGP', 'ETB', 'EUR', 'FJD', 'FKP', 'GBP', 'GEL',
        'GIP', 'GMD', 'GNF', 'GTQ', 'GYD', 'HKD', 'HNL', 'HRK', 'HTG', 'HUF', 'IDR', 'ILS', 'INR', 'ISK', 'JMD',
        'JOD', 'JPY', 'KES', 'KGS', 'KHR', 'KMF', 'KRW', 'KWD', 'KYD', 'KZT', 'LAK', 'LBP', 'LKR', 'LRD', 'LSL',
        'MAD', 'MDL', 'MGA', 'MKD', 'MMK', 'MNT', 'MOP', 'MUR', 'MVR', 'MWK', 'MXN', 'MYR', 'MZN', 'NAD', 'NGN',
        'NIO', 'NOK', 'NPR', 'NZD', 'OMR', 'PAB', 'PEN', 'PGK', 'PHP', 'PKR', 'PLN', 'PYG', 'QAR', 'RON', 'RSD',
        'RUB', 'RWF', 'SAR', 'SBD', 'SCR', 'SEK', 'SGD', 'SHP', 'SLE', 'SOS', 'SRD', 'STD', 'SZL', 'THB', 'TJS',
        'TND', 'TOP', 'TRY', 'TTD', 'TWD', 'TZS', 'UAH', 'UGX', 'UYU', 'UZS', 'VND', 'VUV', 'WST', 'XAF', 'XCD',
        'XOF', 'XPF', 'YER', 'ZAR', 'ZMW', 'BTN', 'GHS',
    ];

    public static function isSupportedCurrency(?string $code): bool
    {
        return in_array(strtoupper(trim((string) $code)), self::SUPPORTED_CURRENCIES, true);
    }

    private static ?array $cache = null;

    public static function all(): array
    {
        if (self::$cache === null) {
            $stored = [];
            try {
                // platform_settings lives in the CENTRAL database. The PlatformSetting
                // model forces the central connection, so this reads the right table
                // even under an active tenant (whose default connection is the tenant
                // DB). We query directly and let a missing table (pre-migration) fall
                // through to the catch — never gate on Schema::hasTable, which checks
                // the DEFAULT connection and would wrongly read "missing" for tenants.
                $stored = PlatformSetting::query()->pluck('value', 'key')->all();
            } catch (Throwable) {
                $stored = [];
            }
            self::$cache = array_merge(self::DEFAULTS, $stored);
        }

        return self::$cache;
    }

    public static function get(string $key, $default = null)
    {
        $v = self::all()[$key] ?? $default;

        return $v === null ? $default : $v;
    }

    /** @param array<string,mixed> $values */
    public static function set(array $values): void
    {
        foreach ($values as $key => $value) {
            if (in_array($key, self::SECRET_KEYS, true) && $value !== null && $value !== '') {
                $value = self::encrypt((string) $value);
            }
            PlatformSetting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value === null ? null : (string) $value],
            );
        }
        self::$cache = null;
    }

    public static function flush(): void
    {
        self::$cache = null;
    }

    // ── Secret helpers ───────────────────────────────────────────────────

    public static function encrypt(string $value): string
    {
        return Crypt::encryptString($value);
    }

    /** Decrypt a stored secret; falls back to the raw value if stored plain. */
    public static function decrypt(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }
        try {
            return Crypt::decryptString($value);
        } catch (Throwable) {
            return $value;
        }
    }

    /** A secret setting, decrypted. */
    public static function secret(string $key): string
    {
        return self::decrypt((string) self::get($key, ''));
    }

    // ── Currency ─────────────────────────────────────────────────────────

    public static function currency(): string
    {
        return strtoupper((string) self::get('platform_currency', 'USD'));
    }

    // ── Stripe ───────────────────────────────────────────────────────────

    public static function stripeEnabled(): bool
    {
        return (string) self::get('pay_stripe_enabled', '0') === '1';
    }

    public static function stripePublishableKey(): string
    {
        return trim((string) self::get('stripe_publishable_key', ''));
    }

    public static function stripeSecretKey(): string
    {
        return self::secret('stripe_secret_key');
    }

    public static function stripeWebhookSecret(): string
    {
        return self::secret('stripe_webhook_secret');
    }

    public static function stripeReady(): bool
    {
        return self::stripeEnabled()
            && self::stripePublishableKey() !== ''
            && self::stripeSecretKey() !== '';
    }

    public static function stripeTestMode(): bool
    {
        return str_starts_with(self::stripePublishableKey(), 'pk_test_');
    }

    // ── PayPal ───────────────────────────────────────────────────────────

    public static function paypalEnabled(): bool
    {
        return (string) self::get('pay_paypal_enabled', '0') === '1';
    }

    public static function paypalMode(): string
    {
        return self::get('paypal_mode', 'sandbox') === 'live' ? 'live' : 'sandbox';
    }

    public static function paypalClientId(): string
    {
        return trim((string) self::get('paypal_client_id', ''));
    }

    public static function paypalSecret(): string
    {
        return self::secret('paypal_secret');
    }

    public static function paypalReady(): bool
    {
        return self::paypalEnabled()
            && self::paypalClientId() !== ''
            && self::paypalSecret() !== '';
    }

    /** Any online gateway configured? */
    public static function anyGatewayReady(): bool
    {
        return self::stripeReady() || self::paypalReady();
    }
}
