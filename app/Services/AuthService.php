<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\InvalidCredentialsException;
use App\Exceptions\InvalidOtpException;
use App\Mail\LoginOtpMail;
use App\Mail\PasswordResetOtpMail;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Passport\AccessToken;

class AuthService
{
    /**
     * Verify credentials. For users that require verification, issue a login
     * OTP instead of a token (two-step login); otherwise return a token.
     *
     * @return array{requires_verification: bool, user: User, token?: string}
     *
     * @throws InvalidCredentialsException
     */
    public function login(string $email, string $password): array
    {
        $user = User::query()->where('email', $email)->first();

        if ($user === null || ! Hash::check($password, $user->password)) {
            throw new InvalidCredentialsException;
        }

        if ($user->is_verification_required) {
            $otp = $this->issueOtp($user->email);
            Mail::to($user->email)->send(new LoginOtpMail($otp));

            return ['requires_verification' => true, 'user' => $user];
        }

        return [
            'requires_verification' => false,
            'user' => $user,
            'token' => $user->createToken('api')->accessToken,
        ];
    }

    /**
     * Verify a login OTP and, if valid and unexpired, issue an access token.
     * The used OTP is consumed.
     *
     * @return array{user: User, token: string}
     *
     * @throws InvalidOtpException
     */
    public function verifyLoginOtp(string $email, string $otp): array
    {
        $this->verifyOtp($email, $otp);

        $user = User::query()->where('email', $email)->firstOrFail();
        $this->consumeOtp($email);

        return ['user' => $user, 'token' => $user->createToken('api')->accessToken];
    }

    /**
     * Revoke the access token backing the current request.
     */
    public function logout(User $user): void
    {
        $token = $user->token();

        if ($token instanceof AccessToken) {
            $token->revoke();
        }
    }

    /**
     * Issue a fresh password-reset OTP for the given email and deliver it to
     * the user. The OTP is stored hashed; only the user receives the plain one.
     */
    public function sendPasswordResetOtp(string $email): void
    {
        $otp = $this->issueOtp($email);

        Mail::to($email)->send(new PasswordResetOtpMail($otp));
    }

    /**
     * Verify a password-reset OTP and, if valid and unexpired, set the new
     * password. The used OTP is consumed and all existing tokens are revoked.
     *
     * @throws InvalidOtpException
     */
    public function resetPassword(string $email, string $otp, string $password): void
    {
        $this->verifyOtp($email, $otp);

        $user = User::query()->where('email', $email)->firstOrFail();
        $user->password = $password;
        $user->save();

        // Consume the OTP and revoke existing tokens so the user must log in again.
        $this->consumeOtp($email);
        $user->tokens()->update(['revoked' => true]);
    }

    /**
     * Generate a fresh OTP, store it hashed against the email (replacing any
     * previous one), and return the plain OTP for delivery.
     */
    private function issueOtp(string $email): string
    {
        $otp = $this->generateOTP();

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            ['token' => Hash::make($otp), 'created_at' => Carbon::now()],
        );

        return $otp;
    }

    /**
     * Ensure the supplied OTP matches the stored hash and has not expired.
     * Expired OTPs are purged. Does NOT consume the OTP on success.
     *
     * @throws InvalidOtpException
     */
    private function verifyOtp(string $email, string $otp): void
    {
        $record = DB::table('password_reset_tokens')->where('email', $email)->first();

        if ($record === null || ! Hash::check($otp, (string) $record->token)) {
            throw new InvalidOtpException;
        }

        $expiresAt = Carbon::parse((string) $record->created_at)
            ->addMinutes(Config::integer('app.otp_expiry_minutes'));

        if ($expiresAt->isPast()) {
            $this->consumeOtp($email);

            throw new InvalidOtpException('The OTP has expired. Please request a new one.');
        }
    }

    /**
     * Remove the stored OTP for the given email.
     */
    private function consumeOtp(string $email): void
    {
        DB::table('password_reset_tokens')->where('email', $email)->delete();
    }

    /**
     * Generate a 6-digit numeric OTP that never starts with 0.
     */
    private function generateOTP(): string
    {
        return (string) random_int(100000, 999999);
    }
}
