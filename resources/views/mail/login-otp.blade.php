<x-mail::message>
# Login verification

Use the following One-Time Password (OTP) to complete your login:

<x-mail::panel>
{{ $otp }}
</x-mail::panel>

This code is valid for **{{ $expiryMinutes }} minutes**.

If you did not try to log in, please secure your account by changing your password.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
