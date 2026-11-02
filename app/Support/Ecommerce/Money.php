<?php

declare(strict_types=1);

namespace App\Support\Ecommerce;

use App\Support\Settings;

/**
 * Single-currency money formatting for the public storefront.
 *
 * The tenant's currency comes from Settings('default_currency'). This class maps a
 * handful of common ISO codes to a symbol + sensible decimal count, and formats an
 * amount as e.g. "₨ 1,200" or "$12.50". PSR-4 autoloaded — no composer dump needed.
 */
class Money
{
    /** code => [symbol, decimals] */
    private const MAP = [
        'PKR' => ['₨', 0],
        'INR' => ['₹', 0],
        'JPY' => ['¥', 0],
        'USD' => ['$', 2],
        'EUR' => ['€', 2],
        'GBP' => ['£', 2],
        'AED' => ['AED ', 2],
        'SAR' => ['SAR ', 2],
        'BDT' => ['৳', 0],
        'CAD' => ['C$', 2],
        'AUD' => ['A$', 2],
    ];

    public static function code(): string
    {
        return strtoupper((string) Settings::get('default_currency', 'PKR'));
    }

    public static function symbol(?string $code = null): string
    {
        $code = strtoupper($code ?: self::code());

        return self::MAP[$code][0] ?? ($code.' ');
    }

    public static function decimals(?string $code = null): int
    {
        $code = strtoupper($code ?: self::code());

        return self::MAP[$code][1] ?? 2;
    }

    /** Format an amount in the tenant currency, e.g. "₨ 1,200". */
    public static function fmt($amount, ?string $code = null): string
    {
        $code = strtoupper($code ?: self::code());
        $sym = self::symbol($code);
        $dec = self::decimals($code);
        $n = number_format((float) $amount, $dec);
        // Letter-ish symbols ("AED ", "SAR ", "C$") already carry their own spacing.
        $space = preg_match('/[A-Za-z]/', $sym) ? '' : '';

        return $sym.$space.$n;
    }
}
