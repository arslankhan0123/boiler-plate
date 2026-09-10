<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Auth\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (mounted at /api/v1)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->name('api.')->group(function (): void {
    Route::prefix('auth')->name('auth.')->group(function (): void {
        // Public: rate-limited to resist brute force (Section 21).
        Route::post('login', [AuthController::class, 'login'])
            ->middleware('throttle:6,1')
            ->name('login');

        // Step two of two-step login: verify the emailed OTP for a token.
        // Per-email AND per-IP throttled to resist OTP guessing.
        Route::post('login/verify-otp', [AuthController::class, 'verifyLoginOtp'])
            ->middleware('throttle:login-otp-verification')
            ->name('login.verify-otp');

        // OTP brute-force protection: per-email AND per-IP limiters
        // (defined in AppServiceProvider) guard issuance and verification.
        Route::post('forgot-password', [AuthController::class, 'forgotPassword'])
            ->middleware('throttle:otp-generation')
            ->name('forgot-password');

        Route::post('reset-password', [AuthController::class, 'resetPassword'])
            ->middleware('throttle:otp-verification')
            ->name('reset-password');

        Route::middleware(['auth:api', 'tenant.init'])->group(function (): void {
            Route::get('me', [AuthController::class, 'me'])->name('me');
            Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        });
    });
});
