<?php

declare(strict_types=1);

namespace App\Support\Ecommerce;

use App\Support\Settings;

/**
 * Storefront themes: a set of colour presets + layout variants a tenant can pick
 * for their public store. The store layout is driven entirely by CSS custom
 * properties, so a theme is just a block of variable overrides (plus a few rule
 * overrides for the spots that use literal colours) injected into the page head.
 *
 * A tenant may also set a custom primary colour, which overrides the preset's
 * brand colour (its shades are derived automatically).
 */
class StoreTheme
{
    /** Colour presets. Each supplies the core palette; shades/gradients derive from these. */
    public const THEMES = [
        'emerald' => ['label' => 'Emerald', 'brand' => '#0f766e', 'accent' => '#f59e0b', 'dark' => '#0b2b28'],
        'royal' => ['label' => 'Royal Purple', 'brand' => '#4f46e5', 'accent' => '#f59e0b', 'dark' => '#1e1b4b'],
        'ocean' => ['label' => 'Ocean Blue', 'brand' => '#0284c7', 'accent' => '#06b6d4', 'dark' => '#0c2a4d'],
        'sunset' => ['label' => 'Sunset', 'brand' => '#ea580c', 'accent' => '#e11d48', 'dark' => '#431407'],
        'berry' => ['label' => 'Berry', 'brand' => '#db2777', 'accent' => '#8b5cf6', 'dark' => '#500724'],
        'forest' => ['label' => 'Forest', 'brand' => '#15803d', 'accent' => '#ca8a04', 'dark' => '#052e16'],
        'graphite' => ['label' => 'Graphite', 'brand' => '#334155', 'accent' => '#f59e0b', 'dark' => '#0f172a'],
        'crimson' => ['label' => 'Crimson', 'brand' => '#dc2626', 'accent' => '#f59e0b', 'dark' => '#450a0a'],
    ];

    /** Layout variants (structural + density changes applied via a body class). */
    public const LAYOUTS = [
        'classic' => ['label' => 'Classic', 'desc' => 'Full-featured with top bar, gradients and depth.'],
        'minimal' => ['label' => 'Minimal', 'desc' => 'Clean and flat — no top bar, solid buttons, lighter.'],
        'rounded' => ['label' => 'Soft & Rounded', 'desc' => 'Pill buttons and extra-rounded cards, friendly feel.'],
    ];

    public const DEFAULT_THEME = 'emerald';
    public const DEFAULT_LAYOUT = 'classic';

    // ── Resolve current selection ────────────────────────────────────────

    public static function currentTheme(): string
    {
        $t = (string) Settings::get('store_theme', self::DEFAULT_THEME);

        return isset(self::THEMES[$t]) ? $t : self::DEFAULT_THEME;
    }

    public static function currentLayout(): string
    {
        $l = (string) Settings::get('store_layout', self::DEFAULT_LAYOUT);

        return isset(self::LAYOUTS[$l]) ? $l : self::DEFAULT_LAYOUT;
    }

    public static function currentPrimary(): ?string
    {
        $c = trim((string) Settings::get('store_primary', ''));

        return self::isHex($c) ? self::normalizeHex($c) : null;
    }

    public static function bodyClass(): string
    {
        return 'tl-'.self::currentLayout();
    }

    /** Resolve the full palette for a theme + optional custom primary. */
    public static function palette(?string $theme = null, ?string $primary = null): array
    {
        $theme ??= self::currentTheme();
        $primary ??= self::currentPrimary();
        $base = self::THEMES[$theme] ?? self::THEMES[self::DEFAULT_THEME];

        $brand = ($primary && self::isHex($primary)) ? self::normalizeHex($primary) : $base['brand'];
        $accent = $base['accent'];
        // When a custom primary is set, derive a matching dark shade; else use preset dark.
        $dark = $primary ? self::darken($brand, 0.62) : $base['dark'];

        return [
            'brand' => $brand,
            'brand_dark' => self::darken($brand, 0.22),
            'brand_light' => self::lighten($brand, 0.28),
            'brand_soft' => self::lighten($brand, 0.90),
            'accent' => $accent,
            'accent_soft' => self::lighten($accent, 0.82),
            'dark' => $dark,
            'shadow_rgb' => self::rgbTriple(self::darken($brand, 0.22)),
            'grad' => 'linear-gradient(135deg,'.self::darken($brand, 0.22).' 0%,'.$brand.' 55%,'.self::lighten($brand, 0.22).' 100%)',
            'grad_h' => 'linear-gradient(135deg,'.$brand.' 0%,'.self::lighten($brand, 0.12).' 55%,'.self::lighten($brand, 0.35).' 100%)',
        ];
    }

    /** Swatch colours (primary, accent) for the settings picker. */
    public static function swatch(string $theme): array
    {
        $t = self::THEMES[$theme] ?? self::THEMES[self::DEFAULT_THEME];

        return ['brand' => $t['brand'], 'accent' => $t['accent']];
    }

