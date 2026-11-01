<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        $this->ensureTenantIsActive();
    }

    /**
     * Tenant users may only sign in while their tenant exists and is active.
     * Credentials were correct, so we log them straight back out and show a toast.
     */
    protected function ensureTenantIsActive(): void
    {
        $user = Auth::user();

        if (! $user || $user->tenant_id === null || $user->is_platform_admin) {
            return;
        }

        $tenant = \App\Models\Tenant::query()->find($user->tenant_id);

        if ($tenant && $tenant->is_active) {
            return;
        }

        Auth::guard('web')->logout();

        $toast = $tenant
            ? ['title' => 'Account inactive', 'message' => "Your organization ({$tenant->name}) is currently inactive. Please contact the administrator."]
            : ['title' => 'Organization not found', 'message' => 'Your organization no longer exists. Please contact the administrator.'];

        session()->flash('toast', ['type' => 'error'] + $toast);

        throw ValidationException::withMessages(['tenant' => $toast['message']]);
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
