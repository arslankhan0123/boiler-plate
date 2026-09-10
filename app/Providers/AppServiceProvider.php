<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\ColumnVisibilityService;
use App\Support\RequestContext;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Singleton so its per-request memo of resolved column visibility is
        // shared across every Resource serialised during the request.
        $this->app->singleton(ColumnVisibilityService::class);

        // Scoped (one instance per request): the CaptureRequestContext middleware
        // populates it; the Auditable trait reads it when dispatching audit jobs.
        $this->app->scoped(RequestContext::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Passport auth is CENTRAL: pin its oauth_* tables to the central
        // connection so token validation/revocation still works after tenancy
        // switches the default connection to a tenant DB (e.g. logout).
        Config::set('passport.connection', Config::string('tenancy.database.central_connection'));

        Passport::tokensExpireIn(Date::now()->addDays(15));
        Passport::refreshTokensExpireIn(Date::now()->addDays(30));
        Passport::personalAccessTokensExpireIn(Date::now()->addMonths(6));

        $this->configureRateLimiters();
    }

    /**
     * Register the named rate limiters used to protect the OTP / password-reset
     * endpoints against brute-force attacks. Each limiter applies two limits —
     * one keyed by the target email, one by the client IP — so an attacker is
     * throttled whether they pivot across IPs (same email) or across emails
     * (same IP). Exceeding either yields a 429 (handled in bootstrap/app.php).
     */
    private function configureRateLimiters(): void
    {
        $generateMax = Config::integer('app.otp_throttle.generate.max');
        $generateDecay = Config::integer('app.otp_throttle.generate.decay_minutes');
        $verifyMax = Config::integer('app.otp_throttle.verify.max');
        $verifyDecay = Config::integer('app.otp_throttle.verify.decay_minutes');
        $ipMultiplier = Config::integer('app.otp_throttle.ip_multiplier');

        // OTP issuance (forgot-password): cap how often an OTP can be requested.
        RateLimiter::for('otp-generation', fn (Request $request): array => [
            Limit::perMinutes($generateDecay, $generateMax)
                ->by('otp-gen:email:'.$this->throttleEmail($request)),
            Limit::perMinutes($generateDecay, $generateMax * $ipMultiplier)
                ->by('otp-gen:ip:'.(string) $request->ip()),
        ]);

        // OTP submission (reset-password): the brute-force surface — cap guesses
        // tightly within the OTP's validity window.
        RateLimiter::for('otp-verification', fn (Request $request): array => [
            Limit::perMinutes($verifyDecay, $verifyMax)
                ->by('otp-verify:email:'.$this->throttleEmail($request)),
            Limit::perMinutes($verifyDecay, $verifyMax * $ipMultiplier)
                ->by('otp-verify:ip:'.(string) $request->ip()),
        ]);

        // Login OTP submission (login/verify-otp): same guess-capping as above,
        // on an independent bucket so it can't be drained by password resets.
        RateLimiter::for('login-otp-verification', fn (Request $request): array => [
            Limit::perMinutes($verifyDecay, $verifyMax)
                ->by('login-otp-verify:email:'.$this->throttleEmail($request)),
            Limit::perMinutes($verifyDecay, $verifyMax * $ipMultiplier)
                ->by('login-otp-verify:ip:'.(string) $request->ip()),
        ]);
    }

    /**
     * Build a stable per-email throttle key. Normalised the same way the model
     * and form requests store it, so attempts can't be split by casing/spacing.
     * Falls back to the IP when no email is supplied.
     */
    private function throttleEmail(Request $request): string
    {
        $email = mb_strtolower(trim((string) $request->input('email')));

        return $email !== '' ? $email : (string) $request->ip();
    }
}