    // ── CSS generation (injected into the store <head>) ──────────────────

    public static function css(?string $theme = null, ?string $layout = null, ?string $primary = null): string
    {
        $p = self::palette($theme, $primary);
        $layout ??= self::currentLayout();

        $root = ":root{"
            ."--brand:{$p['brand']};--brand-dark:{$p['brand_dark']};--brand-light:{$p['brand_light']};--brand-soft:{$p['brand_soft']};"
            ."--accent:{$p['accent']};--accent-soft:{$p['accent_soft']};"
            ."--dark:{$p['dark']};--shadow-rgb:{$p['shadow_rgb']};"
            ."--grad:{$p['grad']};--grad-h:{$p['grad_h']};"
            ."}";

        // Override the few rules that use literal colours instead of variables.
        $overrides =
            ".topbar{background:linear-gradient(90deg,var(--dark),var(--brand-dark) 55%,var(--dark))}"
            .".footer{background:var(--dark)}"
            .".phero h1 span{background:linear-gradient(90deg,var(--brand-light),var(--accent));-webkit-background-clip:text;background-clip:text;color:transparent}"
            .".socials a:hover{background:var(--accent);color:var(--dark)}"
            .".btn-g{box-shadow:0 10px 24px -10px rgba(var(--shadow-rgb),.55)}"
            .".btn-g:hover{box-shadow:0 16px 30px -10px rgba(var(--shadow-rgb),.65)}"
            .".logo .mk,.totop{box-shadow:0 10px 22px -8px rgba(var(--shadow-rgb),.7)}";

        return $root.$overrides.self::layoutCss($layout);
    }

    private static function layoutCss(string $layout): string
    {
        return match ($layout) {
            'minimal' =>
                "body.tl-minimal .topbar{display:none}"
                ."body.tl-minimal .phero::before{opacity:.12}"
                ."body.tl-minimal .sec-deco{display:none}"
                ."body.tl-minimal .btn-g{background:var(--brand);box-shadow:none}"
                ."body.tl-minimal .btn-g::after{display:none}"
                ."body.tl-minimal .btn-g:hover{transform:none;filter:brightness(1.06);box-shadow:none}"
                ."body.tl-minimal .allcat-btn,body.tl-minimal .deal-btn{box-shadow:none}"
                ."body.tl-minimal .header{box-shadow:0 1px 0 var(--line)}"
                ."body.tl-minimal .icon-btn,body.tl-minimal .search form{border-width:1px}",
            'rounded' =>
                "body.tl-rounded{--r:20px;--r-lg:30px}"
                ."body.tl-rounded .btn-g,body.tl-rounded .btn-o,body.tl-rounded .allcat-btn,body.tl-rounded .deal-btn{border-radius:999px}"
                ."body.tl-rounded .icon-btn,body.tl-rounded .burger{border-radius:16px}"
                ."body.tl-rounded .search form,body.tl-rounded .inp,body.tl-rounded .search input{border-radius:999px}"
                ."body.tl-rounded .search button{border-radius:0 999px 999px 0}"
                ."body.tl-rounded .allcat .dd,body.tl-rounded .drawer{border-radius:22px}",
            default => '',
        };
    }

    // ── Colour helpers ───────────────────────────────────────────────────

    public static function isHex(string $c): bool
    {
        return (bool) preg_match('/^#?[0-9a-fA-F]{6}$/', trim($c));
    }

    public static function normalizeHex(string $c): string
    {
        $c = ltrim(trim($c), '#');

        return '#'.strtolower($c);
    }

    /** @return array{0:int,1:int,2:int} */
    private static function toRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }

    private static function fromRgb(int $r, int $g, int $b): string
    {
        return sprintf('#%02x%02x%02x', max(0, min(255, $r)), max(0, min(255, $g)), max(0, min(255, $b)));
    }

    /** Mix toward white by $amt (0..1). */
    public static function lighten(string $hex, float $amt): string
    {
        [$r, $g, $b] = self::toRgb($hex);

        return self::fromRgb(
            (int) round($r + (255 - $r) * $amt),
            (int) round($g + (255 - $g) * $amt),
            (int) round($b + (255 - $b) * $amt),
        );
    }

    /** Mix toward black by $amt (0..1). */
    public static function darken(string $hex, float $amt): string
    {
        [$r, $g, $b] = self::toRgb($hex);

        return self::fromRgb(
            (int) round($r * (1 - $amt)),
            (int) round($g * (1 - $amt)),
            (int) round($b * (1 - $amt)),
        );
    }

    public static function rgbTriple(string $hex): string
    {
        [$r, $g, $b] = self::toRgb($hex);

        return "$r,$g,$b";
    }
}
